@extends('layouts.admin', ['title' => 'Sales'])

@section('content')
    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Billing</p><h2>Create invoice</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('sales.store') }}">
                @csrf
                <label><span>Customer</span><select name="customer_id"><option value="">Walk-in customer</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></label>
                <label><span>Search product / SKU / barcode</span><select name="product_id" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} - {{ $product->sku ?: $product->barcode }} - Stock {{ $product->available_stock }}</option>@endforeach</select></label>
                <div class="field-grid">
                    <label><span>Quantity</span><input type="number" name="quantity" min="1" value="1" required></label>
                    <label><span>Paid amount</span><input type="number" name="paid_amount" min="0" step="0.01"></label>
                </div>
                <label><span>Payment method</span><select name="payment_method"><option value="cash">Cash</option><option value="upi">UPI</option><option value="card">Credit/Debit card</option><option value="net_banking">Net banking</option><option value="credit">Customer credit</option></select></label>
                <button type="submit">Generate invoice</button>
            </form>
        </article>
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Stock out</p><h2>Recent invoices</h2></div></div>
            <div class="table-wrap"><table class="admin-table"><thead><tr><th>Invoice</th><th>Customer</th><th>Items</th><th>Total</th><th>Paid</th></tr></thead><tbody>
                @forelse ($orders as $order)
                    <tr><td>{{ $order->invoice_number }}</td><td>{{ $order->customer->name ?? 'Walk-in' }}</td><td>@foreach ($order->items as $item)<strong>{{ $item->product->name ?? 'Product' }} x {{ $item->quantity }}</strong>@endforeach</td><td>₹{{ number_format($order->total_amount, 2) }}</td><td>₹{{ number_format($order->paid_amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="5">No invoices yet.</td></tr>
                @endforelse
            </tbody></table></div>
            {{ $orders->links() }}
        </article>
    </section>
@endsection
