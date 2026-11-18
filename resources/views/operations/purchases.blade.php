@extends('layouts.admin', ['title' => __('orders.purchases_6c4e94')])

@section('content')
    <style>
        .purchase-bill-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .purchase-bill-grid > label {
            min-width: 0;
        }

        .purchase-bill-grid .is-wide {
            grid-column: 1 / -1;
        }

        .purchase-history-items {
            display: grid;
            gap: 4px;
        }

        .purchase-listing-panel.is-loading .table-wrap,
        .purchase-listing-panel.is-loading .product-pagination {
            opacity: 0.38;
            pointer-events: none;
        }

        .purchase-listing-results {
            position: relative;
            min-height: 160px;
        }

        .purchase-listing-results .product-listing-loader {
            inset: 0;
        }

        .purchase-listing-panel.is-loading .product-listing-loader {
            display: grid;
        }

        @media (max-width: 700px) {
            .purchase-bill-grid {
                grid-template-columns: 1fr;
            }

            .purchase-filter-form {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @include('operations.partials.overview', [
        'title' => __('orders.purchases_6c4e94'),
        'description' => __('products.manage_supplier_bills_purchase_totals_and_incoming_stock_e2ebaa'),
        'action' => __('orders.receive_purchase_42ecb7'),
        'formId' => 'purchase-entry',
    ])

    <section class="ops-grid">
        <article class="admin-section" id="purchase-entry">
            <div class="section-title"><div><p class="eyebrow">{{ __('products.stock_in_93aed6') }}</p><h2>{{ __('orders.receive_purchase_42ecb7') }}</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('purchases.store') }}" data-purchase-form>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>{{ __('orders.check_the_purchase_details_88cfbb') }}</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div class="purchase-bill-grid">
                    <label>
                        <span>{{ __('orders.supplier_55edd4') }}</span>
                        <select name="supplier_id">
                            <option value="">{{ __('orders.no_supplier_d88131') }}</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>{{ __('orders.bill_date_7917c7') }}</span>
                        <input type="date" name="bill_date" value="{{ old('bill_date', now()->toDateString()) }}" required data-date-picker>
                        @error('bill_date') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="is-wide">
                        <span>{{ __('orders.supplier_invoice_no_6232f2') }}</span>
                        <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}" maxlength="120" placeholder="{{ __('orders.bill_invoice_number_from_supplier_b3e7cf') }}" required @if ($errors->has('supplier_invoice_number')) aria-invalid="true" @endif>
                        @error('supplier_invoice_number') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>{{ __('common.tax_amount_422e90') }}</span>
                        <input type="number" name="tax_amount" min="0" step="0.01" value="{{ old('tax_amount', 0) }}" data-replace-on-focus>
                        @error('tax_amount') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>{{ __('common.total_amount_c1ed4a') }}</span>
                        <input type="number" name="total_amount" min="0.01" step="0.01" value="{{ old('total_amount', 0) }}" required data-replace-on-focus>
                        @error('total_amount') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <button class="product-save-button" type="submit" data-purchase-submit>
                    <span class="product-save-button__idle">{{ __('orders.save_bill_657e60') }}</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        {{ __('common.saving_369c53') }}
                    </span>
                </button>
            </form>
        </article>

        <article class="admin-section purchase-listing-panel" data-purchase-listing>
            <div class="section-title"><div><p class="eyebrow">{{ __('orders.purchase_history_575453') }}</p><h2>{{ __('orders.recent_purchase_orders_220243') }}</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form billing-search-form purchase-filter-form" method="GET" action="{{ route('purchases.index') }}" data-purchase-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('orders.search_po_invoice_supplier_eda1e9') }}" data-purchase-search>
                    <select name="per_page" aria-label="{{ __('orders.purchase_orders_per_page_9e6fc0') }}" data-purchase-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ __('common.per_page', ['count' => $option]) }}</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('purchases.index') }}" data-purchase-listing-link>{{ __('common.clear_719ea3') }}</a>
                </form>
            </div>

            <div class="purchase-listing-results" data-purchase-listing-results>
                <div class="product-listing-loader" data-purchase-listing-loader aria-live="polite" aria-hidden="true">
                    <span aria-hidden="true"></span>
                    <strong>{{ __('orders.loading_purchase_orders_5bb19e') }}</strong>
                </div>
                <div class="table-wrap"><table class="admin-table"><thead><tr><th>PO</th><th>{{ __('orders.bill_date_7917c7') }}</th><th>{{ __('orders.invoice_no_ff0dda') }}</th><th>{{ __('orders.supplier_55edd4') }}</th><th>{{ __('common.tax_9be70f') }}</th><th>{{ __('common.total_b25928') }}</th></tr></thead><tbody>
                    @forelse ($orders as $order)
                        <tr><td>{{ $order->order_number }}</td><td>{{ $order->received_at?->translatedFormat('d M Y') ?? '-' }}</td><td>{{ $order->supplier_invoice_number ?: '-' }}</td><td>{{ $order->supplier->name ?? '-' }}</td><td>&#8377;{{ number_format($order->tax_amount, 2) }}</td><td>&#8377;{{ number_format($order->total_amount, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="6">{{ request()->filled('search') ? __('orders.no_purchase_orders_match_the_current_search_2f10f4') : __('orders.no_purchase_orders_yet_a2aedb') }}</td></tr>
                    @endforelse
                </tbody></table></div>
                @include('products.partials.pagination', ['paginator' => $orders, 'itemLabel' => 'purchase orders'])
            </div>
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var purchaseListingRequest = null;
            var purchaseSearchTimer = null;

            function purchaseUrl(form) {
                var params = new URLSearchParams(new FormData(form));

                Array.from(params.keys()).forEach(function (key) {
                    if (params.get(key) === '') {
                        params.delete(key);
                    }
                });

                var query = params.toString();
                return form.action + (query ? '?' + query : '');
            }

            function showPurchaseListingLoader() {
                var listing = document.querySelector('[data-purchase-listing]');
                var loader = document.querySelector('[data-purchase-listing-loader]');

                if (listing && loader) {
                    listing.classList.add('is-loading');
                    loader.setAttribute('aria-hidden', 'false');
                }
            }

            function loadPurchaseListing(url, pushState) {
                var listing = document.querySelector('[data-purchase-listing]');

                if (!listing) {
                    window.location.href = url;
                    return;
                }

                if (purchaseListingRequest) {
                    purchaseListingRequest.abort();
                }

                var requestController = new AbortController();
                purchaseListingRequest = requestController;
                showPurchaseListingLoader();

                fetch(url, {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: requestController.signal
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error(window.t("validation.unable_to_load_purchase_orders_68a222"));
                    }

                    return response.text();
                }).then(function (html) {
                    var page = new DOMParser().parseFromString(html, 'text/html');
                    var nextListing = page.querySelector('[data-purchase-listing]');

                    if (!nextListing) {
                        throw new Error(window.t("validation.purchase_listing_was_not_found_f6ac12"));
                    }

                    listing.replaceWith(nextListing);
                    document.title = page.title || document.title;

                    if (pushState !== false) {
                        window.history.pushState({ purchaseListing: true }, '', url);
                    }
                }).catch(function (error) {
                    if (error.name !== 'AbortError') {
                        window.location.href = url;
                    }
                }).finally(function () {
                    if (purchaseListingRequest === requestController) {
                        purchaseListingRequest = null;
                    }
                });
            }

            document.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-purchase-search-form]');

                if (form) {
                    event.preventDefault();
                    loadPurchaseListing(purchaseUrl(form), true);
                }
            });

            document.addEventListener('change', function (event) {
                var select = event.target.closest('[data-purchase-search-form] select[data-purchase-search]');

                if (select) {
                    loadPurchaseListing(purchaseUrl(select.form), true);
                }
            });

            document.addEventListener('input', function (event) {
                var search = event.target.closest('[data-purchase-search-form] input[type="search"]');

                if (!search) {
                    return;
                }

                window.clearTimeout(purchaseSearchTimer);
                purchaseSearchTimer = window.setTimeout(function () {
                    loadPurchaseListing(purchaseUrl(search.form), true);
                }, 350);
            });

            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-purchase-listing-link], [data-purchase-listing] .product-pagination a');

                if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                loadPurchaseListing(link.href, true);
            });

            window.addEventListener('popstate', function () {
                if (window.location.pathname === new URL('{{ route('purchases.index') }}').pathname) {
                    loadPurchaseListing(window.location.href, false);
                }
            });

            document.querySelectorAll('[data-purchase-form]').forEach(function (form) {
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

                function bindField(field) {
                    field.addEventListener('input', function () {
                        validateField(field);
                    });

                    field.addEventListener('change', function () {
                        validateField(field);
                    });
                }

                function bindReplaceOnFocus(field) {
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
                }

                form.querySelectorAll('input, select, textarea').forEach(bindField);
                form.querySelectorAll('[data-replace-on-focus]').forEach(bindReplaceOnFocus);

                form.addEventListener('submit', function (event) {
                    var firstInvalid = null;
                    var button = form.querySelector('[data-purchase-submit]');

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
@endsection
