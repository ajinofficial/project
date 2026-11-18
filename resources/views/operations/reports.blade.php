@extends('layouts.admin', ['title' => __('dashboard.reports_88bc3f')])

@section('content')
    @php
        $topSoldMax = max(1, $topProducts->max('sold') ?? 1);
        $lowStockCount = $lowStockTotal ?? $lowStock->count();
        $movementCount = $movementTotal ?? $movements->count();
        $dateLabel = $startDate->isSameDay($endDate)
            ? $startDate->translatedFormat('d M Y')
            : $startDate->translatedFormat('d M Y').' - '.$endDate->translatedFormat('d M Y');
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
                <p class="eyebrow">{{ __('dashboard.business_reports_175d28') }}</p>
                <h1>{{ __('products.sales_margin_and_stock_health_f6a6d9') }}</h1>
                <p>{{ __('common.report_description', ['period' => $dateLabel]) }}</p>
            </div>
            <div class="reports-hero-actions">
                <button class="ghost-button reports-print-button" type="button" data-print-report>{{ __('dashboard.print_report_20dad9') }}</button>
                <a class="primary-link" href="{{ route('sales.index') }}">{{ __('orders.create_invoice_a0567c') }}</a>
                <a class="ghost-button" href="{{ route('products.index', ['stock' => 'low']) }}">{{ __('products.resolve_low_stock_fbc900') }}</a>
            </div>
        </header>

        <section class="reports-filter-card">
            <div class="reports-filter-head">
                <div>
                    <strong>{{ __('dashboard.report_period_a5d0a9') }}</strong>
                    <span>{{ $dateLabel }}</span>
                </div>
                <nav class="reports-range-presets" aria-label="{{ __('dashboard.quick_report_ranges_8e074b') }}">
                    @foreach ($rangePresets as $label => [$presetStart, $presetEnd])
                        <a
                            href="{{ route('reports.index', ['start_date' => $presetStart->toDateString(), 'end_date' => $presetEnd->toDateString(), 'movement_type' => $movementType ?: null]) }}"
                            @class(['is-active' => $startDate->isSameDay($presetStart) && $endDate->isSameDay($presetEnd)])
                        >{{ __($label) }}</a>
                    @endforeach
                </nav>
            </div>
            <form class="reports-filter-form" method="GET" action="{{ route('reports.index') }}" data-reports-filter-form>
                <label>
                    <span>{{ __('common.start_date_ff99f5') }}</span>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" max="{{ $today->toDateString() }}" required data-report-filter>
                </label>
                <label>
                    <span>{{ __('common.end_date_89d10c') }}</span>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" min="{{ $startDate->toDateString() }}" max="{{ $today->toDateString() }}" required data-report-filter>
                </label>
                <label>
                    <span>{{ __('common.movement_dc8127') }}</span>
                    <select name="movement_type" data-report-filter data-movement-filter>
                        <option value="">{{ __('common.all_movements_a24e9d') }}</option>
                        <option value="purchase" @selected($movementType === 'purchase')>{{ __('orders.purchase_160f06') }}</option>
                        <option value="sale" @selected($movementType === 'sale')>{{ __('common.sale_0028d7') }}</option>
                        <option value="sales_return" @selected($movementType === 'sales_return')>{{ __('orders.sales_return_81894c') }}</option>
                        <option value="purchase_return" @selected($movementType === 'purchase_return')>{{ __('orders.purchase_return_1c337e') }}</option>
                        <option value="adjustment" @selected($movementType === 'adjustment')>{{ __('common.adjustment_7f0496') }}</option>
                    </select>
                </label>
                <!-- <button class="filter-button" type="submit">{{ __('common.apply_cfea41') }}</button> -->
                <a class="product-clear-filter" href="{{ route('reports.index') }}">{{ __('common.reset_44c57a') }}</a>
            </form>
            @error('end_date') <small>{{ $message }}</small> @enderror
            @error('start_date') <small>{{ $message }}</small> @enderror
        </section>

        <section class="reports-kpi-grid">
            <article class="reports-kpi is-blue">
                <div class="reports-kpi-head"><span>{{ __('orders.sales_revenue_a84272') }}</span><i aria-hidden="true">₹</i></div>
                <strong>&#8377;{{ number_format($rangeRevenue, 0) }}</strong>
                <small>{{ number_format($rangeOrders) }} invoices · &#8377;{{ number_format($averageOrderValue, 0) }} average</small>
            </article>
            <article class="reports-kpi is-green">
                <div class="reports-kpi-head"><span>{{ __('products.gross_profit_011fa1') }}</span><i aria-hidden="true">↗</i></div>
                <strong>&#8377;{{ number_format($profit, 0) }}</strong>
                <small>{{ __('common.margin_percent', ['value' => number_format($profitMargin, 1)]) }}</small>
            </article>
            <article class="reports-kpi is-amber">
                <div class="reports-kpi-head"><span>{{ __('orders.purchase_spend_a97455') }}</span><i aria-hidden="true">↓</i></div>
                <strong>&#8377;{{ number_format($rangePurchases, 0) }}</strong>
                <small>{{ __('products.stock_received_in_range_a8d5c3') }}</small>
            </article>
            <article class="reports-kpi {{ $netProfit >= 0 ? 'is-green' : 'is-red' }}">
                <div class="reports-kpi-head"><span>{{ __('products.net_profit_76549b') }}</span><i aria-hidden="true">&#8377;</i></div>
                <strong>{{ $netProfit < 0 ? '−' : '' }}&#8377;{{ number_format(abs($netProfit), 0) }}</strong>
                <small>{{ __('orders.after_expenses_in_selected_period_8d55b1') }}</small>
            </article>
        </section>

        <section class="reports-chart-grid">
            <article class="reports-card reports-trend-card">
                <div class="section-title reports-chart-head">
                    <div>
                        <p class="eyebrow">{{ __('dashboard.financial_trend_9f773f') }}</p>
                        <h2>{{ __('orders.sales_vs_purchases_c30b98') }}</h2>
                    </div>
                    <div class="reports-chart-legend" aria-label="{{ __('common.chart_legend_0de221') }}">
                        <span class="is-sales">{{ __('orders.sales_d0edfb') }}</span>
                        <span class="is-purchases">{{ __('orders.purchases_6c4e94') }}</span>
                    </div>
                </div>

                <div class="reports-line-chart">
                    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="{{ __('orders.sales_and_purchase_values_across_the_selected_report_period_a19d9e') }}">
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
                            <circle class="reports-chart-point is-sales" cx="{{ $pointX }}" cy="{{ $salesY }}" r="4"><title>{{ __('common.chart_sales', ['label' => $point['label'], 'value' => number_format($point['sales'], 2)]) }}</title></circle>
                            <circle class="reports-chart-point is-purchases" cx="{{ $pointX }}" cy="{{ $purchasesY }}" r="4"><title>{{ __('common.chart_purchases', ['label' => $point['label'], 'value' => number_format($point['purchases'], 2)]) }}</title></circle>
                            <text class="reports-chart-label" x="{{ $pointX }}" y="178" text-anchor="middle">{{ $point['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
            </article>

            <article class="reports-card reports-bar-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('common.units_moved_6024a2') }}</p>
                        <h2>{{ __('products.product_bar_chart_1d58e0') }}</h2>
                    </div>
                </div>
                <div class="reports-bar-chart" role="img" aria-label="{{ __('products.units_sold_by_top_product_3943e2') }}">
                    @forelse ($topProducts as $item)
                        @php $barHeight = max(8, round(($item->sold / $topSoldMax) * 100)); @endphp
                        <div class="reports-bar-column">
                            <strong>{{ number_format($item->sold) }}</strong>
                            <div><i style="height: {{ $barHeight }}%"></i></div>
                            <span title="{{ $item->product?->localized('name') ?? __('products.product_dd3b86') }}">{{ $item->product?->localized('name') ?? __('products.product_dd3b86') }}</span>
                        </div>
                    @empty
                        <div class="empty-state tight-empty">{{ __('products.no_product_sales_to_chart_ece326') }}</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="reports-grid">
            <article class="reports-card reports-wide">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('orders.sales_performance_f8a7ac') }}</p>
                        <h2>{{ __('products.top_selling_products_6e87ea') }}</h2>
                    </div>
                    <a href="{{ route('sales.index') }}">{{ __('orders.billing_abaec4') }}</a>
                </div>

                <div class="reports-ranked-list">
                    @forelse ($topProducts as $item)
                        @php $width = max(8, round(($item->sold / $topSoldMax) * 100)); @endphp
                        <div class="reports-ranked-row">
                            <div>
                                <strong>{{ $item->product?->localized('name') ?? __('products.product_dd3b86') }}</strong>
                                <span>{{ $item->product?->localized('category') ?? __('common.uncategorized_4ef7d7') }}</span>
                            </div>
                            <b>{{ __('common.sold_count', ['count' => number_format($item->sold)]) }} <small>&#8377;{{ number_format($item->revenue, 0) }}</small></b>
                            <i><span style="width: {{ $width }}%"></span></i>
                        </div>
                    @empty
                        <div class="empty-state tight-empty">{{ __('orders.no_sales_found_for_this_date_range_f915a9') }}</div>
                    @endforelse
                </div>
            </article>

            <article class="reports-card reports-summary-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('common.snapshot_b08ae3') }}</p>
                        <h2>{{ __('products.inventory_signals_23f431') }}</h2>
                    </div>
                </div>
                <div class="reports-signal-grid">
                    <a href="{{ route('products.index', ['stock' => 'low']) }}">
                        <strong>{{ number_format($lowStockCount) }}</strong>
                        <span>{{ __('products.low_stock_a6e1fe') }}</span>
                    </a>
                    <a href="{{ route('products.index') }}">
                        <strong>{{ number_format($deadStock->count()) }}</strong>
                        <span>{{ __('products.dead_stock_d83e70') }}</span>
                    </a>
                    <a href="{{ route('reports.index') }}">
                        <strong>{{ number_format($movementCount) }}</strong>
                        <span>{{ __('common.range_movements_9904bc') }}</span>
                    </a>
                </div>
            </article>

            <article class="reports-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('products.inventory_risk_4069a0') }}</p>
                        <h2>{{ __('products.low_stock_products_f6d388') }}</h2>
                    </div>
                    <a href="{{ route('products.index', ['stock' => 'low']) }}">{{ __('common.open_cf9b77') }}</a>
                </div>
                @forelse ($lowStock as $product)
                    <div class="reports-stock-row">
                        <div>
                            <strong>{{ $product->localized('name') }}</strong>
                            <span>{{ __('common.minimum_count', ['count' => $product->minimum_stock_level]) }}</span>
                        </div>
                        <a class="stock-count {{ $product->inventory === 0 ? 'is-out' : '' }}" href="{{ route('purchases.index') }}">{{ $product->inventory }}</a>
                    </div>
                @empty
                    <div class="empty-state tight-empty">{{ __('products.no_low_stock_products_b30aa7') }}</div>
                @endforelse
            </article>

            <article class="reports-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('common.slow_movers_48751d') }}</p>
                        <h2>{{ __('products.dead_stock_d83e70') }}</h2>
                    </div>
                    <a href="{{ route('products.index') }}">{{ __('products.inventory_300d29') }}</a>
                </div>
                @forelse ($deadStock as $product)
                    <div class="reports-stock-row">
                        <div>
                            <strong>{{ $product->localized('name') }}</strong>
                            <span>{{ __('common.units_no_sales', ['count' => number_format($product->inventory)]) }}</span>
                        </div>
                        <span class="reports-muted-pill">{{ $product->localized('category') ?: __('common.item_ecdda5') }}</span>
                    </div>
                @empty
                    <div class="empty-state tight-empty">{{ __('products.no_dead_stock_963ad4') }}</div>
                @endforelse
            </article>

            <article class="reports-card reports-wide">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('common.audit_trail_33de86') }}</p>
                        <h2>{{ __('products.purchase_and_stock_trends_28eaee') }}</h2>
                    </div>
                    <a href="{{ route('purchases.index') }}">{{ __('orders.purchases_6c4e94') }}</a>
                </div>

                <div class="reports-timeline">
                    @forelse ($movements as $movement)
                        <div class="reports-timeline-row">
                            <span class="reports-timeline-dot"></span>
                            <div>
                                <strong>{{ $movement->product?->localized('name') ?? __('products.product_dd3b86') }}</strong>
                                <span>{{ __(ucfirst(str_replace('_', ' ', $movement->type))) }} {{ __('common.units_count', ['count' => number_format($movement->quantity)]) }}</span>
                                @if ($movement->notes)
                                    <small>{{ $movement->display_notes }}</small>
                                @endif
                            </div>
                            <time>{{ $movement->created_at->translatedFormat('d M') }}</time>
                        </div>
                    @empty
                        <div class="empty-state tight-empty">{{ __('common.no_movements_period') }}</div>
                    @endforelse
                </div>
            </article>
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var printButton = document.querySelector('[data-print-report]');
            var filterForm = document.querySelector('[data-reports-filter-form]');

            if (printButton) {
                printButton.addEventListener('click', function () {
                    window.print();
                });
            }

            if (filterForm) {
                filterForm.addEventListener('change', function (event) {
                    if (!event.target.matches('[data-report-filter]') || !filterForm.checkValidity()) {
                        return;
                    }

                    if (typeof filterForm.requestSubmit === 'function') {
                        filterForm.requestSubmit();
                    } else {
                        filterForm.submit();
                    }
                });
            }
        });
    </script>
@endsection
