@extends('layouts.admin', [
    'title' => 'Products',
])

@section('content')
    <section class="product-workspace">
        <header class="product-page-head">
            <div>
                <p class="eyebrow">Products</p>
                <h1>Products</h1>
                <span>Manage item availability, pricing, and stock movement.</span>
            </div>
            <div class="product-head-actions">
                <a class="ghost-button" href="{{ route('products.index', ['stock' => 'low']) }}" data-product-listing-link>Low stock</a>
                <a class="primary-link" href="{{ route('products.create') }}">Add product</a>
            </div>
        </header>

        <section class="product-stat-grid" aria-label="Product metrics">
            <a class="product-stat" href="{{ route('products.index') }}" data-product-listing-link>
                <span>Total Products</span>
                <strong>{{ number_format($stats['total']) }}</strong>
                <small>All catalog items</small>
            </a>
            <a class="product-stat" href="{{ route('products.index', ['status' => 'active']) }}" data-product-listing-link>
                <span>Active Items</span>
                <strong>{{ number_format($stats['active']) }}</strong>
                <small>Ready to sell</small>
            </a>
            <a class="product-stat" href="{{ route('products.index', ['stock' => 'low']) }}" data-product-listing-link>
                <span>Low Stock</span>
                <strong>{{ number_format($stats['low']) }}</strong>
                <small>Needs reorder</small>
            </a>
            <a class="product-stat" href="{{ route('products.index', ['stock' => 'out']) }}" data-product-listing-link>
                <span>Stock Value</span>
                <strong>&#8377;{{ number_format($stats['value'], 0) }}</strong>
                <small>{{ number_format($stats['out']) }} unavailable</small>
            </a>
        </section>

        <section class="v-panel product-management-panel" data-product-listing>
            <div class="product-toolbar">
                <form class="product-filter-form" method="GET" action="{{ route('products.index') }}" data-product-filters>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search product, SKU, category" data-auto-filter>
                    <select name="status" data-auto-filter>
                        <option value="">All status</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                    </select>
                    <select name="stock" data-auto-filter>
                        <option value="">All stock</option>
                        <option value="healthy" @selected(request('stock') === 'healthy')>Healthy</option>
                        <option value="low" @selected(request('stock') === 'low')>Low</option>
                        <option value="out" @selected(request('stock') === 'out')>Out</option>
                    </select>
                    <select name="sort" data-auto-filter>
                        <option value="">Newest</option>
                        <option value="name" @selected(request('sort') === 'name')>Name</option>
                        <option value="stock_low" @selected(request('sort') === 'stock_low')>Stock low</option>
                        <option value="stock_high" @selected(request('sort') === 'stock_high')>Stock high</option>
                        <option value="price_high" @selected(request('sort') === 'price_high')>Price high</option>
                        <option value="price_low" @selected(request('sort') === 'price_low')>Price low</option>
                    </select>
                    <select name="per_page" aria-label="Products per page" data-auto-filter>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('products.index') }}" data-product-listing-link>Clear</a>
                </form>
            </div>

            <div class="product-listing-loader" data-product-listing-loader aria-live="polite" aria-hidden="true">
                <span aria-hidden="true"></span>
                <strong>Loading products</strong>
            </div>

            @if ($products->isEmpty())
                <div class="empty-state product-empty">
                    @if ($hasActiveFilters)
                        <h3>No matching products</h3>
                        <p>No products match the current filters. Clear filters to see all products.</p>
                        <a class="product-clear-filter" href="{{ route('products.index') }}" data-product-listing-link>Clear filters</a>
                    @else
                        <h3>No products yet</h3>
                        <p>Add your first product to start managing your product catalog.</p>
                        <a class="primary-link" href="{{ route('products.create') }}">Add product</a>
                    @endif
                </div>
            @else
                <div class="table-wrap">
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Product ID</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Health</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                @php
                                    $stockLabel = $product->inventory === 0 ? 'Out' : ($product->inventory <= $product->minimum_stock_level ? 'Low' : 'Healthy');
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
                                                <strong>{{ $product->name }}</strong>
                                                <small>{{ $product->brand ?: $product->category ?: 'Uncategorized' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $product->sku ?: 'SKU-'.$product->id }}</td>
                                    <td>
                                        <strong>&#8377;{{ number_format($product->price, 2) }}</strong>
                                        @if ($product->compare_at_price)
                                            <small class="muted-line">&#8377;{{ number_format($product->compare_at_price, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($product->inventory) }}</strong>
                                        <span class="stock-meter"><i style="width: {{ $stockWidth }}%"></i></span>
                                    </td>
                                    <td><span class="status-chip {{ $stockClass }}">{{ $stockLabel }}</span></td>
                                    <td><span class="status-chip status-{{ $product->status }}">{{ ucfirst($product->status) }}</span></td>
                                    <td>
                                        <div class="inventory-actions">
                                            <a href="{{ route('products.edit', $product) }}">Edit</a>
                                            <form method="POST" action="{{ route('products.update', $product) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="name" value="{{ $product->name }}">
                                                <input type="hidden" name="sku" value="{{ $product->sku }}">
                                                <input type="hidden" name="barcode" value="{{ $product->barcode }}">
                                                <input type="hidden" name="category" value="{{ $product->category }}">
                                                <input type="hidden" name="brand" value="{{ $product->brand }}">
                                                <input type="hidden" name="supplier_id" value="{{ $product->supplier_id }}">
                                                <input type="hidden" name="purchase_price" value="{{ $product->purchase_price }}">
                                                <input type="hidden" name="price" value="{{ $product->price }}">
                                                <input type="hidden" name="minimum_stock_level" value="{{ $product->minimum_stock_level }}">
                                                <input type="hidden" name="reserved_stock" value="{{ $product->reserved_stock }}">
                                                <input type="hidden" name="damaged_stock" value="{{ $product->damaged_stock }}">
                                                <input type="hidden" name="returned_stock" value="{{ $product->returned_stock }}">
                                                <input type="hidden" name="image_url" value="{{ $product->image_url }}">
                                                <input type="hidden" name="description" value="{{ $product->description }}">
                                                <input type="hidden" name="status" value="{{ $product->status === 'active' ? 'archived' : 'active' }}">
                                                <button type="submit">{{ $product->status === 'active' ? 'Archive' : 'Activate' }}</button>
                                            </form>
                                            <form
                                                method="POST"
                                                action="{{ route('products.destroy', $product) }}"
                                                data-confirm
                                                @if ($product->status === 'active')
                                                    data-confirm-blocked="true"
                                                    data-confirm-title="Cannot delete active product"
                                                    data-confirm-message="Active products cannot be deleted. Archive {{ $product->name }} first."
                                                    data-confirm-button="OK"
                                                @else
                                                    data-confirm-title="Delete product"
                                                    data-confirm-message="Delete {{ $product->name }}? It will be hidden from products."
                                                    data-confirm-button="Delete"
                                                @endif
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="danger-button" type="submit">Delete</button>
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
            var form = document.querySelector('[data-product-filters]');
            var listing = document.querySelector('[data-product-listing]');
            var loader = document.querySelector('[data-product-listing-loader]');

            if (! form) {
                return;
            }

            function showListingLoader() {
                if (! listing || ! loader) {
                    return;
                }

                listing.classList.add('is-loading');
                loader.setAttribute('aria-hidden', 'false');
            }

            function submitFilters() {
                showListingLoader();

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                form.submit();
            }

            form.querySelectorAll('[data-auto-filter]').forEach(function (field) {
                field.addEventListener('change', submitFilters);
            });

            form.addEventListener('submit', function () {
                showListingLoader();

                form.querySelectorAll('input, select').forEach(function (field) {
                    if (field.value === '') {
                        field.disabled = true;
                    }
                });
            });

            document.querySelectorAll('[data-product-listing-link], .product-pagination a').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }

                    showListingLoader();
                });
            });

            if (listing) {
                listing.querySelectorAll('form:not([data-confirm])').forEach(function (listingForm) {
                    listingForm.addEventListener('submit', showListingLoader);
                });
            }
        })();
    </script>
@endsection
