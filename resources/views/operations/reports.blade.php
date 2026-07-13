@extends('layouts.admin', ['title' => 'Reports'])

@section('content')
    @php
        $topSoldMax = max(1, $topProducts->max('sold') ?? 1);
        $lowStockCount = $lowStockTotal ?? $lowStock->count();
        $movementCount = $movementTotal ?? $movements->count();
        $dateLabel = $startDate->isSameDay($endDate)
            ? $startDate->format('d M Y')
            : $startDate->format('d M Y').' - '.$endDate->format('d M Y');
        $today = now()->startOfDay();
        $rangePresets = [
            'Today' => [$today->copy(), $today->copy()],
            'Last 7 days' => [$today->copy()->subDays(6), $today->copy()],
            'Last 30 days' => [$today->copy()->subDays(29), $today->copy()],
            'This month' => [$today->copy()->startOfMonth(), $today->copy()],
        ];
        $chartMaximum = max(1, $chartPoints->max(fn ($point) => max($point['sales'], $point['purchases'])) ?? 1);
        $chartWidth = 720;
        $chartHeight = 190;
        $chartPlotTop = 16;
        $chartPlotBottom = 156;
        $chartPlotHeight = $chartPlotBottom - $chartPlotTop;
        $chartStep = $chartPoints->count() > 1 ? ($chartWidth - 40) / ($chartPoints->count() - 1) : 0;
        $salesLinePoints = $chartPoints->map(fn ($point, $index) => ($chartPoints->count() === 1 ? $chartWidth / 2 : 20 + ($index * $chartStep)).','.($chartPlotBottom - (($point['sales'] / $chartMaximum) * $chartPlotHeight)))->implode(' ');
        $purchaseLinePoints = $chartPoints->map(fn ($point, $index) => ($chartPoints->count() === 1 ? $chartWidth / 2 : 20 + ($index * $chartStep)).','.($chartPlotBottom - (($point['purchases'] / $chartMaximum) * $chartPlotHeight)))->implode(' ');
    @endphp

    <section class="reports-page">
        <header class="reports-hero">
            <div>
                <p class="eyebrow">Business reports</p>
                <h1>Sales, margin, and stock health</h1>
                <p>Track revenue, purchase spend, profit, fast movers, stale inventory, and stock movement for {{ $dateLabel }}.</p>
            </div>
            <div class="reports-hero-actions">
                <button class="ghost-button reports-print-button" type="button" data-print-report>Print report</button>
                <a class="primary-link" href="{{ route('sales.index') }}">Create invoice</a>
                <a class="ghost-button" href="{{ route('products.index', ['stock' => 'low']) }}">Resolve low stock</a>
            </div>
        </header>

        <section class="reports-filter-card">
            <div class="reports-filter-head">
                <div>
                    <strong>Report period</strong>
                    <span>{{ $dateLabel }}</span>
                </div>
                <nav class="reports-range-presets" aria-label="Quick report ranges">
                    @foreach ($rangePresets as $label => [$presetStart, $presetEnd])
                        <a
                            href="{{ route('reports.index', ['start_date' => $presetStart->toDateString(), 'end_date' => $presetEnd->toDateString(), 'movement_type' => $movementType ?: null]) }}"
                            @class(['is-active' => $startDate->isSameDay($presetStart) && $endDate->isSameDay($presetEnd)])
                        >{{ $label }}</a>
                    @endforeach
                </nav>
            </div>
            <form class="reports-filter-form" method="GET" action="{{ route('reports.index') }}" data-reports-filter-form>
                <label>
                    <span>Start date</span>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" max="{{ $today->toDateString() }}" required data-date-picker>
                </label>
                <label>
                    <span>End date</span>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" max="{{ $today->toDateString() }}" required data-date-picker>
                </label>
                <label>
                    <span>Movement</span>
                    <select name="movement_type" data-movement-filter>
                        <option value="">All movements</option>
                        <option value="purchase" @selected($movementType === 'purchase')>Purchase</option>
                        <option value="sale" @selected($movementType === 'sale')>Sale</option>
                        <option value="sales_return" @selected($movementType === 'sales_return')>Sales return</option>
                        <option value="purchase_return" @selected($movementType === 'purchase_return')>Purchase return</option>
                        <option value="adjustment" @selected($movementType === 'adjustment')>Adjustment</option>
                    </select>
                </label>
                <button class="filter-button" type="submit">Apply</button>
                <a class="product-clear-filter" href="{{ route('reports.index') }}">Reset</a>
            </form>
            @error('end_date') <small>{{ $message }}</small> @enderror
            @error('start_date') <small>{{ $message }}</small> @enderror
        </section>

        <section class="reports-kpi-grid">
            <article class="reports-kpi is-blue">
                <div class="reports-kpi-head"><span>Sales revenue</span><i aria-hidden="true">₹</i></div>
                <strong>&#8377;{{ number_format($rangeRevenue, 0) }}</strong>
                <small>{{ number_format($rangeOrders) }} invoices · &#8377;{{ number_format($averageOrderValue, 0) }} average</small>
            </article>
            <article class="reports-kpi is-green">
                <div class="reports-kpi-head"><span>Gross profit</span><i aria-hidden="true">↗</i></div>
                <strong>&#8377;{{ number_format($profit, 0) }}</strong>
                <small>{{ number_format($profitMargin, 1) }}% margin</small>
            </article>
            <article class="reports-kpi is-amber">
                <div class="reports-kpi-head"><span>Purchase spend</span><i aria-hidden="true">↓</i></div>
                <strong>&#8377;{{ number_format($rangePurchases, 0) }}</strong>
                <small>Stock received in range</small>
            </article>
            <article class="reports-kpi is-red">
                <div class="reports-kpi-head"><span>Returns</span><i aria-hidden="true">↩</i></div>
                <strong>{{ number_format($rangeReturns) }}</strong>
                <small>{{ number_format($unitsSold) }} units sold in range</small>
            </article>
        </section>

        <section class="reports-chart-grid">
            <article class="reports-card reports-trend-card">
                <div class="section-title reports-chart-head">
                    <div>
                        <p class="eyebrow">Financial trend</p>
                        <h2>Sales vs purchases</h2>
                    </div>
                    <div class="reports-chart-legend" aria-label="Chart legend">
                        <span class="is-sales">Sales</span>
                        <span class="is-purchases">Purchases</span>
                    </div>
                </div>

                <div class="reports-line-chart">
                    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Sales and purchase values across the selected report period">
                        @foreach ([0, 0.5, 1] as $gridRatio)
                            @php $gridY = $chartPlotBottom - ($gridRatio * $chartPlotHeight); @endphp
                            <line class="reports-chart-gridline" x1="20" y1="{{ $gridY }}" x2="700" y2="{{ $gridY }}" />
                        @endforeach
                        <polyline class="reports-chart-line is-purchases" points="{{ $purchaseLinePoints }}" />
                        <polyline class="reports-chart-line is-sales" points="{{ $salesLinePoints }}" />
                        @foreach ($chartPoints as $index => $point)
                            @php
                                $pointX = $chartPoints->count() === 1 ? $chartWidth / 2 : 20 + ($index * $chartStep);
                                $salesY = $chartPlotBottom - (($point['sales'] / $chartMaximum) * $chartPlotHeight);
                                $purchasesY = $chartPlotBottom - (($point['purchases'] / $chartMaximum) * $chartPlotHeight);
                            @endphp
                            <circle class="reports-chart-point is-sales" cx="{{ $pointX }}" cy="{{ $salesY }}" r="4"><title>{{ $point['label'] }} sales: ₹{{ number_format($point['sales'], 2) }}</title></circle>
                            <circle class="reports-chart-point is-purchases" cx="{{ $pointX }}" cy="{{ $purchasesY }}" r="4"><title>{{ $point['label'] }} purchases: ₹{{ number_format($point['purchases'], 2) }}</title></circle>
                            <text class="reports-chart-label" x="{{ $pointX }}" y="178" text-anchor="middle">{{ $point['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
            </article>

            <article class="reports-card reports-bar-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Units moved</p>
                        <h2>Product bar chart</h2>
                    </div>
                </div>
                <div class="reports-bar-chart" role="img" aria-label="Units sold by top product">
                    @forelse ($topProducts as $item)
                        @php $barHeight = max(8, round(($item->sold / $topSoldMax) * 100)); @endphp
                        <div class="reports-bar-column">
                            <strong>{{ number_format($item->sold) }}</strong>
                            <div><i style="height: {{ $barHeight }}%"></i></div>
                            <span title="{{ $item->product->name ?? 'Product' }}">{{ $item->product->name ?? 'Product' }}</span>
                        </div>
                    @empty
                        <div class="empty-state tight-empty">No product sales to chart.</div>
                    @endforelse
                </div>
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
                            <b>{{ number_format($item->sold) }} sold <small>&#8377;{{ number_format($item->revenue, 0) }}</small></b>
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
                        <div class="empty-state tight-empty">No {{ $movementType ? str_replace('_', ' ', $movementType).' ' : '' }}movements found for this date range.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var printButton = document.querySelector('[data-print-report]');
            var movementFilter = document.querySelector('[data-movement-filter]');

            if (printButton) {
                printButton.addEventListener('click', function () {
                    window.print();
                });
            }

            if (movementFilter) {
                movementFilter.addEventListener('change', function () {
                    var form = movementFilter.closest('form');

                    if (form && typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    }
                });
            }
        });
    </script>
@endsection
