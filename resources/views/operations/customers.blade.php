@extends('layouts.admin', ['title' => 'Customers'])

@section('content')
    <style>
        .customer-listing-results { position: relative; min-height: 160px; }
        .customer-listing-results .product-listing-loader { inset: 0; }
        .customer-listing-panel.is-loading .table-wrap,
        .customer-listing-panel.is-loading .product-pagination { opacity: .38; pointer-events: none; }
        .customer-listing-panel.is-loading .product-listing-loader { display: grid; }
        .customer-form {
            gap: 12px;
        }

        .customer-form .customer-field {
            display: grid;
            gap: 6px;
        }

        .customer-form .customer-field span {
            margin: 0;
            color: #17201a;
            font-size: var(--text-sm);
            font-weight: 800;
        }

        .customer-form .customer-field-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .customer-form .customer-form-control {
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

        .customer-form .customer-form-control::placeholder {
            color: #050703;
            opacity: 1;
        }

        .customer-form .customer-form-control:focus {
            border-color: #2f80ed !important;
            box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.14) !important;
            outline: none;
        }

        .customer-form .customer-form-control[aria-invalid="true"] {
            border-color: #b42318 !important;
            background: #fff8f7 !important;
        }

        .customer-form small {
            color: #b42318;
            font-size: var(--text-xs);
            font-weight: 700;
        }

        .customer-table {
            min-width: 780px;
        }

        .customer-table th,
        .customer-table td {
            height: auto;
            padding: 9px 10px;
           
        }

        
    </style>

    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Customer management</p><h2>Add customer</h2></div></div>
            <form class="product-form customer-form" method="POST" action="{{ route('customers.store') }}" data-customer-form>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>Check the customer details</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <label class="customer-field">
                    <span>Customer name</span>
                    <input class="customer-form-control" name="name" value="{{ old('name') }}" placeholder="Customer name" required>
                    @error('name') <small>{{ $message }}</small> @enderror
                </label>

                <label class="customer-field">
                    <span>Mobile number</span>
                    <input class="customer-form-control" type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="Phone number" autocomplete="tel" required @if ($errors->has('mobile')) aria-invalid="true" @endif>
                    @error('mobile') <small>{{ $message }}</small> @enderror
                </label>

                <div class="field-grid customer-field-grid">
                    <label class="customer-field">
                        <span>Credit limit</span>
                        <input class="customer-form-control" type="number" name="credit_limit" min="0" step="0.01" value="{{ old('credit_limit', 0) }}" required data-replace-on-focus>
                        @error('credit_limit') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="customer-field">
                        <span>Outstanding balance</span>
                        <input class="customer-form-control" type="number" name="outstanding_balance" min="0" step="0.01" value="{{ old('outstanding_balance', 0) }}" required data-replace-on-focus>
                        @error('outstanding_balance') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <button class="product-save-button" type="submit" data-customer-submit>
                    <span class="product-save-button__idle">Save customer</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        Saving
                    </span>
                </button>
            </form>
        </article>

        <article class="admin-section customer-listing-panel" data-customer-listing>
            <div class="section-title"><div><p class="eyebrow">Sales accounts</p><h2>Customers</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form billing-search-form customer-filter-form" method="GET" action="{{ route('customers.index') }}" data-customer-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search customer or mobile" data-customer-search>
                    <select name="per_page" aria-label="Customers per page" data-customer-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('customers.index') }}" data-customer-listing-link>Clear</a>
                </form>
            </div>

            <div class="customer-listing-results" data-customer-listing-results>
                <div class="product-listing-loader" data-customer-listing-loader aria-live="polite" aria-hidden="true"><span aria-hidden="true"></span><strong>Loading customers</strong></div>
                <div class="table-wrap"><table class="admin-table customer-table">
                    <thead><tr><th>Name</th><th>Mobile</th><th>Credit limit</th><th>Outstanding</th></tr></thead>
                    <tbody>
                    @forelse ($customers as $customer)
                        <tr><td><strong>{{ $customer->name }}</strong></td><td>{{ $customer->mobile ?: '-' }}</td><td>&#8377;{{ number_format($customer->credit_limit, 2) }}</td><td>&#8377;{{ number_format($customer->outstanding_balance, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4">{{ request()->filled('search') ? 'No customers match the current search.' : 'No customers yet.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                @include('products.partials.pagination', ['paginator' => $customers, 'itemLabel' => 'customers'])
            </div>
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var customerRequest = null;
            var customerTimer = null;

            function customerUrl(form) {
                var params = new URLSearchParams(new FormData(form));
                Array.from(params.keys()).forEach(function (key) { if (params.get(key) === '') params.delete(key); });
                return form.action + (params.toString() ? '?' + params.toString() : '');
            }

            function loadCustomers(url, pushState) {
                var listing = document.querySelector('[data-customer-listing]');
                if (!listing) { window.location.href = url; return; }
                if (customerRequest) customerRequest.abort();
                var controller = new AbortController();
                customerRequest = controller;
                listing.classList.add('is-loading');
                listing.querySelector('[data-customer-listing-loader]')?.setAttribute('aria-hidden', 'false');

                fetch(url, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                    .then(function (response) { if (!response.ok) throw new Error('Unable to load customers.'); return response.text(); })
                    .then(function (html) {
                        var page = new DOMParser().parseFromString(html, 'text/html');
                        var next = page.querySelector('[data-customer-listing]');
                        if (!next) throw new Error('Customer listing was not found.');
                        listing.replaceWith(next);
                        document.title = page.title || document.title;
                        if (pushState !== false) window.history.pushState({ customerListing: true }, '', url);
                    })
                    .catch(function (error) { if (error.name !== 'AbortError') window.location.href = url; })
                    .finally(function () { if (customerRequest === controller) customerRequest = null; });
            }

            document.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-customer-search-form]');
                if (form) { event.preventDefault(); loadCustomers(customerUrl(form), true); }
            });
            document.addEventListener('change', function (event) {
                var select = event.target.closest('[data-customer-search-form] select');
                if (select) loadCustomers(customerUrl(select.form), true);
            });
            document.addEventListener('input', function (event) {
                var search = event.target.closest('[data-customer-search-form] input[type="search"]');
                if (!search) return;
                clearTimeout(customerTimer);
                customerTimer = setTimeout(function () { loadCustomers(customerUrl(search.form), true); }, 350);
            });
            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-customer-listing-link], [data-customer-listing] .product-pagination a');
                if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                event.preventDefault();
                loadCustomers(link.href, true);
            });
            window.addEventListener('popstate', function () {
                if (window.location.pathname === new URL('{{ route('customers.index') }}').pathname) loadCustomers(window.location.href, false);
            });

            document.querySelectorAll('[data-customer-form]').forEach(function (form) {
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
                    var button = form.querySelector('[data-customer-submit]');

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
