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
use App\Support\StockNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function suppliers(Request $request): View
    {
        return view('operations.suppliers', [
            'suppliers' => Supplier::where('tenant_id', $request->user()->tenant_id)->latest()->paginate(12),
        ]);
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        Supplier::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_information' => ['nullable', 'string', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'payment_terms' => ['nullable', 'string', 'max:120'],
            'outstanding_balance' => ['nullable', 'numeric', 'min:0'],
        ]) + ['tenant_id' => $request->user()->tenant_id]);

        return back()->with('status', 'Supplier saved.');
    }

    public function customers(Request $request): View
    {
        return view('operations.customers', [
            'customers' => Customer::where('tenant_id', $request->user()->tenant_id)->latest()->paginate(12),
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        Customer::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'outstanding_balance' => ['nullable', 'numeric', 'min:0'],
        ]) + ['tenant_id' => $request->user()->tenant_id]);

        return back()->with('status', 'Customer saved.');
    }

    public function purchases(Request $request): View
    {
        return view('operations.purchases', [
            'products' => Product::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->get(),
            'suppliers' => Supplier::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->get(),
            'orders' => PurchaseOrder::with('supplier', 'items.product')->where('tenant_id', $request->user()->tenant_id)->latest()->paginate(10),
        ]);
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
        ]);

        DB::transaction(function () use ($data, $request, $tenantId) {
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
        });

        return back()->with('status', 'Purchase received and inventory updated.');
    }

    public function sales(Request $request): View
    {
        return view('operations.sales', [
            'products' => Product::where('tenant_id', $request->user()->tenant_id)->where('status', 'active')->orderBy('name')->get(),
            'customers' => Customer::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->get(),
            'orders' => SalesOrder::with('customer', 'items.product')->where('tenant_id', $request->user()->tenant_id)->latest()->paginate(10),
        ]);
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,upi,card,net_banking,credit'],
        ]);

        DB::transaction(function () use ($data, $request, $tenantId) {
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
                'paid_amount' => $data['paid_amount'] ?? $total,
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
        });

        return back()->with('status', 'Sale billed and stock reduced.');
    }

    public function returns(Request $request): View
    {
        return view('operations.returns', [
            'products' => Product::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->get(),
            'movements' => StockMovement::with('product')->where('tenant_id', $request->user()->tenant_id)->whereIn('type', ['sales_return', 'purchase_return'])->latest()->take(20)->get(),
        ]);
    }

    public function storeReturn(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'return_type' => ['required', 'in:sales_return,purchase_return'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $request, $tenantId) {
            $product = Product::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($data['product_id']);
            $delta = $data['return_type'] === 'sales_return' ? $data['quantity'] : -$data['quantity'];
            abort_if($product->inventory + $delta < 0, 422, 'Return quantity is greater than available stock.');

            $product->inventory += $delta;
            $product->returned_stock += $data['return_type'] === 'sales_return' ? $data['quantity'] : 0;
            $product->save();
            StockNotifier::sync($product);

            $this->movement($request, $product, $data['return_type'], $delta, null, null, $data['notes'] ?? null);
        });

        return back()->with('status', 'Return processed.');
    }

    public function reports(Request $request): View
    {
        $tenantId = $request->user()->tenant_id;

        return view('operations.reports', [
            'todaySales' => SalesOrder::where('tenant_id', $tenantId)->whereDate('created_at', today())->sum('total_amount'),
            'monthlyRevenue' => SalesOrder::where('tenant_id', $tenantId)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount'),
            'profit' => SalesItem::whereHas('product', fn ($query) => $query->where('tenant_id', $tenantId))
                ->join('products', 'sales_items.product_id', '=', 'products.id')
                ->selectRaw('COALESCE(SUM((sales_items.selling_price - products.purchase_price) * sales_items.quantity), 0) as value')
                ->value('value'),
            'topProducts' => SalesItem::with('product')->whereHas('product', fn ($query) => $query->where('tenant_id', $tenantId))
                ->selectRaw('product_id, SUM(quantity) as sold')->groupBy('product_id')->orderByDesc('sold')->take(5)->get(),
            'deadStock' => Product::where('tenant_id', $tenantId)
                ->whereNotIn('id', SalesItem::select('product_id'))
                ->orderByDesc('inventory')
                ->take(8)
                ->get(),
            'lowStock' => Product::where('tenant_id', $tenantId)->whereColumn('inventory', '<=', 'minimum_stock_level')->orderBy('inventory')->take(8)->get(),
            'movements' => StockMovement::with('product')->where('tenant_id', $tenantId)->latest()->take(12)->get(),
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
