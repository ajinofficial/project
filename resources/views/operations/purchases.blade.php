@extends('layouts.admin', ['title' => 'Purchases'])

@section('content')
    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Stock in</p><h2>Receive purchase</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('purchases.store') }}">
                @csrf
                <label><span>Supplier</span><select name="supplier_id"><option value="">No supplier</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></label>
                <label><span>Product</span><select name="product_id" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku ?: 'SKU-'.$product->id }})</option>@endforeach</select></label>
                <div class="field-grid">
                    <label><span>Quantity</span><input type="number" name="quantity" min="1" value="1" required></label>
                    <label><span>Purchase price</span><input type="number" name="purchase_price" min="0" step="0.01" required></label>
                </div>
                <label><span>Tax %</span><input type="number" name="tax_percentage" min="0" max="99.99" step="0.01" value="{{ auth()->user()->tenant->default_tax_percentage }}"></label>
                <button type="submit">Receive stock</button>
            </form>
        </article>
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Purchase history</p><h2>Recent purchase orders</h2></div></div>
            <div class="table-wrap"><table class="admin-table"><thead><tr><th>PO</th><th>Supplier</th><th>Items</th><th>Total</th></tr></thead><tbody>
                @forelse ($orders as $order)
                    <tr><td>{{ $order->order_number }}</td><td>{{ $order->supplier->name ?? '-' }}</td><td>@foreach ($order->items as $item)<strong>{{ $item->product->name ?? 'Product' }} x {{ $item->quantity }}</strong>@endforeach</td><td>₹{{ number_format($order->total_amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="4">No purchase orders yet.</td></tr>
                @endforelse
            </tbody></table></div>
            {{ $orders->links() }}
        </article>
    </section>
@endsection
