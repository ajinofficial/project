@extends('layouts.admin', ['title' => 'Returns'])

@section('content')
    <style>
        .return-listing-results { position: relative; min-height: 160px; }
        .return-listing-results .product-listing-loader { inset: 0; }
        .return-listing-panel.is-loading .table-wrap,
        .return-listing-panel.is-loading .product-pagination { opacity: .38; pointer-events: none; }
        .return-listing-panel.is-loading .product-listing-loader { display: grid; }
        .return-form {
            gap: 12px;
        }

        .return-form .return-field {
            display: grid;
            gap: 6px;
        }

        .return-form .return-field span {
            margin: 0;
            color: #17201a;
            font-size: var(--text-sm);
            font-weight: 800;
        }

        .return-form .return-field-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .return-form .return-form-control {
            width: 100% !important;
            min-height: 42px !important;
            border: 1px solid #dfe6db !important;
            border-radius: 8px !important;
            padding: 9px 12px !important;
            color: #050703 !important;
            background: #ffffff !important;
            font-size: var(--text-base) !important;
            line-height: 1.4 !important;
            box-shadow: none !important;
        }

        .return-form textarea.return-form-control {
            min-height: 78px !important;
            resize: vertical;
        }

        .return-form .return-form-control:focus {
            border-color: #2f80ed !important;
            box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.14) !important;
            outline: none;
        }

        .return-form .return-form-control[aria-invalid="true"] {
            border-color: #b42318 !important;
            background: #fff8f7 !important;
        }

        .return-form small {
            color: #b42318;
            font-size: var(--text-xs);
            font-weight: 700;
        }

        .return-table {
            min-width: 820px;
        }

        .return-table th,
        .return-table td {
            height: auto;
            padding: 9px 10px;
        }

        .return-type-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: var(--text-xs);
            font-weight: 800;
            white-space: nowrap;
        }

        .return-type-badge.is-sales {
            color: #166534;
            background: #dcfce7;
        }

        .return-type-badge.is-purchase {
            color: #92400e;
            background: #fef3c7;
        }

        .return-type-badge.is-damaged {
            color: #991b1b;
            background: #fee2e2;
        }
    </style>

    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Stock flow</p><h2>Returns and reservations</h2></div></div>
            <form class="product-form return-form" method="POST" action="{{ route('returns.store') }}" data-return-form>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>Check the return details</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <label class="return-field searchable-select" data-searchable-select>
                    <span>Product</span>
                    <input
                        type="search"
                        class="return-form-control searchable-select__search"
                        placeholder="Search or select product"
                        autocomplete="off"
                        role="combobox"
                        aria-label="Search products"
                        aria-expanded="false"
                        data-searchable-select-input
                    >
                    <div class="searchable-select__menu" role="listbox" data-searchable-select-menu hidden></div>
                    <select class="searchable-select__native" name="product_id" required tabindex="-1" aria-hidden="true">
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }} - {{ $product->sku ?: 'SKU-'.$product->id }} - Available {{ $product->available_stock }} - Reserved {{ $product->reserved_stock }} - Damaged {{ $product->damaged_stock }}</option>
                        @endforeach
                    </select>
                    <small class="searchable-select__status" data-searchable-select-status aria-live="polite"></small>
                    @error('product_id') <small>{{ $message }}</small> @enderror
                </label>

                <div class="field-grid return-field-grid">
                    <label class="return-field">
                        <span>Return type</span>
                        <select class="return-form-control" name="return_type" required>
                            <option value="">Select return type</option>
                            <option value="sales_return" @selected(old('return_type', 'sales_return') === 'sales_return')>Sales return: stock increases</option>
                            <option value="purchase_return" @selected(old('return_type') === 'purchase_return')>Purchase return: stock decreases</option>
                            <option value="damaged_return" @selected(old('return_type') === 'damaged_return')>Damaged return: added to damaged stock</option>
                            <option value="reserve_stock" @selected(old('return_type') === 'reserve_stock')>Reserve stock: sellable stock decreases</option>
                            <option value="release_reserved" @selected(old('return_type') === 'release_reserved')>Release reserved: sellable stock increases</option>
                        </select>
                        @error('return_type') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="return-field">
                        <span>Quantity</span>
                        <input class="return-form-control" type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required data-replace-on-focus>
                        @error('quantity') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="return-field">
                    <span>Notes</span>
                    <textarea class="return-form-control" name="notes" rows="3" placeholder="Reason, invoice reference, or condition notes">{{ old('notes') }}</textarea>
                    @error('notes') <small>{{ $message }}</small> @enderror
                </label>

                <button class="product-save-button" type="submit" data-return-submit>
                    <span class="product-save-button__idle">Process stock flow</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        Processing
                    </span>
                </button>
            </form>
        </article>

        <article class="admin-section return-listing-panel" data-return-listing>
            <div class="section-title"><div><p class="eyebrow">Audit log</p><h2>Return and reservation movements</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form return-filter-form" method="GET" action="{{ route('returns.index') }}" data-return-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search product, SKU, notes" data-return-search>
                    <select name="type" aria-label="Return type" data-return-search>
                        <option value="">All returns</option>
                        <option value="sales_return" @selected(request('type') === 'sales_return')>Sales return</option>
                        <option value="purchase_return" @selected(request('type') === 'purchase_return')>Purchase return</option>
                        <option value="damaged_return" @selected(request('type') === 'damaged_return')>Damaged return</option>
                        <option value="reserve_stock" @selected(request('type') === 'reserve_stock')>Reserved stock</option>
                        <option value="release_reserved" @selected(request('type') === 'release_reserved')>Released reservation</option>
                    </select>
                    <select name="per_page" aria-label="Returns per page" data-return-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('returns.index') }}" data-return-listing-link>Clear</a>
                </form>
            </div>

            <div class="return-listing-results" data-return-listing-results>
                <div class="product-listing-loader" data-return-listing-loader aria-live="polite" aria-hidden="true"><span aria-hidden="true"></span><strong>Loading returns</strong></div>
                <div class="table-wrap"><table class="admin-table return-table">
                    <thead><tr><th>Product</th><th>Type</th><th>Quantity</th><th>Stock after</th><th>Notes</th></tr></thead>
                    <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td><strong>{{ $movement->product->name ?? 'Product' }}</strong></td>
                            <td>
                                <span @class([
                                    'return-type-badge',
                                    'is-sales' => $movement->type === 'sales_return',
                                    'is-purchase' => $movement->type === 'purchase_return',
                                    'is-damaged' => $movement->type === 'damaged_return',
                                    'is-purchase' => in_array($movement->type, ['reserve_stock', 'release_reserved'], true),
                                ])>
                                    {{ str_replace('_', ' ', ucfirst($movement->type)) }}
                                </span>
                            </td>
                            <td>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                            <td>{{ $movement->stock_after }}</td>
                            <td>{{ $movement->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ request()->filled('search') || request()->filled('type') ? 'No return movements match the current filters.' : 'No returns processed yet.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                @include('products.partials.pagination', ['paginator' => $movements, 'itemLabel' => 'returns'])
            </div>
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var returnRequest = null;
            var returnTimer = null;

            function returnUrl(form) {
                var params = new URLSearchParams(new FormData(form));
                Array.from(params.keys()).forEach(function (key) { if (params.get(key) === '') params.delete(key); });
                return form.action + (params.toString() ? '?' + params.toString() : '');
            }

            function loadReturns(url, pushState) {
                var listing = document.querySelector('[data-return-listing]');
                if (!listing) { window.location.href = url; return; }
                if (returnRequest) returnRequest.abort();
                var controller = new AbortController();
                returnRequest = controller;
                listing.classList.add('is-loading');
                listing.querySelector('[data-return-listing-loader]')?.setAttribute('aria-hidden', 'false');

                fetch(url, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                    .then(function (response) { if (!response.ok) throw new Error('Unable to load returns.'); return response.text(); })
                    .then(function (html) {
                        var page = new DOMParser().parseFromString(html, 'text/html');
                        var next = page.querySelector('[data-return-listing]');
                        if (!next) throw new Error('Return listing was not found.');
                        listing.replaceWith(next);
                        document.title = page.title || document.title;
                        if (pushState !== false) window.history.pushState({ returnListing: true }, '', url);
                    })
                    .catch(function (error) { if (error.name !== 'AbortError') window.location.href = url; })
                    .finally(function () { if (returnRequest === controller) returnRequest = null; });
            }

            document.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-return-search-form]');
                if (form) { event.preventDefault(); loadReturns(returnUrl(form), true); }
            });
            document.addEventListener('change', function (event) {
                var select = event.target.closest('[data-return-search-form] select');
                if (select) loadReturns(returnUrl(select.form), true);
            });
            document.addEventListener('input', function (event) {
                var search = event.target.closest('[data-return-search-form] input[type="search"]');
                if (!search) return;
                clearTimeout(returnTimer);
                returnTimer = setTimeout(function () { loadReturns(returnUrl(search.form), true); }, 350);
            });
            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-return-listing-link], [data-return-listing] .product-pagination a');
                if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                event.preventDefault();
                loadReturns(link.href, true);
            });
            window.addEventListener('popstate', function () {
                if (window.location.pathname === new URL('{{ route('returns.index') }}').pathname) loadReturns(window.location.href, false);
            });

            document.querySelectorAll('[data-return-form]').forEach(function (form) {
                form.noValidate = true;

                function fieldLabel(field) {
                    var label = field.closest('label');
                    var labelText = label ? label.querySelector('span') : null;

                    return labelText ? labelText.textContent.trim() : 'This field';
                }

                function existingErrorElement(field) {
                    if (field.nextElementSibling && field.nextElementSibling.matches('[data-validation-error]')) {
                        return field.nextElementSibling;
                    }

                    return null;
                }

                function errorElement(field) {
                    var existingError = existingErrorElement(field);

                    if (existingError) {
                        return existingError;
                    }

                    var error = document.createElement('small');
                    error.setAttribute('data-validation-error', '');
                    error.setAttribute('role', 'alert');
                    field.insertAdjacentElement('afterend', error);

                    return error;
                }

                function validateField(field) {
                    var error = existingErrorElement(field);

                    if (!field.willValidate) {
                        return true;
                    }

                    if (field.checkValidity()) {
                        if (error) {
                            error.textContent = '';
                            error.hidden = true;
                        }

                        field.removeAttribute('aria-invalid');

                        return true;
                    }

                    error = errorElement(field);
                    error.textContent = field.validity.valueMissing
                        ? fieldLabel(field) + ' is required.'
                        : field.validationMessage;
                    error.hidden = false;
                    field.setAttribute('aria-invalid', 'true');

                    return false;
                }

                form.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.addEventListener('input', function () {
                        validateField(field);
                    });

                    field.addEventListener('change', function () {
                        validateField(field);
                    });
                });

                form.querySelectorAll('[data-replace-on-focus]').forEach(function (field) {
                    field.addEventListener('focus', function () {
                        field.select();
                        field.dataset.valueSelected = 'true';
                    });

                    field.addEventListener('mouseup', function (event) {
                        if (field.dataset.valueSelected !== 'true') {
                            return;
                        }

                        event.preventDefault();
                        delete field.dataset.valueSelected;
                    });
                });

                form.addEventListener('submit', function (event) {
                    var firstInvalid = null;
                    var button = form.querySelector('[data-return-submit]');

                    form.querySelectorAll('input, select, textarea').forEach(function (field) {
                        if (!validateField(field) && !firstInvalid) {
                            firstInvalid = field;
                        }
                    });

                    if (!firstInvalid) {
                        if (button) {
                            button.disabled = true;
                            button.classList.add('is-loading');
                            button.setAttribute('aria-busy', 'true');
                        }

                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    firstInvalid.focus();
                });
            });
        });
    </script>

    @include('partials.searchable-select-script')
@endsection
