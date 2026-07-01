@extends('layouts.admin', ['title' => 'Returns'])

@section('content')
    <style>
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
            font-size: 14px;
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
            font-size: 15px !important;
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
            font-size: 12px;
            font-weight: 700;
        }

        .return-table {
            min-width: 820px;
        }

        .return-table th,
        .return-table td {
            height: auto;
            padding: 9px 10px;
            font-size: 12px;
        }

        .return-type-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 11px;
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
    </style>

    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Returns</p><h2>Process return</h2></div></div>
            <form class="product-form return-form" method="POST" action="{{ route('returns.store') }}" data-return-form>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>Check the return details</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <label class="return-field">
                    <span>Product</span>
                    <select class="return-form-control" name="product_id" required>
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }} - Stock {{ $product->inventory }}</option>
                        @endforeach
                    </select>
                    @error('product_id') <small>{{ $message }}</small> @enderror
                </label>

                <div class="field-grid return-field-grid">
                    <label class="return-field">
                        <span>Return type</span>
                        <select class="return-form-control" name="return_type" required>
                            <option value="">Select return type</option>
                            <option value="sales_return" @selected(old('return_type', 'sales_return') === 'sales_return')>Sales return: stock increases</option>
                            <option value="purchase_return" @selected(old('return_type') === 'purchase_return')>Purchase return: stock decreases</option>
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
                    <span class="product-save-button__idle">Process return</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        Processing
                    </span>
                </button>
            </form>
        </article>

        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Audit log</p><h2>Return movements</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form return-filter-form" method="GET" action="{{ route('returns.index') }}" data-return-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search product, SKU, notes" data-return-search>
                    <select name="type" aria-label="Return type" data-return-search>
                        <option value="">All returns</option>
                        <option value="sales_return" @selected(request('type') === 'sales_return')>Sales return</option>
                        <option value="purchase_return" @selected(request('type') === 'purchase_return')>Purchase return</option>
                    </select>
                    <select name="per_page" aria-label="Returns per page" data-return-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('returns.index') }}">Clear</a>
                </form>
            </div>

            <div class="table-wrap">
                <table class="admin-table return-table">
                    <thead><tr><th>Product</th><th>Type</th><th>Quantity</th><th>Stock after</th><th>Notes</th></tr></thead>
                    <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td><strong>{{ $movement->product->name ?? 'Product' }}</strong></td>
                            <td>
                                <span class="return-type-badge {{ $movement->type === 'sales_return' ? 'is-sales' : 'is-purchase' }}">
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
                </table>
            </div>
            @include('products.partials.pagination', ['paginator' => $movements, 'itemLabel' => 'returns'])
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-return-search-form]').forEach(function (form) {
                var search = form.querySelector('[data-return-search]');
                var fields = form.querySelectorAll('[data-return-search]');

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
@endsection
