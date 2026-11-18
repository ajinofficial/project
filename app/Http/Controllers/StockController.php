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

                if (in_array($type, ['purchase', 'sale', 'sales_return', 'purchase_return', 'adjustment', 'service'], true)) {
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
                                ->orWhere('translations->'.app()->getLocale().'->name', 'like', "%{$search}%")
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
                        ->orWhere('translations->'.app()->getLocale().'->name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => __('common.stock_label', ['name' => $product->localized('name'), 'sku' => $product->sku ?: 'SKU-'.$product->id, 'stock' => $product->inventory]),
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
            'product_id.required' => __('products.select_the_product_to_adjust_86a93c'),
            'product_id.exists' => __('validation.select_a_valid_product_from_your_inventory_fd8e3d'),
            'adjustment.required' => __('validation.enter_the_stock_adjustment_e9cefc'),
            'adjustment.integer' => __('validation.stock_adjustment_must_be_a_whole_number_8b3f86'),
            'adjustment.not_in' => __('validation.stock_adjustment_cannot_be_zero_380a46'),
            'stock_date.required' => __('products.select_the_stock_date_4602fc'),
            'stock_date.date_format' => __('validation.select_a_valid_stock_date_1739d7'),
            'stock_date.before_or_equal' => __('validation.stock_date_cannot_be_in_the_future_1d00aa'),
            'purchase_price.numeric' => __('validation.purchase_price_must_be_a_valid_number_a30e0c'),
            'purchase_price.min' => __('validation.purchase_price_cannot_be_negative_fd96c2'),
            'purchase_price.max' => __('validation.purchase_price_is_too_high_b14c73'),
            'profit_percentage.numeric' => __('validation.profit_percentage_must_be_a_valid_number_3913a6'),
            'profit_percentage.min' => __('validation.profit_percentage_cannot_be_negative_628e27'),
            'profit_percentage.max' => __('validation.profit_percentage_is_too_high_f9decd'),
            'notes.max' => __('validation.notes_cannot_exceed_1000_characters_d23ab7'),
        ]);

        $summary = DB::transaction(function () use ($data, $request, $tenantId) {
            $product = Product::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($data['product_id']);
            $nextStock = $product->inventory + $data['adjustment'];

            if ($nextStock < 0) {
                throw ValidationException::withMessages([
                    'adjustment' => __('common.negative_adjustment', ['stock' => $product->inventory]),
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
                    throw ValidationException::withMessages(['purchase_price' => __('validation.selling_price_after_profit_cannot_exceed_99_999_999_99_bc0c10')]);
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
            "Stock adjusted",
            $request->user()->name.' adjusted '.$summary['product_name'].' by '.$summary['adjustment'].' units. Current stock: '.$summary['stock_after'].'.',
            parameters: ['actor' => $request->user()->name, 'product' => $summary['product_name'], 'quantity' => $summary['adjustment'], 'stock' => $summary['stock_after']]
        );

        return redirect()
            ->route('stock.index')
            ->with('status', 'Stock adjusted for '.$summary['product_name'].'.');
    }
}
