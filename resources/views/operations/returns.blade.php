@extends('layouts.admin', ['title' => 'Returns'])

@section('content')
    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Returns</p><h2>Process return</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('returns.store') }}">
                @csrf
                <label><span>Product</span><select name="product_id" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} - Stock {{ $product->inventory }}</option>@endforeach</select></label>
                <div class="field-grid">
                    <label><span>Return type</span><select name="return_type"><option value="sales_return">Sales return: stock increases</option><option value="purchase_return">Purchase return: stock decreases</option></select></label>
                    <label><span>Quantity</span><input type="number" name="quantity" min="1" value="1" required></label>
                </div>
                <label><span>Notes</span><textarea name="notes" rows="3"></textarea></label>
                <button type="submit">Process return</button>
            </form>
        </article>
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Audit log</p><h2>Return movements</h2></div></div>
            @forelse ($movements as $movement)
                <div class="stock-row"><div><strong>{{ $movement->product->name ?? 'Product' }}</strong><span>{{ str_replace('_', ' ', ucfirst($movement->type)) }}: {{ $movement->quantity }}</span></div><span class="stock-count">{{ $movement->stock_after }}</span></div>
            @empty
                <div class="empty-state tight-empty">No returns processed yet.</div>
            @endforelse
        </article>
    </section>
@endsection
