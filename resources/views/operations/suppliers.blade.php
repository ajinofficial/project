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
                    <input class="supplier-form-control" name="contact_information" value="{{ old('contact_information') }}" placeholder="Phone, email, or contact person">
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

        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Purchase partners</p><h2>Suppliers</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form billing-search-form supplier-filter-form" method="GET" action="{{ route('suppliers.index') }}" data-supplier-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search supplier, contact, GST, terms" data-supplier-search>
                    <select name="per_page" aria-label="Suppliers per page" data-supplier-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('suppliers.index') }}">Clear</a>
                </form>
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
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-supplier-search-form]').forEach(function (form) {
                var search = form.querySelector('[data-supplier-search]');
                var fields = form.querySelectorAll('[data-supplier-search]');

                if (!search) {
                    return;
                }

                function submitSearch() {
                    if (search.value.trim() === '') {
                        search.disabled = true;
                    }

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }

                    form.submit();
                }

                fields.forEach(function (field) {
                    field.addEventListener('change', submitSearch);
                    field.addEventListener('search', submitSearch);
                });
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
