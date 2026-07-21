<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ActivityNotifier;
use App\Support\StockNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $hasActiveFilters = $request->filled('search')
            || $request->filled('status')
            || $request->filled('stock');

        $baseQuery = Product::where('tenant_id', $request->user()->tenant_id);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'low' => (clone $baseQuery)->whereColumn('inventory', '<=', 'minimum_stock_level')->where('inventory', '>', 0)->count(),
            'out' => (clone $baseQuery)->where('inventory', 0)->count(),
            'value' => (clone $baseQuery)->selectRaw('COALESCE(SUM(price * inventory), 0) as value')->value('value'),
        ];

        $query = (clone $baseQuery)
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('stock'), function ($query) use ($request) {
                match ((string) $request->string('stock')) {
                    'out' => $query->where('inventory', 0),
                    'low' => $query->whereColumn('inventory', '<=', 'minimum_stock_level')->where('inventory', '>', 0),
                    'healthy' => $query->whereColumn('inventory', '>', 'minimum_stock_level'),
                    default => null,
                };
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                match ((string) $request->string('sort')) {
                    'stock_low' => $query->orderBy('inventory'),
                    'stock_high' => $query->orderByDesc('inventory'),
                    'name' => $query->orderBy('name'),
                    default => $query->latest(),
                };
            }, fn ($query) => $query->latest());

        $products = $query
            ->paginate($perPage)
            ->appends(array_merge($request->except('page'), ['per_page' => $perPage]));

        return view('products.index', compact('products', 'stats', 'perPageOptions', 'perPage', 'hasActiveFilters'));
    }

    public function create(Request $request): View
    {
        return view('products.create', [
            'product' => new Product(['status' => 'draft', 'inventory' => 0, 'minimum_stock_level' => 10]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['image_url'] = $this->storeCroppedImage($request) ?: ($data['image_url'] ?? null);
        $data['inventory'] = 0;
        $data['purchase_price'] = 0;
        $data['price'] = 0;
        $data['tax_percentage'] = $request->user()->tenant->default_tax_percentage ?? 0;
        $data['compare_at_price'] = null;
        unset($data['cropped_image']);

        $product = Product::create($data);
        StockNotifier::sync($product);
        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'product_created',
            'Product created',
            $request->user()->name.' created product '.$product->name.'.'
        );

        return redirect()
            ->route('products.index')
            ->with('status', 'Product created.');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorizeProduct($request, $product);

        return view('products.edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProduct($request, $product);

        $data = $this->validated($request, $product);
        $uploadedImage = $this->storeCroppedImage($request);

        if ($uploadedImage) {
            $data['image_url'] = $uploadedImage;
        }

        unset($data['inventory']);
        unset($data['tax_percentage']);
        unset($data['compare_at_price']);
        unset($data['cropped_image']);

        $product->update($data);
        StockNotifier::sync($product);
        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'product_updated',
            'Product updated',
            $request->user()->name.' updated product '.$product->name.'.'
        );

        return redirect()
            ->route('products.index')
            ->with('status', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProduct($request, $product);

        if ($product->status === 'active') {
            return back()->with('status', 'Active products cannot be deleted. Archive the product first.');
        }

        $product->update(['deleted_status' => 1]);

        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'product_deleted',
            'Product deleted',
            $request->user()->name.' deleted product '.$product->name.'.'
        );

        return redirect()
            ->route('products.index')
            ->with('status', 'Product deleted.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where('tenant_id', $request->user()->tenant_id)
                    ->ignore($product?->id),
            ],
            'barcode' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'inventory' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'minimum_stock_level' => ['required', 'integer', 'min:0', 'max:999999'],
            'reserved_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'damaged_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'returned_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'status' => ['required', 'in:draft,active,archived'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'cropped_image' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];

        return $request->validate($rules);
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        abort_unless((int) $product->tenant_id === (int) $request->user()->tenant_id, 404);
    }

    private function storeCroppedImage(Request $request): ?string
    {
        $image = $request->input('cropped_image');

        if (! is_string($image) || $image === '') {
            return null;
        }

        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $image)) {
            return null;
        }

        $encoded = preg_replace('/^data:image\/(jpeg|jpg|png|webp);base64,/', '', $image);
        $binary = base64_decode($encoded, true);

        if ($binary === false) {
            return null;
        }

        $tenantId = (string) $request->user()->tenant_id;
        $directory = public_path('uploads/'.$tenantId.'/products');
        File::ensureDirectoryExists($directory);

        $filename = Str::uuid().'.jpg';
        file_put_contents($directory.DIRECTORY_SEPARATOR.$filename, $binary);

        return asset('uploads/'.$tenantId.'/products/'.$filename);
    }
}
