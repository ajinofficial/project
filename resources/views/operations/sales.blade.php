@extends('layouts.admin', ['title' => 'Sales'])

@section('content')
    <section class="ops-grid">
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Billing</p><h2>Create invoice</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('sales.store') }}" data-billing-form>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>Check the billing details</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <label>
                    <span>Customer</span>
                    <select name="customer_id">
                        <option value="">Walk-in customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Search product / SKU / barcode</span>
                    <select name="product_id" required>
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }} - {{ $product->sku ?: $product->barcode }} - Stock {{ $product->available_stock }}</option>
                        @endforeach
                    </select>
                    @error('product_id') <small>{{ $message }}</small> @enderror
                </label>

                <div class="field-grid">
                    <label>
                        <span>Quantity</span>
                        <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
                        @error('quantity') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Paid amount</span>
                        <input type="number" name="paid_amount" min="0" step="0.01" value="{{ old('paid_amount', 0) }}" required data-replace-on-focus>
                        @error('paid_amount') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label>
                    <span>Payment method</span>
                    <select name="payment_method" required>
                        <option value="">Select payment method</option>
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                        <option value="upi" @selected(old('payment_method') === 'upi')>UPI</option>
                        <option value="card" @selected(old('payment_method') === 'card')>Credit/Debit card</option>
                        <option value="net_banking" @selected(old('payment_method') === 'net_banking')>Net banking</option>
                        <option value="credit" @selected(old('payment_method') === 'credit')>Customer credit</option>
                    </select>
                    @error('payment_method') <small>{{ $message }}</small> @enderror
                </label>

                <button class="product-save-button" type="submit" data-billing-submit>
                    <span class="product-save-button__idle">Generate invoice</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        Generating
                    </span>
                </button>
            </form>
        </article>
        <article class="admin-section">
            <div class="section-title"><div><p class="eyebrow">Invoices</p><h2>Recent invoices</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form billing-search-form" method="GET" action="{{ route('sales.index') }}" data-billing-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search invoice, customer, product" data-billing-search>
                    <a class="product-clear-filter" href="{{ route('sales.index') }}">Clear</a>
                </form>
            </div>
            <div class="table-wrap"><table class="admin-table"><thead><tr><th>Invoice</th><th>Customer</th><th>Items</th><th>Total</th><th>Paid</th></tr></thead><tbody>
                @forelse ($orders as $order)
                    <tr><td>{{ $order->invoice_number }}</td><td>{{ $order->customer->name ?? 'Walk-in' }}</td><td>@foreach ($order->items as $item)<strong>{{ $item->product->name ?? 'Product' }} x {{ $item->quantity }}</strong>@endforeach</td><td>₹{{ number_format($order->total_amount, 2) }}</td><td>₹{{ number_format($order->paid_amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="5">No invoices yet.</td></tr>
                @endforelse
            </tbody></table></div>
            @include('products.partials.pagination', ['paginator' => $orders, 'itemLabel' => 'invoices'])
        </article>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-billing-search-form]').forEach(function (form) {
                var search = form.querySelector('[data-billing-search]');

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

                search.addEventListener('change', submitSearch);
                search.addEventListener('search', submitSearch);
            });

            document.querySelectorAll('[data-billing-form]').forEach(function (form) {
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
                    var button = form.querySelector('[data-billing-submit]');

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
