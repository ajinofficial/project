@extends('layouts.admin', ['title' => 'Purchases'])

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

    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Stock in</p><h2>Receive purchase</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('purchases.store') }}" data-purchase-form>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>Check the purchase details</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div class="purchase-bill-grid">
                    <label>
                        <span>Supplier</span>
                        <select name="supplier_id">
                            <option value="">No supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Bill date</span>
                        <input type="date" name="bill_date" value="{{ old('bill_date', now()->toDateString()) }}" required data-date-picker>
                        @error('bill_date') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="is-wide">
                        <span>Supplier invoice no.</span>
                        <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}" maxlength="120" placeholder="Bill / invoice number from supplier" required @if ($errors->has('supplier_invoice_number')) aria-invalid="true" @endif>
                        @error('supplier_invoice_number') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Tax amount</span>
                        <input type="number" name="tax_amount" min="0" step="0.01" value="{{ old('tax_amount', 0) }}" data-replace-on-focus>
                        @error('tax_amount') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Total amount</span>
                        <input type="number" name="total_amount" min="0.01" step="0.01" value="{{ old('total_amount', 0) }}" required data-replace-on-focus>
                        @error('total_amount') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <button class="product-save-button" type="submit" data-purchase-submit>
                    <span class="product-save-button__idle">Save bill</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        Saving
                    </span>
                </button>
            </form>
        </article>

        <article class="admin-section purchase-listing-panel" data-purchase-listing>
            <div class="section-title"><div><p class="eyebrow">Purchase history</p><h2>Recent purchase orders</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form billing-search-form purchase-filter-form" method="GET" action="{{ route('purchases.index') }}" data-purchase-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search PO, invoice, supplier" data-purchase-search>
                    <select name="per_page" aria-label="Purchase orders per page" data-purchase-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('purchases.index') }}" data-purchase-listing-link>Clear</a>
                </form>
            </div>

            <div class="purchase-listing-results" data-purchase-listing-results>
                <div class="product-listing-loader" data-purchase-listing-loader aria-live="polite" aria-hidden="true">
                    <span aria-hidden="true"></span>
                    <strong>Loading purchase orders</strong>
                </div>
                <div class="table-wrap"><table class="admin-table"><thead><tr><th>PO</th><th>Bill date</th><th>Invoice No.</th><th>Supplier</th><th>Tax</th><th>Total</th></tr></thead><tbody>
                    @forelse ($orders as $order)
                        <tr><td>{{ $order->order_number }}</td><td>{{ $order->received_at?->format('d M Y') ?? '-' }}</td><td>{{ $order->supplier_invoice_number ?: '-' }}</td><td>{{ $order->supplier->name ?? '-' }}</td><td>&#8377;{{ number_format($order->tax_amount, 2) }}</td><td>&#8377;{{ number_format($order->total_amount, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="6">{{ request()->filled('search') ? 'No purchase orders match the current search.' : 'No purchase orders yet.' }}</td></tr>
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
                        throw new Error('Unable to load purchase orders.');
                    }

                    return response.text();
                }).then(function (html) {
                    var page = new DOMParser().parseFromString(html, 'text/html');
                    var nextListing = page.querySelector('[data-purchase-listing]');

                    if (!nextListing) {
                        throw new Error('Purchase listing was not found.');
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
