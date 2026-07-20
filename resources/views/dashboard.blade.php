@extends('layouts.admin', ['title' => 'Dashboard'])

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
                <p class="eyebrow">Tenant workspace</p>
                <h1>{{ $tenant->business_name ?? 'Inventory Dashboard' }}</h1>
                <p>Today: {{ number_format($stats['today_orders']) }} invoices, {{ number_format($stats['inventory']) }} units in stock, and {{ number_format($stats['low_stock']) }} low-stock alerts.</p>
            </div>
            <div class="dashboard-actions">
                <a class="primary-link" href="{{ route('sales.index') }}">Create invoice</a>
                <a class="ghost-button" href="{{ route('purchases.index') }}">Receive stock</a>
                <a class="ghost-button" href="{{ route('products.create') }}">Add product</a>
            </div>
        </header>

        <section class="dashboard-kpis dashboard-kpis-pro">
            <a class="dashboard-kpi is-blue" href="{{ route('sales.index') }}">
                <span>Today's sales</span>
                <strong>&#8377;{{ number_format($stats['today_sales'], 0) }}</strong>
                <small>{{ number_format($stats['today_orders']) }} invoices created</small>
            </a>
            <a class="dashboard-kpi is-green" href="{{ route('reports.index') }}">
                <span>Monthly revenue</span>
                <strong>&#8377;{{ number_format($stats['monthly_revenue'], 0) }}</strong>
                <small>Gross profit &#8377;{{ number_format($stats['gross_profit'], 0) }}</small>
            </a>
            <a class="dashboard-kpi is-amber" href="{{ route('purchases.index') }}">
                <span>Monthly purchase</span>
                <strong>&#8377;{{ number_format($stats['monthly_purchase'], 0) }}</strong>
                <small>Inventory value &#8377;{{ number_format($stats['inventory_value'], 0) }}</small>
            </a>
            <a class="dashboard-kpi is-red" href="{{ route('customers.index') }}">
                <span>Outstanding</span>
                <strong>&#8377;{{ number_format($stats['outstanding'], 0) }}</strong>
                <small>Customer dues</small>
            </a>
        </section>

        <section class="dashboard-quick-grid">
            <a href="{{ route('sales.index') }}"><b>Billing</b><span>Search SKU, create invoice, collect payment</span></a>
            <a href="{{ route('purchases.index') }}"><b>Stock In</b><span>Receive purchase orders and update cost</span></a>
            <a href="{{ route('returns.index') }}"><b>Returns</b><span>Process sales or supplier returns</span></a>
            <a href="{{ route('reports.index') }}"><b>Reports</b><span>Revenue, margin, dead stock, trends</span></a>
        </section>

        <section class="dashboard-chart-grid">
            <article class="dashboard-panel dashboard-chart-panel">
                <div class="section-title">
                    <div><p class="eyebrow">7 day graph</p><h2>Sales vs purchase</h2></div>
                    <a href="{{ route('reports.index') }}">Reports</a>
                </div>
                <div class="dashboard-bar-chart" aria-label="Seven day sales and purchase graph">
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
                <div class="dashboard-chart-legend"><span class="sales">Sales</span><span class="purchase">Purchase</span></div>
            </article>

            <article class="dashboard-panel dashboard-donut-panel">
                <div class="section-title"><div><p class="eyebrow">Graph</p><h2>Stock health</h2></div></div>
                <div class="dashboard-donut-wrap">
                    <div class="dashboard-donut" style="--healthy: {{ $healthyShare }}%; --low: {{ $lowShare }}%; --out: {{ $outShare }}%;">
                        <strong>{{ number_format($stats['products']) }}</strong>
                        <span>Products</span>
                    </div>
                    <div class="dashboard-donut-legend">
                        <span><i class="healthy"></i> Healthy {{ $healthyShare }}%</span>
                        <span><i class="low"></i> Low {{ $lowShare }}%</span>
                        <span><i class="out"></i> Out {{ $outShare }}%</span>
                    </div>
                </div>
            </article>
        </section>

        <section class="dashboard-pro-grid">
            <article class="dashboard-panel dashboard-stock-card">
                <div class="section-title">
                    <div><p class="eyebrow">Inventory health</p><h2>Stock control</h2></div>
                    <a href="{{ route('products.index') }}">View all</a>
                </div>

                <div class="stock-health-meter" aria-label="Stock health">
                    <span class="healthy" style="width: {{ $healthyShare }}%"></span>
                    <span class="low" style="width: {{ $lowShare }}%"></span>
                    <span class="out" style="width: {{ $outShare }}%"></span>
                </div>

                <div class="dashboard-stock-stats">
                    <a href="{{ route('products.index') }}"><strong>{{ number_format($stats['products']) }}</strong><span>Products</span></a>
                    <a href="{{ route('products.index', ['stock' => 'healthy']) }}"><strong>{{ number_format($stats['healthy_stock']) }}</strong><span>Healthy</span></a>
                    <a href="{{ route('products.index', ['stock' => 'low']) }}"><strong>{{ number_format($stats['low_stock']) }}</strong><span>Low</span></a>
                    <a href="{{ route('products.index', ['stock' => 'out']) }}"><strong>{{ number_format($stats['out_of_stock']) }}</strong><span>Out</span></a>
                </div>

                <div class="dashboard-mini-list">
                    <h3>Recent products</h3>
                    @forelse ($recentProducts as $product)
                        <div class="dashboard-product-row">
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <span>{{ $product->sku ?: 'SKU-'.$product->id }} · {{ $product->category ?: 'Uncategorized' }}</span>
                            </div>
                            <b>{{ $product->inventory }}</b>
                        </div>
                    @empty
                        <div class="empty-state tight-empty">No products yet.</div>
                    @endforelse
                </div>
            </article>

            <article class="dashboard-panel">
                <div class="section-title"><div><p class="eyebrow">Alerts</p><h2>Low stock</h2></div><a href="{{ route('products.index', ['stock' => 'low']) }}">Open</a></div>
                @forelse ($lowStockProducts as $product)
                    <div class="stock-row">
                        <div><strong>{{ $product->name }}</strong><span>Minimum {{ $product->minimum_stock_level }} · Available {{ $product->available_stock }}</span></div>
                        <a class="stock-count {{ $product->inventory === 0 ? 'is-out' : '' }}" href="{{ route('purchases.index') }}">{{ $product->inventory }}</a>
                    </div>
                @empty
                    <div class="empty-state tight-empty">No low-stock alerts.</div>
                @endforelse
            </article>

            <article class="dashboard-panel">
                <div class="section-title"><div><p class="eyebrow">Sales</p><h2>Recent invoices</h2></div><a href="{{ route('sales.index') }}">Billing</a></div>
                @forelse ($recentSales as $sale)
                    <div class="stock-row">
                        <div><strong>{{ $sale->invoice_number }}</strong><span>{{ $sale->customer->name ?? 'Walk-in customer' }}</span></div>
                        <span>&#8377;{{ number_format($sale->total_amount, 0) }}</span>
                    </div>
                @empty
                    <div class="empty-state tight-empty">No invoices yet.</div>
                @endforelse
            </article>

            <article class="dashboard-panel">
                <div class="section-title"><div><p class="eyebrow">Movement audit</p><h2>Stock movements</h2></div><a href="{{ route('reports.index') }}">Reports</a></div>
                @forelse ($recentMovements as $movement)
                    <div class="stock-row">
                        <div><strong>{{ $movement->product->name ?? 'Product' }}</strong><span>{{ ucfirst(str_replace('_', ' ', $movement->type)) }} {{ $movement->quantity }} units</span></div>
                        <span>{{ $movement->stock_after }}</span>
                    </div>
                @empty
                    <div class="empty-state tight-empty">No stock movements yet.</div>
                @endforelse
            </article>

            <article class="dashboard-panel" data-best-sellers data-best-sellers-url="{{ route('dashboard.best-sellers') }}">
                <div class="section-title">
                    <div><p class="eyebrow">Top products</p><h2>Best sellers</h2></div>
                    <label class="best-seller-filter"><span class="sr-only">Rank best sellers by</span><select data-best-seller-metric aria-label="Rank best sellers by"><option value="units" @selected($topProductMetric === 'units')>Units sold</option><option value="profit" @selected($topProductMetric === 'profit')>Profit earned</option></select></label>
                </div>
                <div data-best-seller-results aria-live="polite">
                    @include('dashboard.partials.best-sellers', ['metric' => $topProductMetric])
                </div>
            </article>

            <article class="dashboard-panel">
                <div class="section-title"><div><p class="eyebrow">Categories</p><h2>Stock by category</h2></div></div>
                @forelse ($categoryBreakdown as $category)
                    @php $width = min(100, max(6, round(($category->units / max(1, $stats['inventory'])) * 100))); @endphp
                    <div class="category-progress-row">
                        <div><strong>{{ $category->label }}</strong><span>{{ number_format($category->total) }} products · {{ number_format($category->units) }} units</span></div>
                        <i><span style="width: {{ $width }}%"></span></i>
                    </div>
                @empty
                    <div class="empty-state tight-empty">No category data yet.</div>
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

                if (! response.ok) throw new Error('Unable to load best sellers.');
                results.innerHTML = data.html;
            } catch (error) {
                results.insertAdjacentHTML('afterbegin', '<p class="best-seller-error">Unable to update best sellers. Please try again.</p>');
            } finally {
                panel.classList.remove('is-loading');
                select.disabled = false;
            }
        });
    });
</script>
@endpush
