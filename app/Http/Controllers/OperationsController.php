<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesItem;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Support\ActivityNotifier;
use App\Support\StockNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function suppliers(Request $request): View
    {
        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $suppliers = Supplier::where('tenant_id', $request->user()->tenant_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_information', 'like', "%{$search}%")
                        ->orWhere('gst_number', 'like', "%{$search}%")
                        ->orWhere('payment_terms', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends(array_merge($request->except('page'), ['per_page' => $perPage]));

        return view('operations.suppliers', [
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'suppliers' => $suppliers,
        ]);
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_information' => ['required', 'string', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'payment_terms' => ['nullable', 'string', 'max:120'],
            'outstanding_balance' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Enter the supplier name.',
            'name.max' => 'Supplier name cannot exceed 255 characters.',
            'contact_information.required' => 'Enter the contact information.',
            'contact_information.max' => 'Contact information cannot exceed 255 characters.',
            'gst_number.max' => 'GST number cannot exceed 30 characters.',
            'payment_terms.max' => 'Payment terms cannot exceed 120 characters.',
            'outstanding_balance.required' => 'Enter the outstanding balance.',
            'outstanding_balance.numeric' => 'Outstanding balance must be a valid number.',
            'outstanding_balance.min' => 'Outstanding balance cannot be negative.',
        ]) + ['tenant_id' => $request->user()->tenant_id]);

        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'supplier_created',
            'Supplier saved',
            $request->user()->name.' saved supplier '.$supplier->name.'.'
        );

        return back()->with('status', 'Supplier saved.');
    }

    public function customers(Request $request): View
    {
        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $customers = Customer::where('tenant_id', $request->user()->tenant_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends(array_merge($request->except('page'), ['per_page' => $perPage]));

        return view('operations.customers', [
            'customers' => $customers,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $customer = Customer::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:30'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'outstanding_balance' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Enter the customer name.',
            'name.max' => 'Customer name cannot exceed 255 characters.',
            'mobile.required' => 'Enter the mobile number.',
            'mobile.max' => 'Mobile number cannot exceed 30 characters.',
            'credit_limit.required' => 'Enter the credit limit.',
            'credit_limit.numeric' => 'Credit limit must be a valid number.',
            'credit_limit.min' => 'Credit limit cannot be negative.',
            'outstanding_balance.required' => 'Enter the outstanding balance.',
            'outstanding_balance.numeric' => 'Outstanding balance must be a valid number.',
            'outstanding_balance.min' => 'Outstanding balance cannot be negative.',
        ]) + ['tenant_id' => $request->user()->tenant_id]);

        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'customer_created',
            'Customer saved',
            $request->user()->name.' saved customer '.$customer->name.'.'
        );

        return back()->with('status', 'Customer saved.');
    }

    public function purchases(Request $request): View
    {
        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $orders = PurchaseOrder::with('supplier', 'items.product')
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('items.product', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends(array_merge($request->except('page'), ['per_page' => $perPage]));

        return view('operations.purchases', [
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'suppliers' => Supplier::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->get(),
            'orders' => $orders,
        ]);
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        $data = $request->validate([
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId),
            ],
            'supplier_invoice_number' => [
                'required',
                'string',
                'max:120',
                Rule::unique('purchase_orders', 'supplier_invoice_number')->where('tenant_id', $tenantId),
            ],
            'bill_date' => ['required', 'date'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'total_amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ], [
            'supplier_id.exists' => 'Select a supplier from your business.',
            'supplier_invoice_number.required' => 'Enter the supplier invoice number.',
            'supplier_invoice_number.max' => 'Supplier invoice number cannot exceed 120 characters.',
            'supplier_invoice_number.unique' => 'This supplier invoice number is already recorded.',
            'bill_date.required' => 'Select the bill date.',
            'bill_date.date' => 'Bill date must be a valid date.',
            'tax_amount.numeric' => 'Tax amount must be a valid number.',
            'tax_amount.min' => 'Tax amount cannot be negative.',
            'tax_amount.max' => 'Tax amount is too high.',
            'total_amount.required' => 'Enter the total amount.',
            'total_amount.numeric' => 'Total amount must be a valid number.',
            'total_amount.min' => 'Total amount must be greater than zero.',
            'total_amount.max' => 'Total amount is too high.',
        ]);

        $order = PurchaseOrder::create([
            'tenant_id' => $tenantId,
            'supplier_id' => $data['supplier_id'] ?? null,
            'order_number' => 'PO-'.now()->format('YmdHis'),
            'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
            'status' => 'received',
            'tax_amount' => $data['tax_amount'] ?? 0,
            'total_amount' => $data['total_amount'],
            'received_at' => Carbon::parse($data['bill_date'])->startOfDay(),
        ]);

        ActivityNotifier::notify(
            $tenantId,
            'purchase_received',
            'Purchase bill recorded',
            $request->user()->name.' recorded purchase bill '.$order->order_number.' for '.$order->total_amount.'.'
        );

        return back()->with('status', 'Purchase bill recorded.');
    }

    public function sales(Request $request): View
    {
        $orders = SalesOrder::with('customer', 'items.product')
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('items.product', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->appends($request->only('search'));

        return view('operations.sales', [
            'products' => Product::where('tenant_id', $request->user()->tenant_id)->where('status', 'active')->orderBy('name')->get(),
            'customers' => Customer::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->get(),
            'orders' => $orders,
        ]);
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        $data = $request->validate([
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,upi,card,net_banking,credit'],
        ], [
            'customer_id.exists' => 'Select a customer from your business.',
            'items.required' => 'Add at least one product to the bill.',
            'items.min' => 'Add at least one product to the bill.',
            'items.*.product_id.required' => 'Select a product for every bill row.',
            'items.*.product_id.exists' => 'Select valid products from your inventory.',
            'items.*.quantity.required' => 'Enter quantity for every bill row.',
            'items.*.quantity.integer' => 'Quantity must be a whole number.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'paid_amount.required' => 'Enter the paid amount.',
            'paid_amount.numeric' => 'Paid amount must be a valid number.',
            'paid_amount.min' => 'Paid amount must be greater than zero.',
            'payment_method.required' => 'Select the payment method.',
            'payment_method.in' => 'Select a valid payment method.',
        ]);

        $data['items'] = collect($data['items'])
            ->groupBy('product_id')
            ->map(fn ($items, $productId) => [
                'product_id' => (int) $productId,
                'quantity' => (int) $items->sum('quantity'),
            ])
            ->values()
            ->all();

        $saleSummary = DB::transaction(function () use ($data, $request, $tenantId) {
            $productIds = collect($data['items'])->pluck('product_id')->all();
            $products = Product::where('tenant_id', $tenantId)
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $tax = 0;
            $totalQuantity = 0;

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Select valid products from your inventory.',
                    ]);
                }

                if ($product->available_stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => $product->name.' has only '.$product->available_stock.' units available.',
                    ]);
                }

                $lineSubtotal = $item['quantity'] * $product->price;
                $subtotal += $lineSubtotal;
                $tax += $lineSubtotal * ($product->tax_percentage / 100);
                $totalQuantity += $item['quantity'];
            }

            $total = $subtotal + $tax;

            $order = SalesOrder::create([
                'tenant_id' => $tenantId,
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => $request->user()->tenant->invoice_prefix.'-'.now()->format('YmdHis'),
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'paid_amount' => $data['paid_amount'],
                'payment_method' => $data['payment_method'],
            ]);

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);

                SalesItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'selling_price' => $product->price,
                    'tax_percentage' => $product->tax_percentage,
                ]);

                $product->inventory -= $item['quantity'];
                $product->save();
                StockNotifier::sync($product);

                $this->movement($request, $product, 'sale', -$item['quantity'], 'sales_orders', $order->id, 'Invoice generated.');
            }

            if ($order->customer_id && $order->paid_amount < $order->total_amount) {
                Customer::where('tenant_id', $tenantId)->where('id', $order->customer_id)
                    ->increment('outstanding_balance', $order->total_amount - $order->paid_amount);
            }

            return [
                'invoice_number' => $order->invoice_number,
                'item_count' => count($data['items']),
                'quantity' => $totalQuantity,
                'total' => $order->total_amount,
            ];
        });

        ActivityNotifier::notify(
            $tenantId,
            'sale_billed',
            'Sale billed',
            $request->user()->name.' billed '.$saleSummary['invoice_number'].' for '.$saleSummary['quantity'].' units across '.$saleSummary['item_count'].' product(s).'
        );

        return back()->with('status', 'Sale billed and stock reduced.');
    }

    public function returns(Request $request): View
    {
        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $movements = StockMovement::with('product')
            ->where('tenant_id', $request->user()->tenant_id)
            ->whereIn('type', ['sales_return', 'purchase_return', 'damaged_return'])
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = (string) $request->string('type');

                if (in_array($type, ['sales_return', 'purchase_return', 'damaged_return'], true)) {
                    $query->where('type', $type);
                }
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('notes', 'like', "%{$search}%")
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

        return view('operations.returns', [
            'products' => Product::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->get(),
            'movements' => $movements,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function storeReturn(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        $data = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'return_type' => ['required', 'in:sales_return,purchase_return,damaged_return'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'product_id.required' => 'Select a product for the return.',
            'product_id.exists' => 'Select a valid product from your inventory.',
            'quantity.required' => 'Enter the return quantity.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 999999.',
            'return_type.required' => 'Select the return type.',
            'return_type.in' => 'Select a valid return type.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ]);

        $returnSummary = DB::transaction(function () use ($data, $request, $tenantId) {
            $product = Product::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($data['product_id']);
            $delta = $data['return_type'] === 'purchase_return' ? -$data['quantity'] : $data['quantity'];

            if ($product->inventory + $delta < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Return quantity is greater than available stock.',
                ]);
            }

            $product->inventory += $delta;
            $product->returned_stock += in_array($data['return_type'], ['sales_return', 'damaged_return'], true) ? $data['quantity'] : 0;
            $product->damaged_stock += $data['return_type'] === 'damaged_return' ? $data['quantity'] : 0;
            $product->save();
            StockNotifier::sync($product);

            $this->movement($request, $product, $data['return_type'], $delta, null, null, $data['notes'] ?? null);

            return [
                'product_name' => $product->name,
                'quantity' => $data['quantity'],
                'return_type' => $data['return_type'],
            ];
        });

        ActivityNotifier::notify(
            $tenantId,
            'return_processed',
            'Return processed',
            $request->user()->name.' processed a '.str_replace('_', ' ', $returnSummary['return_type']).' for '.$returnSummary['quantity'].' units of '.$returnSummary['product_name'].'.'
        );

        return back()->with('status', 'Return processed.');
    }

    public function reports(Request $request): View
    {
        $tenantId = $request->user()->tenant_id;
        $filters = $request->validate([
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
            'movement_type' => ['nullable', Rule::in(['purchase', 'sale', 'sales_return', 'purchase_return', 'adjustment'])],
        ]);

        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : now()->startOfMonth();
        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->endOfDay();

        $salesQuery = SalesOrder::where('tenant_id', $tenantId)->whereBetween('created_at', [$startDate, $endDate]);
        $purchaseQuery = PurchaseOrder::where('tenant_id', $tenantId)->whereBetween('received_at', [$startDate, $endDate]);
        $returnQuery = StockMovement::where('tenant_id', $tenantId)->whereIn('type', ['sales_return', 'purchase_return'])->whereBetween('created_at', [$startDate, $endDate]);
        $rangeRevenue = (float) (clone $salesQuery)->sum('total_amount');
        $rangeOrders = (clone $salesQuery)->count();
        $profit = (float) SalesItem::query()
            ->join('sales_orders', 'sales_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_items.product_id', '=', 'products.id')
            ->where('sales_orders.tenant_id', $tenantId)
            ->whereBetween('sales_orders.created_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM((sales_items.selling_price - products.purchase_price) * sales_items.quantity), 0) as value')
            ->value('value');
        $rangeExpenses = (float) Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');
        $netProfit = $profit - $rangeExpenses;
        $unitsSold = (int) SalesItem::query()
            ->join('sales_orders', 'sales_items.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_orders.tenant_id', $tenantId)
            ->whereBetween('sales_orders.created_at', [$startDate, $endDate])
            ->sum('sales_items.quantity');
        $dailySales = (clone $salesQuery)
            ->selectRaw('DATE(created_at) as report_date, SUM(total_amount) as total')
            ->groupBy('report_date')
            ->pluck('total', 'report_date');
        $dailyPurchases = (clone $purchaseQuery)
            ->selectRaw('DATE(received_at) as report_date, SUM(total_amount) as total')
            ->groupBy('report_date')
            ->pluck('total', 'report_date');
        $periodDays = (int) $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1;
        $bucketDays = max(1, (int) ceil($periodDays / 12));
        $chartPoints = collect();
        $bucketStart = $startDate->copy()->startOfDay();

        while ($bucketStart->lte($endDate)) {
            $bucketEnd = $bucketStart->copy()->addDays($bucketDays - 1)->endOfDay();

            if ($bucketEnd->gt($endDate)) {
                $bucketEnd = $endDate->copy();
            }

            $from = $bucketStart->toDateString();
            $to = $bucketEnd->toDateString();
            $chartPoints->push([
                'label' => $bucketDays === 1 ? $bucketStart->format('d M') : $bucketStart->format('d M').'–'.$bucketEnd->format('d M'),
                'sales' => (float) $dailySales->filter(fn ($value, $date) => $date >= $from && $date <= $to)->sum(),
                'purchases' => (float) $dailyPurchases->filter(fn ($value, $date) => $date >= $from && $date <= $to)->sum(),
            ]);
            $bucketStart = $bucketEnd->copy()->addSecond()->startOfDay();
        }
        $movementsQuery = StockMovement::with('product')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($filters['movement_type'] ?? null, fn ($query, $type) => $query->where('type', $type));

        $soldProductIds = SalesItem::query()
            ->join('products as sold_products', 'sales_items.product_id', '=', 'sold_products.id')
            ->where('sold_products.tenant_id', $tenantId)
            ->select('sales_items.product_id');

        return view('operations.reports', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'movementType' => $filters['movement_type'] ?? '',
            'rangeRevenue' => $rangeRevenue,
            'rangeOrders' => $rangeOrders,
            'averageOrderValue' => $rangeOrders > 0 ? $rangeRevenue / $rangeOrders : 0,
            'profitMargin' => $rangeRevenue > 0 ? ($profit / $rangeRevenue) * 100 : 0,
            'unitsSold' => $unitsSold,
            'chartPoints' => $chartPoints,
            'rangePurchases' => (clone $purchaseQuery)->sum('total_amount'),
            'rangeReturns' => (clone $returnQuery)->count(),
            'profit' => $profit,
            'rangeExpenses' => $rangeExpenses,
            'netProfit' => $netProfit,
            'topProducts' => SalesItem::with('product')
                ->join('sales_orders', 'sales_items.sales_order_id', '=', 'sales_orders.id')
                ->where('sales_orders.tenant_id', $tenantId)
                ->whereBetween('sales_orders.created_at', [$startDate, $endDate])
                ->selectRaw('sales_items.product_id, SUM(sales_items.quantity) as sold, SUM(sales_items.quantity * sales_items.selling_price) as revenue')
                ->groupBy('sales_items.product_id')
                ->orderByDesc('sold')
                ->take(5)
                ->get(),
            'deadStock' => Product::where('tenant_id', $tenantId)
                ->whereNotIn('id', $soldProductIds)
                ->orderByDesc('inventory')
                ->take(8)
                ->get(),
            'lowStock' => Product::where('tenant_id', $tenantId)->whereColumn('inventory', '<=', 'minimum_stock_level')->orderBy('inventory')->take(8)->get(),
            'lowStockTotal' => Product::where('tenant_id', $tenantId)->whereColumn('inventory', '<=', 'minimum_stock_level')->count(),
            'movementTotal' => StockMovement::where('tenant_id', $tenantId)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'movements' => $movementsQuery->latest()->take(12)->get(),
        ]);
    }

    private function movement(Request $request, Product $product, string $type, int $quantity, ?string $referenceType, ?int $referenceId, ?string $notes): void
    {
        StockMovement::create([
            'tenant_id' => $request->user()->tenant_id,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_after' => $product->inventory,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'user_id' => $request->user()->id,
        ]);
    }
}
