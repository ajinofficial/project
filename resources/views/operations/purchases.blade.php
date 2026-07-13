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
                        <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}" maxlength="120" placeholder="Bill / invoice number from supplier">
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

        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Purchase history</p><h2>Recent purchase orders</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form billing-search-form purchase-filter-form" method="GET" action="{{ route('purchases.index') }}" data-purchase-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search PO, invoice, supplier" data-purchase-search>
                    <select name="per_page" aria-label="Purchase orders per page" data-purchase-search>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('purchases.index') }}">Clear</a>
                </form>
            </div>

            <div class="table-wrap"><table class="admin-table"><thead><tr><th>PO</th><th>Bill date</th><th>Invoice No.</th><th>Supplier</th><th>Tax</th><th>Total</th></tr></thead><tbody>
                @forelse ($orders as $order)
                    <tr><td>{{ $order->order_number }}</td><td>{{ $order->received_at?->format('d M Y') ?? '-' }}</td><td>{{ $order->supplier_invoice_number ?: '-' }}</td><td>{{ $order->supplier->name ?? '-' }}</td><td>&#8377;{{ number_format($order->tax_amount, 2) }}</td><td>&#8377;{{ number_format($order->total_amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="6">{{ request()->filled('search') ? 'No purchase orders match the current search.' : 'No purchase orders yet.' }}</td></tr>
                @endforelse
            </tbody></table></div>
            @include('products.partials.pagination', ['paginator' => $orders, 'itemLabel' => 'purchase orders'])
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-purchase-search-form]').forEach(function (form) {
                var search = form.querySelector('[data-purchase-search]');
                var fields = form.querySelectorAll('[data-purchase-search]');

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
