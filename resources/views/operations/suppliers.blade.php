@extends('layouts.admin', ['title' => 'Suppliers'])

@section('content')
    <style>
        .supplier-form {
            gap: 12px;
        }

        .supplier-form .supplier-field {
            display: grid;
            gap: 6px;
        }

        .supplier-form .supplier-field span {
            margin: 0;
            color: #17201a;
            font-size: 14px;
            font-weight: 800;
        }

        .supplier-form .supplier-field-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .supplier-form .supplier-form-control {
            width: 100% !important;
            min-height: 42px !important;
            border: 1px solid #dfe6db !important;
            border-radius: 8px !important;
            padding: 9px 12px !important;
            color: #050703 !important;
            background: #ffffff !important;
            font-size: 15px !important;
            line-height: 1.4 !important;
            box-shadow: none !important;
        }

        .supplier-form .supplier-form-control::placeholder {
            color: #050703;
            opacity: 1;
        }

        .supplier-form .supplier-form-control:focus {
            border-color: #2f80ed !important;
            box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.14) !important;
            outline: none;
        }

        .supplier-form .supplier-form-control[aria-invalid="true"] {
            border-color: #b42318 !important;
            background: #fff8f7 !important;
        }

        .supplier-form small {
            color: #b42318;
            font-size: 12px;
            font-weight: 700;
        }

        .supplier-listing-panel.is-loading .table-wrap,
        .supplier-listing-panel.is-loading .product-pagination {
            opacity: 0.38;
            pointer-events: none;
        }

        .supplier-listing-results {
            position: relative;
            min-height: 160px;
        }

        .supplier-listing-results .product-listing-loader {
            inset: 0;
        }

        .supplier-listing-panel.is-loading .product-listing-loader {
            display: grid;
        }
    </style>

    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Supplier management</p><h2>Add supplier</h2></div></div>
            <form class="product-form supplier-form" method="POST" action="{{ route('suppliers.store') }}" data-supplier-form>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>Check the supplier details</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <label class="supplier-field supplier-field-wide">
                    <span>Supplier name</span>
                    <input class="supplier-form-control" name="name" value="{{ old('name') }}" placeholder="Supplier or company name" required>
                    @error('name') <small>{{ $message }}</small> @enderror
                </label>

                <label class="supplier-field supplier-field-wide">
                    <span>Contact information</span>
                    <input class="supplier-form-control" name="contact_information" value="{{ old('contact_information') }}" placeholder="Phone, email, or contact person" required @if ($errors->has('contact_information')) aria-invalid="true" @endif>
                    @error('contact_information') <small>{{ $message }}</small> @enderror
                </label>

                <div class="field-grid supplier-field-grid">
                    <label class="supplier-field">
                        <span>GST number</span>
                        <input class="supplier-form-control" name="gst_number" value="{{ old('gst_number') }}" placeholder="GSTIN">
                        @error('gst_number') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="supplier-field">
                        <span>Payment terms</span>
                        <input class="supplier-form-control" name="payment_terms" value="{{ old('payment_terms') }}" placeholder="Net 15, COD">
                        @error('payment_terms') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="supplier-field supplier-field-balance">
                    <span>Outstanding balance</span>
                    <input class="supplier-form-control" type="number" name="outstanding_balance" min="0" step="0.01" value="{{ old('outstanding_balance', 0) }}" required data-replace-on-focus>
                    @error('outstanding_balance') <small>{{ $message }}</small> @enderror
                </label>

                <button class="product-save-button" type="submit" data-supplier-submit>
                    <span class="product-save-button__idle">Save supplier</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        Saving
                    </span>
                </button>
            </form>
        </article>

        <article class="admin-section supplier-listing-panel" data-supplier-listing>
            <div class="section-title"><div><p class="eyebrow">Purchase partners</p><h2>Suppliers</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form billing-search-form supplier-filter-form" method="GET" action="{{ route('suppliers.index') }}" data-supplier-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search supplier, contact, GST, terms" data-supplier-search>
                    <select name="per_page" aria-label="Suppliers per page" data-supplier-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('suppliers.index') }}" data-supplier-listing-link>Clear</a>
                </form>
            </div>

            <div class="supplier-listing-results" data-supplier-listing-results>
                <div class="product-listing-loader" data-supplier-listing-loader aria-live="polite" aria-hidden="true">
                    <span aria-hidden="true"></span>
                    <strong>Loading suppliers</strong>
                </div>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Name</th><th>GST</th><th>Terms</th><th>Outstanding</th></tr></thead>
                        <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr><td><strong>{{ $supplier->name }}</strong><span>{{ $supplier->contact_information }}</span></td><td>{{ $supplier->gst_number ?: '-' }}</td><td>{{ $supplier->payment_terms ?: '-' }}</td><td>&#8377;{{ number_format($supplier->outstanding_balance, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="4">{{ request()->filled('search') ? 'No suppliers match the current search.' : 'No suppliers yet.' }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('products.partials.pagination', ['paginator' => $suppliers, 'itemLabel' => 'suppliers'])
            </div>
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var supplierListingRequest = null;
            var supplierSearchTimer = null;

            function supplierUrl(form) {
                var params = new URLSearchParams(new FormData(form));

                Array.from(params.keys()).forEach(function (key) {
                    if (params.get(key) === '') {
                        params.delete(key);
                    }
                });

                var query = params.toString();
                return form.action + (query ? '?' + query : '');
            }

            function showSupplierListingLoader() {
                var listing = document.querySelector('[data-supplier-listing]');
                var loader = document.querySelector('[data-supplier-listing-loader]');

                if (listing && loader) {
                    listing.classList.add('is-loading');
                    loader.setAttribute('aria-hidden', 'false');
                }
            }

            function loadSupplierListing(url, pushState) {
                var listing = document.querySelector('[data-supplier-listing]');

                if (!listing) {
                    window.location.href = url;
                    return;
                }

                if (supplierListingRequest) {
                    supplierListingRequest.abort();
                }

                var requestController = new AbortController();
                supplierListingRequest = requestController;
                showSupplierListingLoader();

                fetch(url, {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: requestController.signal
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('Unable to load suppliers.');
                    }

                    return response.text();
                }).then(function (html) {
                    var page = new DOMParser().parseFromString(html, 'text/html');
                    var nextListing = page.querySelector('[data-supplier-listing]');

                    if (!nextListing) {
                        throw new Error('Supplier listing was not found.');
                    }

                    listing.replaceWith(nextListing);
                    document.title = page.title || document.title;

                    if (pushState !== false) {
                        window.history.pushState({ supplierListing: true }, '', url);
                    }
                }).catch(function (error) {
                    if (error.name !== 'AbortError') {
                        window.location.href = url;
                    }
                }).finally(function () {
                    if (supplierListingRequest === requestController) {
                        supplierListingRequest = null;
                    }
                });
            }

            document.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-supplier-search-form]');

                if (form) {
                    event.preventDefault();
                    loadSupplierListing(supplierUrl(form), true);
                }
            });

            document.addEventListener('change', function (event) {
                var select = event.target.closest('[data-supplier-search-form] select[data-supplier-search]');

                if (select) {
                    loadSupplierListing(supplierUrl(select.form), true);
                }
            });

            document.addEventListener('input', function (event) {
                var search = event.target.closest('[data-supplier-search-form] input[type="search"]');

                if (!search) {
                    return;
                }

                window.clearTimeout(supplierSearchTimer);
                supplierSearchTimer = window.setTimeout(function () {
                    loadSupplierListing(supplierUrl(search.form), true);
                }, 350);
            });

            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-supplier-listing-link], [data-supplier-listing] .product-pagination a');

                if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                loadSupplierListing(link.href, true);
            });

            window.addEventListener('popstate', function () {
                if (window.location.pathname === new URL('{{ route('suppliers.index') }}').pathname) {
                    loadSupplierListing(window.location.href, false);
                }
            });

            document.querySelectorAll('[data-supplier-form]').forEach(function (form) {
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
                    var button = form.querySelector('[data-supplier-submit]');

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
