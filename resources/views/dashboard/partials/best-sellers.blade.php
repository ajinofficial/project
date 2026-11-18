@forelse ($topProducts as $item)
    <div class="stock-row">
        <div>
            <strong>{{ $item->product?->localized('name') ?? __('products.product_dd3b86') }}</strong>
            <span>{{ $item->product?->localized('category') ?? __('common.uncategorized_4ef7d7') }}</span>
        </div>
        @if ($metric === 'profit')
            <span class="best-seller-value">&#8377;{{ number_format($item->profit, 0) }} profit</span>
        @else
            <span class="best-seller-value">{{ __('common.sold_count', ['count' => number_format($item->sold)]) }}</span>
        @endif
    </div>
@empty
    <div class="empty-state tight-empty">{{ __('orders.no_sales_yet_ca637f') }}</div>
@endforelse
