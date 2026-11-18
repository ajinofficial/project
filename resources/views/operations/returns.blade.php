@extends('layouts.admin', ['title' => __('orders.returns_9582a0')])

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

    @include('operations.partials.overview', [
        'title' => __('orders.returns_9582a0'),
        'description' => __('products.manage_returned_items_damaged_stock_and_stock_reservations_49da60'),
        'action' => __('orders.process_return_e6d5e0'),
        'formId' => 'return-entry',
    ])

    <section class="ops-grid">
        <article class="admin-section" id="return-entry">
            <div class="section-title"><div><p class="eyebrow">{{ __('products.stock_flow_c72fc0') }}</p><h2>{{ __('orders.returns_and_reservations_6f7956') }}</h2></div></div>
            <form class="product-form return-form" method="POST" action="{{ route('returns.store') }}" data-return-form>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>{{ __('orders.check_the_return_details_a0e522') }}</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <label class="return-field searchable-select" data-searchable-select>
                    <span>{{ __('products.product_dd3b86') }}</span>
                    <input
                        type="search"
                        class="return-form-control searchable-select__search"
                        placeholder="{{ __('products.search_or_select_product_8ab7d7') }}"
                        autocomplete="off"
                        role="combobox"
                        aria-label="{{ __('products.search_products_4412bf') }}"
                        aria-expanded="false"
                        data-searchable-select-input
                    >
                    <div class="searchable-select__menu" role="listbox" data-searchable-select-menu hidden></div>
                    <select class="searchable-select__native" name="product_id" required tabindex="-1" aria-hidden="true">
                        <option value="">{{ __('products.select_product_596d90') }}</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ __('common.return_product_label', ['name' => $product->localized('name'), 'sku' => $product->sku ?: 'SKU-'.$product->id, 'available' => $product->available_stock, 'reserved' => $product->reserved_stock, 'damaged' => $product->damaged_stock]) }}</option>
                        @endforeach
                    </select>
                    <small class="searchable-select__status" data-searchable-select-status aria-live="polite"></small>
                    @error('product_id') <small>{{ $message }}</small> @enderror
                </label>

                <div class="field-grid return-field-grid">
                    <label class="return-field">
                        <span>{{ __('orders.return_type_41b1fb') }}</span>
                        <select class="return-form-control" name="return_type" required>
                            <option value="">{{ __('orders.select_return_type_56f054') }}</option>
                            <option value="sales_return" @selected(old('return_type', 'sales_return') === 'sales_return')>{{ __('products.sales_return_stock_increases_ef91d7') }}</option>
                            <option value="purchase_return" @selected(old('return_type') === 'purchase_return')>{{ __('products.purchase_return_stock_decreases_b8880b') }}</option>
                            <option value="damaged_return" @selected(old('return_type') === 'damaged_return')>{{ __('products.damaged_return_added_to_damaged_stock_7741c4') }}</option>
                            <option value="reserve_stock" @selected(old('return_type') === 'reserve_stock')>{{ __('products.reserve_stock_sellable_stock_decreases_303adc') }}</option>
                            <option value="release_reserved" @selected(old('return_type') === 'release_reserved')>{{ __('products.release_reserved_sellable_stock_increases_eca294') }}</option>
                        </select>
                        @error('return_type') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="return-field">
                        <span>{{ __('common.quantity_44f6af') }}</span>
                        <input class="return-form-control" type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required data-replace-on-focus>
                        @error('quantity') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="return-field">
                    <span>{{ __('common.notes_704400') }}</span>
                    <textarea class="return-form-control" name="notes" rows="3" placeholder="{{ __('orders.reason_invoice_reference_or_condition_notes_cf5161') }}">{{ old('notes') }}</textarea>
                    @error('notes') <small>{{ $message }}</small> @enderror
                </label>

                <button class="product-save-button" type="submit" data-return-submit>
                    <span class="product-save-button__idle">{{ __('products.process_stock_flow_b85d6a') }}</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        {{ __('common.processing_e63451') }}
                    </span>
                </button>
            </form>
        </article>

        <article class="admin-section return-listing-panel" data-return-listing>
            <div class="section-title"><div><p class="eyebrow">{{ __('common.audit_log_3cfc5f') }}</p><h2>{{ __('orders.return_and_reservation_movements_a8cd07') }}</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form return-filter-form" method="GET" action="{{ route('returns.index') }}" data-return-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('products.search_product_sku_notes_eb334b') }}" data-return-search>
                    <select name="type" aria-label="{{ __('orders.return_type_41b1fb') }}" data-return-search>
                        <option value="">{{ __('orders.all_returns_c13fe7') }}</option>
                        <option value="sales_return" @selected(request('type') === 'sales_return')>{{ __('orders.sales_return_81894c') }}</option>
                        <option value="purchase_return" @selected(request('type') === 'purchase_return')>{{ __('orders.purchase_return_1c337e') }}</option>
                        <option value="damaged_return" @selected(request('type') === 'damaged_return')>{{ __('orders.damaged_return_10f07f') }}</option>
                        <option value="reserve_stock" @selected(request('type') === 'reserve_stock')>{{ __('products.reserved_stock_ba252d') }}</option>
                        <option value="release_reserved" @selected(request('type') === 'release_reserved')>{{ __('common.released_reservation_067daf') }}</option>
                    </select>
                    <select name="per_page" aria-label="{{ __('orders.returns_per_page_487073') }}" data-return-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ __('common.per_page', ['count' => $option]) }}</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('returns.index') }}" data-return-listing-link>{{ __('common.clear_719ea3') }}</a>
                </form>
            </div>

            <div class="return-listing-results" data-return-listing-results>
                <div class="product-listing-loader" data-return-listing-loader aria-live="polite" aria-hidden="true"><span aria-hidden="true"></span><strong>{{ __('orders.loading_returns_ad8f5e') }}</strong></div>
                <div class="table-wrap"><table class="admin-table return-table">
                    <thead><tr><th>{{ __('products.product_dd3b86') }}</th><th>{{ __('common.type_3deb74') }}</th><th>{{ __('common.quantity_44f6af') }}</th><th>{{ __('products.stock_after_4b2cb3') }}</th><th>{{ __('common.notes_704400') }}</th></tr></thead>
                    <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td><strong>{{ $movement->product?->localized('name') ?? __('products.product_dd3b86') }}</strong></td>
                            <td>
                                <span @class([
                                    'return-type-badge',
                                    'is-sales' => $movement->type === 'sales_return',
                                    'is-purchase' => $movement->type === 'purchase_return',
                                    'is-damaged' => $movement->type === 'damaged_return',
                                    'is-purchase' => in_array($movement->type, ['reserve_stock', 'release_reserved'], true),
                                ])>
                                    {{ __(str_replace('_', ' ', ucfirst($movement->type))) }}
                                </span>
                            </td>
                            <td>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                            <td>{{ $movement->stock_after }}</td>
                            <td>{{ $movement->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ request()->filled('search') || request()->filled('type') ? __('orders.no_return_movements_match_the_current_filters_d1e1cc') : __('orders.no_returns_processed_yet_d46c65') }}</td></tr>
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
                    .then(function (response) { if (!response.ok) throw new Error(window.t("validation.unable_to_load_returns_91dc73")); return response.text(); })
                    .then(function (html) {
                        var page = new DOMParser().parseFromString(html, 'text/html');
                        var next = page.querySelector('[data-return-listing]');
                        if (!next) throw new Error(window.t("validation.return_listing_was_not_found_365e42"));
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

                    return labelText ? labelText.textContent.trim() : window.t("common.this_field_f89fd4");
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
                        ? window.t('validation.required', {attribute: fieldLabel(field)})
                        : window.localizedValidationMessage(field);
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
