@extends('layouts.admin', ['title' => 'Reports'])

@section('content')
    @php
        $topSoldMax = max(1, $topProducts->max('sold') ?? 1);
        $lowStockCount = $lowStockTotal ?? $lowStock->count();
        $movementCount = $movementTotal ?? $movements->count();
        $dateLabel = $startDate->isSameDay($endDate)
            ? $startDate->format('d M Y')
            : $startDate->format('d M Y').' - '.$endDate->format('d M Y');
    @endphp

    <section class="reports-page">
        <header class="reports-hero">
            <div>
                <p class="eyebrow">Business reports</p>
                <h1>Sales, margin, and stock health</h1>
                <p>Track revenue, purchase spend, profit, fast movers, stale inventory, and stock movement for {{ $dateLabel }}.</p>
            </div>
            <div class="reports-hero-actions">
                <a class="primary-link" href="{{ route('sales.index') }}">Create invoice</a>
                <a class="ghost-button" href="{{ route('products.index', ['stock' => 'low']) }}">Resolve low stock</a>
            </div>
        </header>

        <section class="reports-filter-card">
            <form class="reports-filter-form" method="GET" action="{{ route('reports.index') }}">
                <label>
                    <span>Start date</span>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}">
                </label>
                <label>
                    <span>End date</span>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}">
                </label>
                <button class="filter-button" type="submit">Apply</button>
                <a class="product-clear-filter" href="{{ route('reports.index') }}">Reset</a>
            </form>
            @error('end_date') <small>{{ $message }}</small> @enderror
            @error('start_date') <small>{{ $message }}</small> @enderror
        </section>

        <section class="reports-kpi-grid">
            <article class="reports-kpi is-blue">
                <span>Sales revenue</span>
                <strong>&#8377;{{ number_format($rangeRevenue, 0) }}</strong>
                <small>{{ number_format($rangeOrders) }} invoices</small>
            </article>
            <article class="reports-kpi is-green">
                <span>Gross profit</span>
                <strong>&#8377;{{ number_format($profit, 0) }}</strong>
                <small>Sales minus purchase cost</small>
            </article>
            <article class="reports-kpi is-amber">
                <span>Purchase spend</span>
                <strong>&#8377;{{ number_format($rangePurchases, 0) }}</strong>
                <small>Stock received in range</small>
            </article>
            <article class="reports-kpi is-red">
                <span>Returns</span>
                <strong>{{ number_format($rangeReturns) }}</strong>
                <small>Return movements in range</small>
            </article>
        </section>

        <section class="reports-grid">
            <article class="reports-card reports-wide">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Sales performance</p>
                        <h2>Top-selling products</h2>
                    </div>
                    <a href="{{ route('sales.index') }}">Billing</a>
                </div>

                <div class="reports-ranked-list">
                    @forelse ($topProducts as $item)
                        @php $width = max(8, round(($item->sold / $topSoldMax) * 100)); @endphp
                        <div class="reports-ranked-row">
                            <div>
                                <strong>{{ $item->product->name ?? 'Product' }}</strong>
                                <span>{{ $item->product->category ?? 'Uncategorized' }}</span>
                            </div>
                            <b>{{ number_format($item->sold) }} sold</b>
                            <i><span style="width: {{ $width }}%"></span></i>
                        </div>
                    @empty
                        <div class="empty-state tight-empty">No sales found for this date range.</div>
                    @endforelse
                </div>
            </article>

            <article class="reports-card reports-summary-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Snapshot</p>
                        <h2>Inventory signals</h2>
                    </div>
                </div>
                <div class="reports-signal-grid">
                    <a href="{{ route('products.index', ['stock' => 'low']) }}">
                        <strong>{{ number_format($lowStockCount) }}</strong>
                        <span>Low stock</span>
                    </a>
                    <a href="{{ route('products.index') }}">
                        <strong>{{ number_format($deadStock->count()) }}</strong>
                        <span>Dead stock</span>
                    </a>
                    <a href="{{ route('reports.index') }}">
                        <strong>{{ number_format($movementCount) }}</strong>
                        <span>Range movements</span>
                    </a>
                </div>
            </article>

            <article class="reports-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Inventory risk</p>
                        <h2>Low-stock products</h2>
                    </div>
                    <a href="{{ route('products.index', ['stock' => 'low']) }}">Open</a>
                </div>
                @forelse ($lowStock as $product)
                    <div class="reports-stock-row">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>Minimum {{ $product->minimum_stock_level }}</span>
                        </div>
                        <a class="stock-count {{ $product->inventory === 0 ? 'is-out' : '' }}" href="{{ route('purchases.index') }}">{{ $product->inventory }}</a>
                    </div>
                @empty
                    <div class="empty-state tight-empty">No low-stock products.</div>
                @endforelse
            </article>

            <article class="reports-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Slow movers</p>
                        <h2>Dead stock</h2>
                    </div>
                    <a href="{{ route('products.index') }}">Inventory</a>
                </div>
                @forelse ($deadStock as $product)
                    <div class="reports-stock-row">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>{{ number_format($product->inventory) }} units with no sales</span>
                        </div>
                        <span class="reports-muted-pill">{{ $product->category ?: 'Item' }}</span>
                    </div>
                @empty
                    <div class="empty-state tight-empty">No dead stock.</div>
                @endforelse
            </article>

            <article class="reports-card reports-wide">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Audit trail</p>
                        <h2>Purchase and stock trends</h2>
                    </div>
                    <a href="{{ route('purchases.index') }}">Purchases</a>
                </div>

                <div class="reports-timeline">
                    @forelse ($movements as $movement)
                        <div class="reports-timeline-row">
                            <span class="reports-timeline-dot"></span>
                            <div>
                                <strong>{{ $movement->product->name ?? 'Product' }}</strong>
                                <span>{{ ucfirst(str_replace('_', ' ', $movement->type)) }} {{ number_format($movement->quantity) }} units</span>
                                @if ($movement->notes)
                                    <small>{{ $movement->notes }}</small>
                                @endif
                            </div>
                            <time>{{ $movement->created_at->format('d M') }}</time>
                        </div>
                    @empty
                        <div class="empty-state tight-empty">No stock movements found for this date range.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </section>
@endsection
