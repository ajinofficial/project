<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseItem;
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
            'contact_information' => ['nullable', 'string', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'payment_terms' => ['nullable', 'string', 'max:120'],
            'outstanding_balance' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Enter the supplier name.',
            'name.max' => 'Supplier name cannot exceed 255 characters.',
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
            'mobile' => ['nullable', 'string', 'max:30'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'outstanding_balance' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Enter the customer name.',
            'name.max' => 'Customer name cannot exceed 255 characters.',
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
            'products' => Product::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->get(),
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
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
        ], [
            'supplier_id.exists' => 'Select a supplier from your business.',
            'product_id.required' => 'Select a product to receive.',
            'product_id.exists' => 'Select a valid product from your inventory.',
            'quantity.required' => 'Enter the received quantity.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 999999.',
            'purchase_price.required' => 'Enter the purchase price.',
            'purchase_price.numeric' => 'Purchase price must be a valid number.',
            'purchase_price.min' => 'Purchase price cannot be negative.',
            'purchase_price.max' => 'Purchase price is too high.',
            'tax_percentage.numeric' => 'Tax percentage must be a valid number.',
            'tax_percentage.min' => 'Tax percentage cannot be negative.',
            'tax_percentage.max' => 'Tax percentage cannot exceed 99.99.',
        ]);

        $purchaseSummary = DB::transaction(function () use ($data, $request, $tenantId) {
            $product = Product::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($data['product_id']);
            $total = $data['quantity'] * $data['purchase_price'];

            $order = PurchaseOrder::create([
                'tenant_id' => $tenantId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'order_number' => 'PO-'.now()->format('YmdHis'),
                'status' => 'received',
                'total_amount' => $total,
                'received_at' => now(),
            ]);

            PurchaseItem::create([
                'purchase_order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'purchase_price' => $data['purchase_price'],
                'tax_percentage' => $data['tax_percentage'] ?? $product->tax_percentage,
            ]);

            $oldStock = max(1, $product->inventory);
            $product->inventory += $data['quantity'];
            $product->purchase_price = (($product->purchase_price * $oldStock) + $total) / ($oldStock + $data['quantity']);
            $product->save();
            StockNotifier::sync($product);

            $this->movement($request, $product, 'purchase', $data['quantity'], 'purchase_orders', $order->id, 'Stock received from supplier.');

            return [
                'order_number' => $order->order_number,
                'product_name' => $product->name,
                'quantity' => $data['quantity'],
            ];
        });

        ActivityNotifier::notify(
            $tenantId,
            'purchase_received',
            'Purchase received',
            $request->user()->name.' received '.$purchaseSummary['quantity'].' units of '.$purchaseSummary['product_name'].' on '.$purchaseSummary['order_number'].'.'
        );

        return back()->with('status', 'Purchase received and inventory updated.');
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
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,upi,card,net_banking,credit'],
        ]);

        $saleSummary = DB::transaction(function () use ($data, $request, $tenantId) {
            $product = Product::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($data['product_id']);
            abort_if($product->available_stock < $data['quantity'], 422, 'Not enough stock available.');

            $subtotal = $data['quantity'] * $product->price;
            $tax = $subtotal * ($product->tax_percentage / 100);
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

            SalesItem::create([
                'sales_order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'selling_price' => $product->price,
                'tax_percentage' => $product->tax_percentage,
            ]);

            $product->inventory -= $data['quantity'];
            $product->save();
            StockNotifier::sync($product);

            if ($order->customer_id && $order->paid_amount < $order->total_amount) {
                Customer::where('tenant_id', $tenantId)->where('id', $order->customer_id)
                    ->increment('outstanding_balance', $order->total_amount - $order->paid_amount);
            }

            $this->movement($request, $product, 'sale', -$data['quantity'], 'sales_orders', $order->id, 'Invoice generated.');

            return [
                'invoice_number' => $order->invoice_number,
                'product_name' => $product->name,
                'quantity' => $data['quantity'],
                'total' => $order->total_amount,
            ];
        });

        ActivityNotifier::notify(
            $tenantId,
            'sale_billed',
            'Sale billed',
            $request->user()->name.' billed '.$saleSummary['invoice_number'].' for '.$saleSummary['quantity'].' units of '.$saleSummary['product_name'].'.'
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
            ->whereIn('type', ['sales_return', 'purchase_return'])
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = (string) $request->string('type');

                if (in_array($type, ['sales_return', 'purchase_return'], true)) {
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
            'return_type' => ['required', 'in:sales_return,purchase_return'],
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
            $delta = $data['return_type'] === 'sales_return' ? $data['quantity'] : -$data['quantity'];

            if ($product->inventory + $delta < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Return quantity is greater than available stock.',
                ]);
            }

            $product->inventory += $delta;
            $product->returned_stock += $data['return_type'] === 'sales_return' ? $data['quantity'] : 0;
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
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

        $soldProductIds = SalesItem::query()
            ->join('products as sold_products', 'sales_items.product_id', '=', 'sold_products.id')
            ->where('sold_products.tenant_id', $tenantId)
            ->select('sales_items.product_id');

        return view('operations.reports', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rangeRevenue' => (clone $salesQuery)->sum('total_amount'),
            'rangeOrders' => (clone $salesQuery)->count(),
            'rangePurchases' => (clone $purchaseQuery)->sum('total_amount'),
            'rangeReturns' => (clone $returnQuery)->count(),
            'profit' => SalesItem::query()
                ->join('sales_orders', 'sales_items.sales_order_id', '=', 'sales_orders.id')
                ->join('products', 'sales_items.product_id', '=', 'products.id')
                ->where('sales_orders.tenant_id', $tenantId)
                ->whereBetween('sales_orders.created_at', [$startDate, $endDate])
                ->selectRaw('COALESCE(SUM((sales_items.selling_price - products.purchase_price) * sales_items.quantity), 0) as value')
                ->value('value'),
            'topProducts' => SalesItem::with('product')
                ->join('sales_orders', 'sales_items.sales_order_id', '=', 'sales_orders.id')
                ->where('sales_orders.tenant_id', $tenantId)
                ->whereBetween('sales_orders.created_at', [$startDate, $endDate])
                ->selectRaw('sales_items.product_id, SUM(sales_items.quantity) as sold')
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
            'movements' => StockMovement::with('product')->where('tenant_id', $tenantId)->whereBetween('created_at', [$startDate, $endDate])->latest()->take(12)->get(),
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
