@extends('layouts.admin', ['title' => __('products.stock_bcecf4')])

@section('content')
    <style>
        .stock-workspace .product-stat-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .stock-workspace .product-stat { min-height: 96px; }
        .stock-workspace.product-overview .product-stat:nth-child(5) { border-top-color: #fb923c; }
        .stock-workspace.product-overview .product-stat:nth-child(6) { border-top-color: #f87171; }
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
        .stock-flow-row strong { display: block; color: var(--v-text); font-size: var(--text-sm); }
        .stock-watch-row span,
        .stock-flow-row span { display: block; margin-top: 4px; color: var(--v-muted); font-size: var(--text-xs); line-height: 1.35; }
        .stock-count-pill,
        .stock-type-pill {
            flex: 0 0 auto;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: var(--text-xs);
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
        .stock-listing-results { position: relative; min-height: 160px; }
        .stock-listing-results .product-listing-loader { inset: 0; }
        @media (max-width: 1280px) {
            .stock-workspace .product-stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 760px) {
            .stock-workspace .product-stat-grid,
            .stock-filter-form { grid-template-columns: 1fr; }
            .stock-filter-form .product-clear-filter { width: 100%; }
            .stock-workspace.product-overview .product-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 360px) {
            .stock-workspace.product-overview .product-stat-grid { grid-template-columns: minmax(0, 1fr); }
        }
    </style>

    <section class="product-workspace stock-workspace product-overview">
        <header class="product-page-head">
            <div>
                <p class="eyebrow">{{ __('products.stock_bcecf4') }}</p>
                <h1>{{ __('products.stock_control_3a6cb5') }}</h1>
                <span>{{ __('products.track_stock_on_hand_and_audit_purchase_billing_return_and_adjustment_m_d270fb') }}</span>
            </div>
            <div class="product-head-actions">
                <a class="ghost-button" href="{{ route('products.index') }}">{{ __('products.inventory_300d29') }}</a>
                <a class="primary-link" href="{{ route('stock.create') }}">{{ __('products.add_stock_64db16') }}</a>
            </div>
        </header>

        <section class="product-stat-grid" aria-label="{{ __('products.stock_summary_1e8eb6') }}">
            <div class="product-stat"><span>{{ __('common.on_hand_d94c32') }}</span><strong>{{ number_format($stats['on_hand']) }}</strong><small>{{ __('products.total_physical_stock_f25c40') }}</small></div>
            <div class="product-stat"><span>{{ __('common.available_7c62a1') }}</span><strong>{{ number_format($stats['available']) }}</strong><small>{{ __('products.sellable_after_holds_b24d1f') }}</small></div>
            <div class="product-stat"><span>{{ __('common.reserved_67a6ff') }}</span><strong>{{ number_format($stats['reserved']) }}</strong><small>{{ __('common.held_units_92b017') }}</small></div>
            <div class="product-stat"><span>{{ __('common.damaged_052fc7') }}</span><strong>{{ number_format($stats['damaged']) }}</strong><small>{{ __('products.unsellable_units_d9770d') }}</small></div>
            <div class="product-stat"><span>{{ __('products.low_stock_a6e1fe') }}</span><strong>{{ number_format($stats['low']) }}</strong><small>{{ __('products.needs_reorder_6ab4bf') }}</small></div>
            <div class="product-stat"><span>{{ __('products.out_of_stock_8b78c7') }}</span><strong>{{ number_format($stats['out']) }}</strong><small>{{ __('common.unavailable_items_2114ba') }}</small></div>
        </section>

        <section class="v-panel product-management-panel">
            <div class="section-title"><div><p class="eyebrow">{{ __('notifications.alerts_d0efe0') }}</p><h2>{{ __('products.low_stock_watchlist_6fa822') }}</h2></div></div>
            <div class="stock-watch-list">
                @forelse ($lowStockProducts as $product)
                    <div class="stock-watch-row">
                        <div>
                            <strong>{{ $product->localized('name') }}</strong>
                            <span>{{ $product->sku ?: 'SKU-'.$product->id }} - {{ __('common.stock_min_available', ['minimum' => number_format($product->minimum_stock_level), 'available' => number_format($product->available_stock)]) }}</span>
                        </div>
                        <span class="stock-count-pill {{ $product->inventory === 0 ? 'is-out' : '' }}">{{ number_format($product->inventory) }}</span>
                    </div>
                @empty
                    <div class="empty-state product-empty">{{ __('products.no_low_stock_items_ea95dc') }}</div>
                @endforelse
            </div>
        </section>

        <section class="v-panel product-management-panel" data-stock-listing>
            <div class="section-title"><div><p class="eyebrow">{{ __('common.connected_flow_ba0d93') }}</p><h2>{{ __('products.stock_movement_ledger_c56946') }}</h2></div></div>
            <div class="stock-flow-row">
                <div>
                    <strong>{{ __('products.purchases_add_stock_billing_reduces_stock_returns_and_adjustments_upda_b85a3e') }}</strong>
                    <span>{{ __('products.every_operation_writes_to_this_ledger_with_quantity_stock_after_and_re_c3eddd') }}</span>
                </div>
            </div>
            <div class="product-toolbar">
                <form class="product-filter-form stock-filter-form" method="GET" action="{{ route('stock.index') }}" data-stock-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('products.search_product_sku_notes_eb334b') }}" data-stock-search>
                    <select name="type" data-stock-search>
                        <option value="">{{ __('common.all_movement_eb0b65') }}</option>
                        <option value="purchase" @selected(request('type') === 'purchase')>{{ __('orders.purchase_160f06') }}</option>
                        <option value="sale" @selected(request('type') === 'sale')>{{ __('common.sale_0028d7') }}</option>
                        <option value="sales_return" @selected(request('type') === 'sales_return')>{{ __('orders.sales_return_81894c') }}</option>
                        <option value="purchase_return" @selected(request('type') === 'purchase_return')>{{ __('orders.purchase_return_1c337e') }}</option>
                        <option value="adjustment" @selected(request('type') === 'adjustment')>{{ __('common.adjustment_7f0496') }}</option>
                        <option value="service" @selected(request('type') === 'service')>{{ __('orders.service_parts_bc8506') }}</option>
                    </select>
                    <select name="per_page" aria-label="{{ __('common.movements_per_page_e3dc47') }}" data-stock-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ __('common.per_page', ['count' => $option]) }}</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('stock.index') }}" data-stock-listing-link>{{ __('common.clear_719ea3') }}</a>
                </form>
            </div>

            <div class="stock-listing-results" data-stock-listing-results>
                <div class="product-listing-loader" data-stock-listing-loader aria-live="polite" aria-hidden="true">
                    <span aria-hidden="true"></span>
                    <strong>{{ __('common.loading_movements_005cb9') }}</strong>
                </div>

                <div class="table-wrap">
                    <table class="inventory-table stock-movement-table">
                        <thead><tr><th>{{ __('common.date_eb9a4b') }}</th><th>{{ __('products.product_dd3b86') }}</th><th>{{ __('common.type_3deb74') }}</th><th>{{ __('common.quantity_44f6af') }}</th><th>{{ __('products.stock_after_4b2cb3') }}</th><th>{{ __('common.notes_704400') }}</th></tr></thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                @php $typeClass = $movement->quantity > 0 ? 'is-in' : ($movement->quantity < 0 ? 'is-out' : 'is-neutral'); @endphp
                                <tr>
                                    <td>{{ $movement->created_at->translatedFormat('d M Y') }}</td>
                                    <td>
                                        <div class="inventory-product-cell">
                                            <span class="item-thumb">
                                                @if ($movement->product?->image_url)
                                                    <img src="{{ $movement->product->image_url }}" alt="">
                                                @else
                                                    {{ strtoupper(substr($movement->product?->localized('name') ?? 'P', 0, 1)) }}
                                                @endif
                                            </span>
                                            <div>
                                                <strong>{{ $movement->product?->localized('name') ?? __('products.product_dd3b86') }}</strong>
                                                <small>{{ $movement->product->sku ?? __('products.no_sku_1bc1c2') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="stock-type-pill {{ $typeClass }}">{{ __(str_replace('_', ' ', ucfirst($movement->type))) }}</span></td>
                                    <td>{{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity) }}</td>
                                    <td>{{ number_format($movement->stock_after) }}</td>
                                    <td>{{ $movement->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">{{ request()->filled('search') || request()->filled('type') ? __('products.no_stock_movements_match_the_current_filters_577eec') : __('products.no_stock_movements_yet_cde5e1') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('products.partials.pagination', ['paginator' => $movements, 'itemLabel' => 'movements'])
            </div>
        </section>
    </section>

    <script>
        (function () {
            if (window.stockListingAjaxBound) {
                return;
            }

            window.stockListingAjaxBound = true;
            var activeRequest = null;
            var searchTimer = null;

            function showStockListingLoader() {
                var listing = document.querySelector('[data-stock-listing]');
                var loader = document.querySelector('[data-stock-listing-loader]');

                if (! listing || ! loader) {
                    return;
                }

                listing.classList.add('is-loading');
                loader.setAttribute('aria-hidden', 'false');
            }

            function filterUrl(form) {
                var params = new URLSearchParams(new FormData(form));

                Array.from(params.keys()).forEach(function (key) {
                    if (params.get(key) === '') {
                        params.delete(key);
                    }
                });

                var query = params.toString();
                return form.action + (query ? '?' + query : '');
            }

            function loadStockListing(url, pushState) {
                var workspace = document.querySelector('.stock-workspace');

                if (! workspace) {
                    window.location.href = url;
                    return;
                }

                if (activeRequest) {
                    activeRequest.abort();
                }

                var requestController = new AbortController();
                activeRequest = requestController;
                showStockListingLoader();

                fetch(url, {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: requestController.signal
                }).then(function (response) {
                    if (! response.ok) {
                        throw new Error(window.t("validation.unable_to_load_stock_movements_b7a06e"));
                    }

                    return response.text();
                }).then(function (html) {
                    var page = new DOMParser().parseFromString(html, 'text/html');
                    var nextWorkspace = page.querySelector('.stock-workspace');

                    if (! nextWorkspace) {
                        throw new Error(window.t("validation.stock_listing_was_not_found_5763fe"));
                    }

                    workspace.replaceWith(nextWorkspace);
                    document.title = page.title || document.title;

                    if (pushState !== false) {
                        window.history.pushState({ stockListing: true }, '', url);
                    }
                }).catch(function (error) {
                    if (error.name !== 'AbortError') {
                        window.location.href = url;
                    }
                }).finally(function () {
                    if (activeRequest === requestController) {
                        activeRequest = null;
                    }
                });
            }

            document.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-stock-search-form]');

                if (! form) {
                    return;
                }

                event.preventDefault();
                loadStockListing(filterUrl(form), true);
            });

            document.addEventListener('change', function (event) {
                var field = event.target.closest('[data-stock-search-form] select[data-stock-search]');

                if (field) {
                    loadStockListing(filterUrl(field.form), true);
                }
            });

            document.addEventListener('input', function (event) {
                var search = event.target.closest('[data-stock-search-form] input[type="search"]');

                if (! search) {
                    return;
                }

                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(function () {
                    loadStockListing(filterUrl(search.form), true);
                }, 350);
            });

            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-stock-listing-link], [data-stock-listing] .product-pagination a');

                if (! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                loadStockListing(link.href, true);
            });

            window.addEventListener('popstate', function () {
                if (window.location.pathname === new URL('{{ route('stock.index') }}').pathname) {
                    loadStockListing(window.location.href, false);
                }
            });
        })();
    </script>
@endsection
