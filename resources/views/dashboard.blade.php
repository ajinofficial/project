@extends('layouts.admin', ['title' => __('dashboard.dashboard_d87f47')])

@section('content')
    @php
        $stockTotal = max(1, $stats['products']);
        $healthyShare = round(($stats['healthy_stock'] / $stockTotal) * 100);
        $lowShare = round(($stats['low_stock'] / $stockTotal) * 100);
        $outShare = round(($stats['out_of_stock'] / $stockTotal) * 100);
    @endphp

    <section class="inapp-dashboard dashboard-pro">
        <header class="dashboard-hero dashboard-hero-pro">
            <div>
                <p class="eyebrow">{{ __('settings.tenant_workspace_9f51a9') }}</p>
                <h1>{{ $tenant->business_name ?? __('products.inventory_dashboard_f8c153') }}</h1>
                <p>{{ __('common.today_summary', ['invoices' => number_format($stats['today_orders']), 'units' => number_format($stats['inventory']), 'alerts' => number_format($stats['low_stock'])]) }}</p>
            </div>
            <div class="dashboard-actions">
                <a class="primary-link" href="{{ route('sales.index') }}">{{ __('orders.create_invoice_a0567c') }}</a>
                <a class="ghost-button" href="{{ route('purchases.index') }}">{{ __('products.receive_stock_fc78c1') }}</a>
                <a class="ghost-button" href="{{ route('products.create') }}">{{ __('products.add_product_7c3795') }}</a>
            </div>
        </header>

        <section class="dashboard-kpis dashboard-kpis-pro">
            <a class="dashboard-kpi is-blue" href="{{ route('sales.index') }}">
                <span>{{ __('orders.today_s_sales_23b9b8') }}</span>
                <strong>&#8377;{{ number_format($stats['today_sales'], 0) }}</strong>
                <small>{{ __('common.invoices_created', ['count' => number_format($stats['today_orders'])]) }}</small>
            </a>
            <a class="dashboard-kpi is-green" href="{{ route('reports.index') }}">
                <span>{{ __('dashboard.monthly_revenue_93e061') }}</span>
                <strong>&#8377;{{ number_format($stats['monthly_revenue'], 0) }}</strong>
                <small>Gross profit &#8377;{{ number_format($stats['gross_profit'], 0) }}</small>
            </a>
            <a class="dashboard-kpi {{ $stats['net_profit'] >= 0 ? 'is-amber' : 'is-red' }}" href="{{ route('expenses.index') }}">
                <span>{{ __('products.net_profit_76549b') }}</span>
                <strong>{{ $stats['net_profit'] < 0 ? '−' : '' }}&#8377;{{ number_format(abs($stats['net_profit']), 0) }}</strong>
                <small>{{ __('orders.after_all_recorded_expenses_ad8bbb') }}</small>
            </a>
            <a class="dashboard-kpi is-red" href="{{ route('customers.index') }}">
                <span>{{ __('common.outstanding_f8ee57') }}</span>
                <strong>&#8377;{{ number_format($stats['outstanding'], 0) }}</strong>
                <small>{{ __('orders.customer_dues_20fc37') }}</small>
            </a>
        </section>

        <section class="dashboard-quick-grid">
            @if (\App\Support\RolePermission::canAccess(auth()->user(), 'services'))
            <a href="{{ route('services.index') }}"><b>{{ __('common.services_open', ['count' => $stats['service_open']]) }}</b><span>{{ __('common.service_totals', ['revenue' => number_format($stats['service_revenue'], 2), 'cost' => number_format($stats['service_costs'], 2), 'profit' => number_format($stats['service_profit'], 2)]) }}</span></a>
            @endif
            <a href="{{ route('sales.index') }}"><b>{{ __('orders.billing_abaec4') }}</b><span>{{ __('products.search_sku_create_invoice_collect_payment_25bd56') }}</span></a>
            <a href="{{ route('purchases.index') }}"><b>{{ __('products.stock_in_ec230f') }}</b><span>{{ __('orders.receive_purchase_orders_and_update_cost_ebd2c4') }}</span></a>
            <a href="{{ route('returns.index') }}"><b>{{ __('orders.returns_9582a0') }}</b><span>{{ __('orders.process_sales_or_supplier_returns_6a80f2') }}</span></a>
            <a href="{{ route('reports.index') }}"><b>{{ __('dashboard.reports_88bc3f') }}</b><span>{{ __('products.revenue_margin_dead_stock_trends_7daae0') }}</span></a>
        </section>

        <section class="dashboard-chart-grid">
            <article class="dashboard-panel dashboard-chart-panel">
                <div class="section-title">
                    <div><p class="eyebrow">{{ __('dashboard.7_day_graph_ef3f75') }}</p><h2>{{ __('orders.sales_vs_purchase_402b0b') }}</h2></div>
                    <a href="{{ route('reports.index') }}">{{ __('dashboard.reports_88bc3f') }}</a>
                </div>
                <div class="dashboard-bar-chart" aria-label="{{ __('orders.seven_day_sales_and_purchase_graph_361eca') }}">
                    @foreach ($trend as $day)
                        @php
                            $salesHeight = max(5, round(($day['sales'] / $trendMax) * 100));
                            $purchaseHeight = max(5, round(($day['purchases'] / $trendMax) * 100));
                        @endphp
                        <div class="dashboard-bar-day">
                            <div class="dashboard-bars">
                                <span class="sales" style="height: {{ $salesHeight }}%" title="Sales: {{ number_format($day['sales'], 0) }}"></span>
                                <span class="purchase" style="height: {{ $purchaseHeight }}%" title="Purchase: {{ number_format($day['purchases'], 0) }}"></span>
                            </div>
                            <small>{{ $day['label'] }}</small>
                        </div>
                    @endforeach
                </div>
                <div class="dashboard-chart-legend"><span class="sales">{{ __('orders.sales_d0edfb') }}</span><span class="purchase">{{ __('orders.purchase_160f06') }}</span></div>
            </article>

            <article class="dashboard-panel dashboard-donut-panel">
                <div class="section-title"><div><p class="eyebrow">{{ __('dashboard.graph_9a7405') }}</p><h2>{{ __('products.stock_health_720984') }}</h2></div></div>
                <div class="dashboard-donut-wrap">
                    <div class="dashboard-donut" style="--healthy: {{ $healthyShare }}%; --low: {{ $lowShare }}%; --out: {{ $outShare }}%;">
                        <strong>{{ number_format($stats['products']) }}</strong>
                        <span>{{ __('products.products_fe0a09') }}</span>
                    </div>
                    <div class="dashboard-donut-legend">
                        <span><i class="healthy"></i> {{ __('common.healthy_percent', ['value' => $healthyShare]) }}</span>
                        <span><i class="low"></i> {{ __('common.low_percent', ['value' => $lowShare]) }}</span>
                        <span><i class="out"></i> {{ __('common.out_percent', ['value' => $outShare]) }}</span>
                    </div>
                </div>
            </article>
        </section>

        <section class="dashboard-pro-grid">
            <article class="dashboard-panel dashboard-stock-card">
                <div class="section-title">
                    <div><p class="eyebrow">{{ __('products.inventory_health_d23350') }}</p><h2>{{ __('products.stock_control_3a6cb5') }}</h2></div>
                    <a href="{{ route('products.index') }}">{{ __('common.view_all_931e1a') }}</a>
                </div>

                <div class="stock-health-meter" aria-label="{{ __('products.stock_health_720984') }}">
                    <span class="healthy" style="width: {{ $healthyShare }}%"></span>
                    <span class="low" style="width: {{ $lowShare }}%"></span>
                    <span class="out" style="width: {{ $outShare }}%"></span>
                </div>

                <div class="dashboard-stock-stats">
                    <a href="{{ route('products.index') }}"><strong>{{ number_format($stats['products']) }}</strong><span>{{ __('products.products_fe0a09') }}</span></a>
                    <a href="{{ route('products.index', ['stock' => 'healthy']) }}"><strong>{{ number_format($stats['healthy_stock']) }}</strong><span>{{ __('common.healthy_80ea15') }}</span></a>
                    <a href="{{ route('products.index', ['stock' => 'low']) }}"><strong>{{ number_format($stats['low_stock']) }}</strong><span>{{ __('common.low_a12494') }}</span></a>
                    <a href="{{ route('products.index', ['stock' => 'out']) }}"><strong>{{ number_format($stats['out_of_stock']) }}</strong><span>{{ __('common.out_220e06') }}</span></a>
                </div>

                <div class="dashboard-mini-list">
                    <h3>{{ __('products.recent_products_229023') }}</h3>
                    @forelse ($recentProducts as $product)
                        <div class="dashboard-product-row">
                            <div>
                                <strong>{{ $product->localized('name') }}</strong>
                                <span>{{ $product->sku ?: 'SKU-'.$product->id }} · {{ $product->localized('category') ?: __('common.uncategorized_4ef7d7') }}</span>
                            </div>
                            <b>{{ $product->inventory }}</b>
                        </div>
                    @empty
                        <div class="empty-state tight-empty">{{ __('products.no_products_yet_a68066') }}</div>
                    @endforelse
                </div>
            </article>

            <article class="dashboard-panel">
                <div class="section-title"><div><p class="eyebrow">{{ __('notifications.alerts_d0efe0') }}</p><h2>{{ __('products.low_stock_a6e1fe') }}</h2></div><a href="{{ route('products.index', ['stock' => 'low']) }}">{{ __('common.open_cf9b77') }}</a></div>
                @forelse ($lowStockProducts as $product)
                    <div class="stock-row">
                        <div><strong>{{ $product->localized('name') }}</strong><span>{{ __('common.stock_min_available', ['minimum' => $product->minimum_stock_level, 'available' => $product->available_stock]) }}</span></div>
                        <a class="stock-count {{ $product->inventory === 0 ? 'is-out' : '' }}" href="{{ route('purchases.index') }}">{{ $product->inventory }}</a>
                    </div>
                @empty
                    <div class="empty-state tight-empty">{{ __('notifications.no_low_stock_alerts_5ebabe') }}</div>
                @endforelse
            </article>

            <article class="dashboard-panel">
                <div class="section-title"><div><p class="eyebrow">{{ __('orders.sales_d0edfb') }}</p><h2>{{ __('orders.recent_invoices_11b72a') }}</h2></div><a href="{{ route('sales.index') }}">{{ __('orders.billing_abaec4') }}</a></div>
                @forelse ($recentSales as $sale)
                    <div class="stock-row">
                        <div><strong>{{ $sale->invoice_number }}</strong><span>{{ $sale->customer->name ?? __('orders.walk_in_customer_543765') }}</span></div>
                        <span>&#8377;{{ number_format($sale->total_amount, 0) }}</span>
                    </div>
                @empty
                    <div class="empty-state tight-empty">{{ __('orders.no_invoices_yet_7da80f') }}</div>
                @endforelse
            </article>

            <article class="dashboard-panel">
                <div class="section-title"><div><p class="eyebrow">{{ __('common.movement_audit_e63d6b') }}</p><h2>{{ __('products.stock_movements_d7af96') }}</h2></div><a href="{{ route('reports.index') }}">{{ __('dashboard.reports_88bc3f') }}</a></div>
                @forelse ($recentMovements as $movement)
                    <div class="stock-row">
                        <div><strong>{{ $movement->product?->localized('name') ?? __('products.product_dd3b86') }}</strong><span>{{ __(ucfirst(str_replace('_', ' ', $movement->type))) }} {{ __('common.units_count', ['count' => $movement->quantity]) }}</span></div>
                        <span>{{ $movement->stock_after }}</span>
                    </div>
                @empty
                    <div class="empty-state tight-empty">{{ __('products.no_stock_movements_yet_cde5e1') }}</div>
                @endforelse
            </article>

            <article class="dashboard-panel" data-best-sellers data-best-sellers-url="{{ route('dashboard.best-sellers') }}">
                <div class="section-title">
                    <div><p class="eyebrow">{{ __('products.top_products_a3b3ba') }}</p><h2>{{ __('dashboard.best_sellers_b14995') }}</h2></div>
                    <label class="best-seller-filter"><span class="sr-only">{{ __('dashboard.rank_best_sellers_by_8b256f') }}</span><select data-best-seller-metric aria-label="{{ __('dashboard.rank_best_sellers_by_8b256f') }}"><option value="units" @selected($topProductMetric === 'units')>{{ __('common.units_sold_b1a3b1') }}</option><option value="profit" @selected($topProductMetric === 'profit')>{{ __('products.profit_earned_e0d134') }}</option></select></label>
                </div>
                <div data-best-seller-results aria-live="polite">
                    @include('dashboard.partials.best-sellers', ['metric' => $topProductMetric])
                </div>
            </article>

            <article class="dashboard-panel">
                <div class="section-title"><div><p class="eyebrow">{{ __('common.categories_6ccb60') }}</p><h2>{{ __('products.stock_by_category_44f6bf') }}</h2></div></div>
                @forelse ($categoryBreakdown as $category)
                    @php $width = min(100, max(6, round(($category->units / max(1, $stats['inventory'])) * 100))); @endphp
                    <div class="category-progress-row">
                        <div><strong>{{ $category->label }}</strong><span>{{ __('common.product_units', ['products' => number_format($category->total), 'units' => number_format($category->units)]) }}</span></div>
                        <i><span style="width: {{ $width }}%"></span></i>
                    </div>
                @empty
                    <div class="empty-state tight-empty">{{ __('products.no_category_data_yet_eed8e8') }}</div>
                @endforelse
            </article>
        </section>
    </section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-best-sellers]').forEach((panel) => {
        const select = panel.querySelector('[data-best-seller-metric]');
        const results = panel.querySelector('[data-best-seller-results]');

        select?.addEventListener('change', async () => {
            const url = new URL(panel.dataset.bestSellersUrl, window.location.origin);
            url.searchParams.set('metric', select.value);
            panel.classList.add('is-loading');
            select.disabled = true;

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await response.json();

                if (! response.ok) throw new Error(window.t("validation.unable_to_load_best_sellers_fa2a99"));
                results.innerHTML = data.html;
            } catch (error) {
                var errorMessage = document.createElement('p');
                errorMessage.className = 'best-seller-error';
                errorMessage.textContent = window.t('Unable to load best sellers.');
                results.prepend(errorMessage);
            } finally {
                panel.classList.remove('is-loading');
                select.disabled = false;
            }
        });
    });
</script>
@endpush
