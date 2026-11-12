<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\ActivityNotifier;
use App\Support\StockNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $request->user()->tenant_id;
        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $productsQuery = Product::where('tenant_id', $tenantId);

        $stats = [
            'on_hand' => (clone $productsQuery)->sum('inventory'),
            'available' => (clone $productsQuery)->get()->sum('available_stock'),
            'reserved' => (clone $productsQuery)->sum('reserved_stock'),
            'damaged' => (clone $productsQuery)->sum('damaged_stock'),
            'low' => (clone $productsQuery)->whereColumn('inventory', '<=', 'minimum_stock_level')->where('inventory', '>', 0)->count(),
            'out' => (clone $productsQuery)->where('inventory', 0)->count(),
        ];

        $movements = StockMovement::with('product')
            ->where('tenant_id', $tenantId)
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = (string) $request->string('type');

                if (in_array($type, ['purchase', 'sale', 'sales_return', 'purchase_return', 'adjustment'], true)) {
                    $query->where('type', $type);
                }
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('notes', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends(array_merge($request->except('page'), ['per_page' => $perPage]));

        return view('stock.index', [
            'products' => Product::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'lowStockProducts' => Product::where('tenant_id', $tenantId)
                ->whereColumn('inventory', '<=', 'minimum_stock_level')
                ->orderBy('inventory')
                ->take(8)
                ->get(),
            'movements' => $movements,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): View
    {
        return view('stock.create', [
            'products' => Product::where('tenant_id', $request->user()->tenant_id)
                ->orderBy('name')
                ->take(10)
                ->get(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $products = Product::where('tenant_id', $request->user()->tenant_id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => $product->name.' - '.($product->sku ?: 'SKU-'.$product->id).' - Stock '.$product->inventory,
                'purchase_price' => $product->purchase_price,
                'selling_price' => $product->price,
            ])->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
                'has_more' => $products->hasMorePages(),
            ],
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        // Older stock clients supplied only a percentage with their purchase price.
        if (! $request->has('profit_type') && ($request->filled('purchase_price') || $request->filled('profit_percentage'))) {
            $request->merge(['profit_type' => 'percentage']);
        }
        $data = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'adjustment' => ['required', 'integer', 'min:-999999', 'max:999999', 'not_in:0'],
            'stock_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'purchase_price' => ['required_with:profit_type', 'nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'profit_type' => ['required_with:profit_amount,profit_percentage,purchase_price', 'nullable', 'in:percentage,amount'],
            'profit_percentage' => ['required_if:profit_type,percentage', 'prohibited_unless:profit_type,percentage', 'nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'profit_amount' => ['required_if:profit_type,amount', 'prohibited_unless:profit_type,amount', 'nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'product_id.required' => 'Select the product to adjust.',
            'product_id.exists' => 'Select a valid product from your inventory.',
            'adjustment.required' => 'Enter the stock adjustment.',
            'adjustment.integer' => 'Stock adjustment must be a whole number.',
            'adjustment.not_in' => 'Stock adjustment cannot be zero.',
            'stock_date.required' => 'Select the stock date.',
            'stock_date.date_format' => 'Select a valid stock date.',
            'stock_date.before_or_equal' => 'Stock date cannot be in the future.',
            'purchase_price.numeric' => 'Purchase price must be a valid number.',
            'purchase_price.min' => 'Purchase price cannot be negative.',
            'purchase_price.max' => 'Purchase price is too high.',
            'profit_percentage.numeric' => 'Profit percentage must be a valid number.',
            'profit_percentage.min' => 'Profit percentage cannot be negative.',
            'profit_percentage.max' => 'Profit percentage is too high.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ]);

        $summary = DB::transaction(function () use ($data, $request, $tenantId) {
            $product = Product::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($data['product_id']);
            $nextStock = $product->inventory + $data['adjustment'];

            if ($nextStock < 0) {
                throw ValidationException::withMessages([
                    'adjustment' => 'Adjustment would make stock negative. Current stock is '.$product->inventory.'.',
                ]);
            }

            $oldStock = $product->inventory;
            $product->inventory = $nextStock;

            if ($data['adjustment'] > 0 && isset($data['purchase_price'])) {
                $purchasePrice = (float) $data['purchase_price'];

                $product->purchase_price = $oldStock > 0
                    ? (($product->purchase_price * $oldStock) + ($purchasePrice * $data['adjustment'])) / ($oldStock + $data['adjustment'])
                    : $purchasePrice;

                $profit = ($data['profit_type'] ?? 'percentage') === 'amount'
                    ? (float) $data['profit_amount']
                    : $purchasePrice * ((float) $data['profit_percentage'] / 100);
                $sellingPrice = round($purchasePrice + $profit, 2);
                if ($sellingPrice > 99999999.99) {
                    throw ValidationException::withMessages(['purchase_price' => 'Selling price after profit cannot exceed 99,999,999.99.']);
                }
                $product->price = $sellingPrice;
            }

            $product->save();
            StockNotifier::sync($product);

            $notes = ($data['notes'] ?? '') ?: 'Manual stock adjustment.';

            if ($data['adjustment'] > 0 && isset($data['purchase_price'])) {
                $notes = trim($notes.' Purchase price: '.number_format((float) $data['purchase_price'], 2).'.');

                if (isset($data['profit_percentage'])) {
                    $notes = trim($notes.' Profit: '.number_format((float) $data['profit_percentage'], 2).'%. Selling price: '.number_format((float) $product->price, 2).'.');
                } elseif (isset($data['profit_amount'])) {
                    $notes = trim($notes.' Profit amount per unit: '.number_format((float) $data['profit_amount'], 2).'. Selling price: '.number_format((float) $product->price, 2).'.');
                }
            }

            $movementDate = Carbon::createFromFormat('Y-m-d', $data['stock_date'])
                ->setTimeFrom(now());

            StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $data['adjustment'],
                'stock_after' => $product->inventory,
                'notes' => $notes,
                'user_id' => $request->user()->id,
                'created_at' => $movementDate,
                'updated_at' => $movementDate,
            ]);

            return [
                'product_name' => $product->name,
                'adjustment' => $data['adjustment'],
                'stock_after' => $product->inventory,
            ];
        });

        ActivityNotifier::notify(
            $tenantId,
            'stock_adjusted',
            'Stock adjusted',
            $request->user()->name.' adjusted '.$summary['product_name'].' by '.$summary['adjustment'].' units. Current stock: '.$summary['stock_after'].'.'
        );

        return redirect()
            ->route('stock.index')
            ->with('status', 'Stock adjusted for '.$summary['product_name'].'.');
    }
}
