<section class="product-workspace product-overview">
    <header class="product-page-head">
        <div>
            <p class="eyebrow">{{ $title }}</p>
            <h1>{{ $title }}</h1>
            <span>{{ $description }}</span>
        </div>
        <div class="product-head-actions">
            <a class="primary-link" href="#{{ $formId }}">{{ $action }}</a>
        </div>
    </header>
    <section class="product-stat-grid" aria-label="{{ $title }}">
        @foreach ($overviewCards as $card)
            <div class="product-stat">
                <span>{{ $card['label'] }}</span>
                <strong>{{ $card['currency'] ?? false ? '₹' : '' }}{{ number_format($card['value'], $card['currency'] ?? false ? 2 : 0) }}</strong>
                <small>{{ $card['hint'] }}</small>
            </div>
        @endforeach
    </section>
</section>
