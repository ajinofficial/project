@extends('layouts.admin', [
    'title' => __('products.products_fe0a09'),
])

@section('content')
    <section class="product-workspace product-overview">
        <header class="product-page-head">
            <div>
                <p class="eyebrow">{{ __('products.products_fe0a09') }}</p>
                <h1>{{ __('products.products_fe0a09') }}</h1>
                <span>{{ __('products.manage_item_availability_pricing_and_stock_movement_fe8040') }}</span>
            </div>
            <div class="product-head-actions">
                <a class="ghost-button" href="{{ route('products.index', ['stock' => 'low']) }}" data-product-listing-link>{{ __('products.low_stock_a6e1fe') }}</a>
                <a class="primary-link" href="{{ route('products.create') }}">{{ __('products.add_product_7c3795') }}</a>
            </div>
        </header>

        <section class="product-stat-grid" aria-label="{{ __('products.product_metrics_a6a39c') }}">
            <a class="product-stat" href="{{ route('products.index') }}" data-product-listing-link>
                <span>{{ __('products.total_products_c48f2b') }}</span>
                <strong>{{ number_format($stats['total']) }}</strong>
                <small>{{ __('common.all_catalog_items_8f2b36') }}</small>
            </a>
            <a class="product-stat" href="{{ route('products.index', ['status' => 'active']) }}" data-product-listing-link>
                <span>{{ __('common.active_items_cd2cf4') }}</span>
                <strong>{{ number_format($stats['active']) }}</strong>
                <small>{{ __('common.ready_to_sell_cb3667') }}</small>
            </a>
            <a class="product-stat" href="{{ route('products.index', ['stock' => 'low']) }}" data-product-listing-link>
                <span>{{ __('products.low_stock_023d11') }}</span>
                <strong>{{ number_format($stats['low']) }}</strong>
                <small>{{ __('products.needs_reorder_6ab4bf') }}</small>
            </a>
            <a class="product-stat" href="{{ route('products.index', ['stock' => 'out']) }}" data-product-listing-link>
                <span>{{ __('products.stock_value_c2267f') }}</span>
                <strong>&#8377;{{ number_format($stats['value'], 0) }}</strong>
                <small>{{ __('common.unavailable_count', ['count' => number_format($stats['out'])]) }}</small>
            </a>
        </section>

        <section class="v-panel product-management-panel" data-product-listing>
            <div class="product-toolbar">
                <form class="product-filter-form" method="GET" action="{{ route('products.index') }}" data-product-filters>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('products.search_product_sku_category_089bcf') }}" data-auto-filter>
                    <select name="status" data-auto-filter>
                        <option value="">{{ __('common.all_status_543fcc') }}</option>
                        <option value="draft" @selected(request('status') === 'draft')>{{ __('common.draft_23d33e') }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ __('common.active_a733b8') }}</option>
                        <option value="archived" @selected(request('status') === 'archived')>{{ __('common.archived_eddc81') }}</option>
                    </select>
                    <select name="stock" data-auto-filter>
                        <option value="">{{ __('products.all_stock_a7e443') }}</option>
                        <option value="healthy" @selected(request('stock') === 'healthy')>{{ __('common.healthy_80ea15') }}</option>
                        <option value="low" @selected(request('stock') === 'low')>{{ __('common.low_a12494') }}</option>
                        <option value="out" @selected(request('stock') === 'out')>{{ __('common.out_220e06') }}</option>
                    </select>
                    <select name="sort" data-auto-filter>
                        <option value="">{{ __('common.newest_d04428') }}</option>
                        <option value="name" @selected(request('sort') === 'name')>{{ __('common.name_709a23') }}</option>
                        <option value="stock_low" @selected(request('sort') === 'stock_low')>{{ __('products.stock_low_7b361b') }}</option>
                        <option value="stock_high" @selected(request('sort') === 'stock_high')>{{ __('products.stock_high_864b69') }}</option>
                    </select>
                    <select name="per_page" aria-label="{{ __('products.products_per_page_db48df') }}" data-auto-filter>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ __('common.per_page', ['count' => $option]) }}</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('products.index') }}" data-product-listing-link>{{ __('common.clear_719ea3') }}</a>
                </form>
            </div>

            <div class="product-listing-loader" data-product-listing-loader aria-live="polite" aria-hidden="true">
                <span aria-hidden="true"></span>
                <strong>{{ __('products.loading_products_430714') }}</strong>
            </div>

            @if ($products->isEmpty())
                <div class="empty-state product-empty">
                    @if ($hasActiveFilters)
                        <h3>{{ __('products.no_matching_products_6a98e1') }}</h3>
                        <p>{{ __('products.no_products_match_the_current_filters_clear_filters_to_see_all_product_aab614') }}</p>
                        <a class="product-clear-filter" href="{{ route('products.index') }}" data-product-listing-link>{{ __('common.clear_filters_412226') }}</a>
                    @else
                        <h3>{{ __('products.no_products_yet_d2fb4b') }}</h3>
                        <p>{{ __('products.add_your_first_product_to_start_managing_your_product_catalog_df88dd') }}</p>
                        <a class="primary-link" href="{{ route('products.create') }}">{{ __('products.add_product_7c3795') }}</a>
                    @endif
                </div>
            @else
                <div class="table-wrap">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>{{ __('products.product_dd3b86') }}</th>
                                <th>{{ __('products.product_id_ea9c55') }}</th>
                                <th>{{ __('common.brand_62b4aa') }}</th>
                                <th>{{ __('products.category_a3c686') }}</th>
                                <th>{{ __('common.qty_1e5ff9') }}</th>
                                <th>{{ __('common.health_3703cd') }}</th>
                                <th>{{ __('common.status_bae7d5') }}</th>
                                <th>{{ __('common.action_97c89a') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                @php
                                    $stockLabel = $product->inventory === 0 ? __('common.out_220e06') : ($product->inventory <= $product->minimum_stock_level ? __('common.low_a12494') : __('common.healthy_80ea15'));
                                    $stockClass = $product->inventory === 0 ? 'stock-out' : ($product->inventory <= $product->minimum_stock_level ? 'stock-low' : 'stock-ok');
                                    $stockWidth = min(100, max(4, $product->inventory));
                                @endphp
                                <tr>
                                    <td>
                                        <div class="inventory-product-cell">
                                            <span class="item-thumb">
                                                @if ($product->image_url)
                                                    <img src="{{ $product->image_url }}" alt="">
                                                @else
                                                    {{ strtoupper(substr($product->name, 0, 1)) }}
                                                @endif
                                            </span>
                                            <div>
                                                <strong>{{ $product->localized('name') }}</strong>
                                                <small>{{ $product->barcode ?: __('products.no_barcode_1e2834') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $product->sku ?: 'SKU-'.$product->id }}</td>
                                    <td>{{ $product->brand ?: '—' }}</td>
                                    <td>{{ $product->localized('category') ?: __('common.uncategorized_4ef7d7') }}</td>
                                    <td>
                                        <strong>{{ number_format($product->inventory) }}</strong>
                                        <span class="stock-meter"><i style="width: {{ $stockWidth }}%"></i></span>
                                    </td>
                                    <td><span class="status-chip {{ $stockClass }}">{{ $stockLabel }}</span></td>
                                    <td><span class="status-chip status-{{ $product->status }}">{{ __(ucfirst($product->status)) }}</span></td>
                                    <td>
                                        <div class="inventory-actions">
                                            <a href="{{ route('products.edit', $product) }}">{{ __('common.edit_530164') }}</a>
                                            <form method="POST" action="{{ route('products.update', $product) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="name" value="{{ $product->localized('name') }}">
                                                <input type="hidden" name="sku" value="{{ $product->sku }}">
                                                <input type="hidden" name="barcode" value="{{ $product->barcode }}">
                                                <input type="hidden" name="category" value="{{ $product->localized('category') }}">
                                                <input type="hidden" name="brand" value="{{ $product->brand }}">
                                                <input type="hidden" name="minimum_stock_level" value="{{ $product->minimum_stock_level }}">
                                                <input type="hidden" name="image_url" value="{{ $product->image_url }}">
                                                <input type="hidden" name="description" value="{{ $product->localized('description') }}">
                                                <input type="hidden" name="status" value="{{ $product->status === 'active' ? 'archived' : 'active' }}">
                                                <button type="submit">{{ $product->status === 'active' ? __('common.archive_2621c6') : __('common.activate_92ef08') }}</button>
                                            </form>
                                            <form
                                                method="POST"
                                                action="{{ route('products.destroy', $product) }}"
                                                data-confirm
                                                @if ($product->status === 'active')
                                                    data-confirm-blocked="true"
                                                    data-confirm-title="{{ __('validation.cannot_delete_active_product_e9b429') }}"
                                                    data-confirm-message="Active products cannot be deleted. Archive {{ $product->localized('name') }} first."
                                                    data-confirm-button="{{ __('common.ok_9ce3bd') }}"
                                                @else
                                                    data-confirm-title="{{ __('products.delete_product_513a0e') }}"
                                                    data-confirm-message="Delete {{ $product->localized('name') }}? It will be hidden from products."
                                                    data-confirm-button="{{ __('common.delete_f6fdbe') }}"
                                                @endif
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="danger-button" type="submit">{{ __('common.delete_f6fdbe') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('products.partials.pagination', ['paginator' => $products])
            @endif
        </section>
    </section>

    <script>
        (function () {
            if (window.productListingAjaxBound) {
                return;
            }

            window.productListingAjaxBound = true;
            var activeRequest = null;
            var searchTimer = null;

            function showListingLoader() {
                var listing = document.querySelector('[data-product-listing]');
                var loader = document.querySelector('[data-product-listing-loader]');

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

            function loadListing(url, pushState) {
                var workspace = document.querySelector('.product-workspace');

                if (! workspace) {
                    window.location.href = url;
                    return;
                }

                if (activeRequest) {
                    activeRequest.abort();
                }

                var requestController = new AbortController();
                activeRequest = requestController;
                showListingLoader();

                fetch(url, {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: requestController.signal
                }).then(function (response) {
                    if (! response.ok) {
                        throw new Error(window.t("validation.unable_to_load_products_051a4c"));
                    }

                    return response.text();
                }).then(function (html) {
                    var page = new DOMParser().parseFromString(html, 'text/html');
                    var nextWorkspace = page.querySelector('.product-workspace');

                    if (! nextWorkspace) {
                        throw new Error(window.t("validation.product_listing_was_not_found_c7441e"));
                    }

                    workspace.replaceWith(nextWorkspace);
                    document.title = page.title || document.title;

                    if (pushState !== false) {
                        window.history.pushState({ productListing: true }, '', url);
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
                var form = event.target.closest('[data-product-filters]');

                if (! form) {
                    return;
                }

                event.preventDefault();
                loadListing(filterUrl(form), true);
            });

            document.addEventListener('change', function (event) {
                var field = event.target.closest('[data-product-filters] [data-auto-filter]');

                if (! field || field.type === 'search') {
                    return;
                }

                loadListing(filterUrl(field.form), true);
            });

            document.addEventListener('input', function (event) {
                var search = event.target.closest('[data-product-filters] input[type="search"]');

                if (! search) {
                    return;
                }

                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(function () {
                    loadListing(filterUrl(search.form), true);
                }, 350);
            });

            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-product-listing-link], .product-pagination a');

                if (! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                loadListing(link.href, true);
            });

            window.addEventListener('popstate', function () {
                loadListing(window.location.href, false);
            });
        })();
    </script>
@endsection
