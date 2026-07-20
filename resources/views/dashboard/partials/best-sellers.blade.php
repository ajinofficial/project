@forelse ($topProducts as $item)
    <div class="stock-row">
        <div>
            <strong>{{ $item->product->name ?? 'Product' }}</strong>
            <span>{{ $item->product->category ?? 'Uncategorized' }}</span>
        </div>
        @if ($metric === 'profit')
            <span class="best-seller-value">&#8377;{{ number_format($item->profit, 0) }} profit</span>
        @else
            <span class="best-seller-value">{{ number_format($item->sold) }} sold</span>
        @endif
    </div>
@empty
    <div class="empty-state tight-empty">No sales yet.</div>
@endforelse
