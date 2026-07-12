@extends('layouts.admin', ['title' => 'Stock'])

@section('content')
    <style>
        .stock-workspace .product-stat-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .stock-workspace .product-stat { min-height: 96px; }
        .stock-watch-list { display: grid; gap: 10px; }
        .stock-watch-row,
        .stock-flow-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid var(--v-line);
            border-radius: 8px;
            padding: 12px;
            background: #fff;
        }
        .stock-watch-row strong,
        .stock-flow-row strong { display: block; color: var(--v-text); font-size: 13px; }
        .stock-watch-row span,
        .stock-flow-row span { display: block; margin-top: 4px; color: var(--v-muted); font-size: 12px; line-height: 1.35; }
        .stock-count-pill,
        .stock-type-pill {
            flex: 0 0 auto;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }
        .stock-count-pill { color: #92400e; background: #fef3c7; }
        .stock-count-pill.is-out { color: #991b1b; background: #fee2e2; }
        .stock-type-pill.is-in { color: #166534; background: #dcfce7; }
        .stock-type-pill.is-out { color: #991b1b; background: #fee2e2; }
        .stock-type-pill.is-neutral { color: #334155; background: #e2e8f0; }
        .stock-filter-form { grid-template-columns: minmax(190px, 1fr) minmax(130px, 170px) minmax(100px, 130px) 70px; }
        .stock-movement-table { min-width: 940px; }
        .stock-flow-row { margin-bottom: 16px; }
        @media (max-width: 1280px) {
            .stock-workspace .product-stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 760px) {
            .stock-workspace .product-stat-grid,
            .stock-filter-form { grid-template-columns: 1fr; }
            .stock-filter-form .product-clear-filter { width: 100%; }
        }
    </style>

    <section class="product-workspace stock-workspace">
        <header class="product-page-head">
            <div>
                <p class="eyebrow">Stock</p>
                <h1>Stock control</h1>
                <span>Track stock on hand and audit purchase, billing, return, and adjustment movement.</span>
            </div>
            <div class="product-head-actions">
                <a class="ghost-button" href="{{ route('products.index') }}" data-stock-listing-link>Inventory</a>
                <a class="primary-link" href="{{ route('stock.create') }}" data-stock-listing-link>Add stock</a>
            </div>
        </header>

        <section class="product-stat-grid" aria-label="Stock summary">
            <div class="product-stat"><span>On hand</span><strong>{{ number_format($stats['on_hand']) }}</strong><small>Total physical stock</small></div>
            <div class="product-stat"><span>Available</span><strong>{{ number_format($stats['available']) }}</strong><small>Sellable after holds</small></div>
            <div class="product-stat"><span>Reserved</span><strong>{{ number_format($stats['reserved']) }}</strong><small>Held units</small></div>
            <div class="product-stat"><span>Damaged</span><strong>{{ number_format($stats['damaged']) }}</strong><small>Unsellable units</small></div>
            <div class="product-stat"><span>Low stock</span><strong>{{ number_format($stats['low']) }}</strong><small>Needs reorder</small></div>
            <div class="product-stat"><span>Out of stock</span><strong>{{ number_format($stats['out']) }}</strong><small>Unavailable items</small></div>
        </section>

        <section class="v-panel product-management-panel">
            <div class="section-title"><div><p class="eyebrow">Alerts</p><h2>Low stock watchlist</h2></div></div>
            <div class="stock-watch-list">
                @forelse ($lowStockProducts as $product)
                    <div class="stock-watch-row">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $product->sku ?: 'SKU-'.$product->id }} - Minimum {{ number_format($product->minimum_stock_level) }} - Available {{ number_format($product->available_stock) }}</span>
                        </div>
                        <span class="stock-count-pill {{ $product->inventory === 0 ? 'is-out' : '' }}">{{ number_format($product->inventory) }}</span>
                    </div>
                @empty
                    <div class="empty-state product-empty">No low-stock items.</div>
                @endforelse
            </div>
        </section>

        <section class="v-panel product-management-panel" data-stock-listing>
            <div class="section-title"><div><p class="eyebrow">Connected flow</p><h2>Stock movement ledger</h2></div></div>
            <div class="stock-flow-row">
                <div>
                    <strong>Purchases add stock. Billing reduces stock. Returns and adjustments update stock.</strong>
                    <span>Every operation writes to this ledger with quantity, stock after, and reference notes.</span>
                </div>
            </div>
            <div class="product-listing-loader" data-stock-listing-loader aria-live="polite" aria-hidden="true">
                <span aria-hidden="true"></span>
                <strong>Loading movements</strong>
            </div>

            <div class="product-toolbar">
                <form class="product-filter-form stock-filter-form" method="GET" action="{{ route('stock.index') }}" data-stock-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search product, SKU, notes" data-stock-search>
                    <select name="type" data-stock-search>
                        <option value="">All movement</option>
                        <option value="purchase" @selected(request('type') === 'purchase')>Purchase</option>
                        <option value="sale" @selected(request('type') === 'sale')>Sale</option>
                        <option value="sales_return" @selected(request('type') === 'sales_return')>Sales return</option>
                        <option value="purchase_return" @selected(request('type') === 'purchase_return')>Purchase return</option>
                        <option value="adjustment" @selected(request('type') === 'adjustment')>Adjustment</option>
                    </select>
                    <select name="per_page" aria-label="Movements per page" data-stock-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('stock.index') }}">Clear</a>
                </form>
            </div>

            <div class="table-wrap">
                <table class="inventory-table stock-movement-table">
                    <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Quantity</th><th>Stock after</th><th>Notes</th></tr></thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            @php $typeClass = $movement->quantity > 0 ? 'is-in' : ($movement->quantity < 0 ? 'is-out' : 'is-neutral'); @endphp
                            <tr>
                                <td>{{ $movement->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="inventory-product-cell">
                                        <span class="item-thumb">
                                            @if ($movement->product?->image_url)
                                                <img src="{{ $movement->product->image_url }}" alt="">
                                            @else
                                                {{ strtoupper(substr($movement->product->name ?? 'P', 0, 1)) }}
                                            @endif
                                        </span>
                                        <div>
                                            <strong>{{ $movement->product->name ?? 'Product' }}</strong>
                                            <small>{{ $movement->product->sku ?? 'No SKU' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="stock-type-pill {{ $typeClass }}">{{ str_replace('_', ' ', ucfirst($movement->type)) }}</span></td>
                                <td>{{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity) }}</td>
                                <td>{{ number_format($movement->stock_after) }}</td>
                                <td>{{ $movement->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">{{ request()->filled('search') || request()->filled('type') ? 'No stock movements match the current filters.' : 'No stock movements yet.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('products.partials.pagination', ['paginator' => $movements, 'itemLabel' => 'movements'])
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var listing = document.querySelector('[data-stock-listing]');
            var loader = document.querySelector('[data-stock-listing-loader]');

            function showStockListingLoader() {
                if (! listing || ! loader) {
                    return;
                }

                listing.classList.add('is-loading');
                loader.setAttribute('aria-hidden', 'false');
            }

            document.querySelectorAll('[data-stock-search-form]').forEach(function (form) {
                var search = form.querySelector('input[name="search"]');
                var fields = form.querySelectorAll('[data-stock-search]');
                var timer = null;

                function submitFilters() {
                    showStockListingLoader();

                    if (search && search.value.trim() === '') {
                        search.disabled = true;
                    }

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }

                    form.submit();
                }

                fields.forEach(function (field) {
                    if (field.matches('input[type="search"]')) {
                        field.addEventListener('input', function () {
                            window.clearTimeout(timer);
                            timer = window.setTimeout(submitFilters, 350);
                        });

                        field.addEventListener('search', submitFilters);
                        return;
                    }

                    field.addEventListener('change', submitFilters);
                });

                form.addEventListener('submit', function () {
                    showStockListingLoader();
                });
            });

            document.querySelectorAll('[data-stock-listing-link], .product-pagination a').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }

                    showStockListingLoader();
                });
            });
        });
    </script>
@endsection
