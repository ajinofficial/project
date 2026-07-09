@extends('layouts.admin', ['title' => 'Sales'])

@php
    $oldItems = old('items');

    if (! is_array($oldItems) || count($oldItems) === 0) {
        $oldItems = [[
            'product_id' => old('product_id', ''),
            'quantity' => old('quantity', 1),
        ]];
    }

    $productPayload = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku ?: $product->barcode,
        'stock' => $product->available_stock,
        'price' => (float) $product->price,
        'tax' => (float) $product->tax_percentage,
    ])->values();
@endphp

@section('content')
    <style>
        .billing-page,
        .billing-page .admin-section,
        .billing-page .product-form {
            min-width: 0;
        }

        .billing-page .product-form {
            max-width: none;
            width: 100%;
        }

        .billing-items {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            min-width: 0;
        }

        .sr-only {
            border: 0;
            clip: rect(0, 0, 0, 0);
            height: 1px;
            margin: -1px;
            overflow: hidden;
            padding: 0;
            position: absolute;
            width: 1px;
        }

        .billing-item-row,
        .billing-items-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 74px 82px 88px 40px;
            gap: 8px;
            align-items: center;
            padding: 10px;
        }

        .billing-item-row > label {
            min-width: 0;
        }

        .billing-item-row > label::before {
            content: attr(data-label);
            display: none;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .billing-item-row select,
        .billing-item-row input {
            min-width: 0;
            width: 100%;
        }

        .billing-item-row select,
        .billing-item-row input,
        .billing-row-remove {
            min-height: 42px;
        }

        .billing-items-head {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .billing-item-row + .billing-item-row {
            border-top: 1px solid #eef2f7;
        }

        .billing-cell-total,
        .billing-line-price {
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .billing-cell-total::before,
        .billing-line-price::before {
            content: attr(data-label);
            display: none;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .billing-stock-note {
            color: #64748b;
            display: block;
            font-size: 12px;
            margin-top: 4px;
        }

        .billing-row-remove,
        .billing-add-row {
            align-items: center;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            font-weight: 700;
            justify-content: center;
            min-height: 40px;
        }

        .billing-row-remove {
            width: 40px;
            padding: 0;
        }

        .billing-row-remove:hover,
        .billing-add-row:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .billing-add-row {
            gap: 8px;
            margin-top: 12px;
            padding: 0 14px;
        }

        .billing-summary {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: grid;
            gap: 8px;
            padding: 14px;
        }

        .billing-summary div {
            align-items: center;
            display: flex;
            justify-content: space-between;
        }

        .billing-summary strong {
            font-size: 20px;
        }

        .billing-items-error {
            color: #b91c1c;
            font-size: 13px;
            margin-top: 8px;
        }

        .invoice-item-list {
            display: grid;
            gap: 4px;
        }

        .billing-invoice-table {
            min-width: 0;
        }

        .billing-invoice-table td::before {
            display: none;
        }

        @media (max-width: 1300px) {
            .billing-page {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .billing-items {
                border: 0;
                display: grid;
                gap: 12px;
                overflow: visible;
                background: transparent;
            }

            .billing-items-head {
                display: none;
            }

            .billing-item-row {
                grid-template-columns: minmax(0, 1fr) minmax(112px, 140px);
                grid-template-areas:
                    "product product"
                    "qty remove"
                    "rate total";
                align-items: start;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: #fff;
            }

            .billing-item-row > label:first-child {
                grid-area: product;
            }

            .billing-item-row > label:nth-child(2) {
                grid-area: qty;
            }

            .billing-item-row > label::before {
                display: block;
            }

            .billing-line-price {
                grid-area: rate;
                align-self: stretch;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 4px;
                min-height: 48px;
                padding: 9px 10px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: #f8fafc;
            }

            .billing-cell-total {
                grid-area: total;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 4px;
                min-height: 48px;
                padding: 9px 10px;
                border: 1px solid #dbeafe;
                border-radius: 8px;
                background: #eff6ff;
            }

            .billing-line-price::before,
            .billing-cell-total::before {
                display: block;
            }

            .billing-row-remove {
                grid-area: remove;
                align-self: end;
                width: 100%;
                min-height: 42px;
            }
        }

        @media (max-width: 640px) {
            .billing-page .admin-section {
                padding-left: 12px;
                padding-right: 12px;
            }

            .billing-item-row {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "product"
                    "qty"
                    "rate"
                    "total"
                    "remove";
                gap: 12px;
                padding: 14px;
            }

            .billing-line-price,
            .billing-cell-total {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                white-space: normal;
            }

            .billing-line-price::before,
            .billing-cell-total::before {
                display: inline;
            }

            .billing-row-remove {
                width: 100%;
            }

            .billing-add-row,
            .billing-page .product-save-button {
                width: 100%;
            }

            .billing-summary {
                padding: 12px;
            }

            .billing-summary strong {
                font-size: 18px;
            }

            .billing-page .product-toolbar,
            .billing-page .billing-search-form {
                width: 100%;
            }

            .billing-page .billing-search-form {
                grid-template-columns: 1fr;
            }

            .billing-page .product-clear-filter {
                justify-content: center;
                width: 100%;
            }

            .billing-page .table-wrap {
                overflow: visible;
            }

            .billing-invoice-table,
            .billing-invoice-table thead,
            .billing-invoice-table tbody,
            .billing-invoice-table tr,
            .billing-invoice-table th,
            .billing-invoice-table td {
                display: block;
                min-width: 0;
                width: 100%;
            }

            .billing-invoice-table thead {
                display: none;
            }

            .billing-invoice-table tr {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                margin-bottom: 12px;
                overflow: hidden;
                background: #fff;
            }

            .billing-invoice-table td {
                align-items: flex-start;
                display: flex;
                gap: 12px;
                height: auto;
                justify-content: space-between;
                padding: 10px 12px;
                border-bottom: 1px solid #eef2f7;
                text-align: right;
            }

            .billing-invoice-table td:last-child {
                border-bottom: 0;
            }

            .billing-invoice-table td::before {
                content: attr(data-label);
                display: inline;
                flex: 0 0 82px;
                color: #64748b;
                font-size: 12px;
                font-weight: 800;
                text-align: left;
                text-transform: uppercase;
            }

            .billing-invoice-table td[colspan] {
                text-align: left;
            }

            .billing-invoice-table td[colspan]::before {
                display: none;
            }

            .invoice-item-list {
                justify-items: end;
            }
        }
    </style>

    <section class="ops-grid billing-page">
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

                <div>
                    <div class="billing-items" data-billing-items>
                        <div class="billing-items-head">
                            <span>Product</span>
                            <span>Qty</span>
                            <span>Rate</span>
                            <span>Total</span>
                            <span></span>
                        </div>

                        @foreach ($oldItems as $index => $item)
                            <div class="billing-item-row" data-billing-row>
                                <label data-label="Product">
                                    <span class="sr-only">Product</span>
                                    <select name="items[{{ $index }}][product_id]" required data-product-select>
                                        <option value="">Select product</option>
                                        @foreach ($products as $product)
                                            <option
                                                value="{{ $product->id }}"
                                                data-price="{{ $product->price }}"
                                                data-tax="{{ $product->tax_percentage }}"
                                                data-stock="{{ $product->available_stock }}"
                                                @selected((string) ($item['product_id'] ?? '') === (string) $product->id)
                                            >
                                                {{ $product->name }} - {{ $product->sku ?: $product->barcode ?: 'No SKU' }} - Stock {{ $product->available_stock }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="billing-stock-note" data-stock-note></small>
                                </label>

                                <label data-label="Qty">
                                    <span class="sr-only">Quantity</span>
                                    <input type="number" name="items[{{ $index }}][quantity]" min="1" value="{{ $item['quantity'] ?? 1 }}" required data-quantity-input>
                                </label>

                                <span class="billing-line-price" data-label="Rate" data-line-price>&#8377;0.00</span>
                                <span class="billing-cell-total" data-label="Total" data-line-total>&#8377;0.00</span>
                                <button class="billing-row-remove" type="button" aria-label="Remove product" data-remove-row>&times;</button>
                            </div>
                        @endforeach
                    </div>
                    @error('items') <div class="billing-items-error">{{ $message }}</div> @enderror
                    @error('items.*.product_id') <div class="billing-items-error">{{ $message }}</div> @enderror
                    @error('items.*.quantity') <div class="billing-items-error">{{ $message }}</div> @enderror
                    <button class="billing-add-row" type="button" data-add-row>+ Add product</button>
                </div>

                <div class="billing-summary" aria-live="polite">
                    <div><span>Subtotal</span><b data-billing-subtotal>&#8377;0.00</b></div>
                    <div><span>Tax</span><b data-billing-tax>&#8377;0.00</b></div>
                    <div><span>Total</span><strong data-billing-total>&#8377;0.00</strong></div>
                </div>

                <div class="field-grid">
                    <label>
                        <span>Paid amount</span>
                        <input type="number" name="paid_amount" min="0" step="0.01" value="{{ old('paid_amount', 0) }}" required data-replace-on-focus>
                        @error('paid_amount') <small>{{ $message }}</small> @enderror
                    </label>

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
                </div>

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
            <div class="table-wrap"><table class="admin-table billing-invoice-table"><thead><tr><th>Invoice</th><th>Customer</th><th>Items</th><th>Total</th><th>Paid</th></tr></thead><tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td data-label="Invoice">{{ $order->invoice_number }}</td>
                        <td data-label="Customer">{{ $order->customer->name ?? 'Walk-in' }}</td>
                        <td data-label="Items">
                            <span class="invoice-item-list">
                                @foreach ($order->items as $item)
                                    <strong>{{ $item->product->name ?? 'Product' }} x {{ $item->quantity }}</strong>
                                @endforeach
                            </span>
                        </td>
                        <td data-label="Total">&#8377;{{ number_format($order->total_amount, 2) }}</td>
                        <td data-label="Paid">&#8377;{{ number_format($order->paid_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No invoices yet.</td></tr>
                @endforelse
            </tbody></table></div>
            @include('products.partials.pagination', ['paginator' => $orders, 'itemLabel' => 'invoices'])
        </article>
    </section>

    <template data-billing-row-template>
        <div class="billing-item-row" data-billing-row>
            <label data-label="Product">
                <span class="sr-only">Product</span>
                <select required data-product-select>
                    <option value="">Select product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-tax="{{ $product->tax_percentage }}" data-stock="{{ $product->available_stock }}">
                            {{ $product->name }} - {{ $product->sku ?: $product->barcode ?: 'No SKU' }} - Stock {{ $product->available_stock }}
                        </option>
                    @endforeach
                </select>
                <small class="billing-stock-note" data-stock-note></small>
            </label>

            <label data-label="Qty">
                <span class="sr-only">Quantity</span>
                <input type="number" min="1" value="1" required data-quantity-input>
            </label>

            <span class="billing-line-price" data-label="Rate" data-line-price>&#8377;0.00</span>
            <span class="billing-cell-total" data-label="Total" data-line-total>&#8377;0.00</span>
            <button class="billing-row-remove" type="button" aria-label="Remove product" data-remove-row>&times;</button>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var productData = @json($productPayload);

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

                var items = form.querySelector('[data-billing-items]');
                var template = document.querySelector('[data-billing-row-template]');
                var addRow = form.querySelector('[data-add-row]');
                var subtotalOutput = form.querySelector('[data-billing-subtotal]');
                var taxOutput = form.querySelector('[data-billing-tax]');
                var totalOutput = form.querySelector('[data-billing-total]');
                var currency = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' });

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

                function selectedProduct(select) {
                    var productId = Number(select.value);

                    return productData.find(function (product) {
                        return Number(product.id) === productId;
                    });
                }

                function rows() {
                    return Array.prototype.slice.call(items.querySelectorAll('[data-billing-row]'));
                }

                function renameRows() {
                    rows().forEach(function (row, index) {
                        var select = row.querySelector('[data-product-select]');
                        var quantity = row.querySelector('[data-quantity-input]');

                        select.name = 'items[' + index + '][product_id]';
                        quantity.name = 'items[' + index + '][quantity]';
                    });
                }

                function updateTotals() {
                    var subtotal = 0;
                    var tax = 0;

                    rows().forEach(function (row) {
                        var select = row.querySelector('[data-product-select]');
                        var quantityInput = row.querySelector('[data-quantity-input]');
                        var note = row.querySelector('[data-stock-note]');
                        var linePrice = row.querySelector('[data-line-price]');
                        var lineTotal = row.querySelector('[data-line-total]');
                        var product = selectedProduct(select);
                        var quantity = Math.max(0, Number(quantityInput.value || 0));
                        var lineSubtotal = product ? product.price * quantity : 0;
                        var lineTax = product ? lineSubtotal * (product.tax / 100) : 0;

                        subtotal += lineSubtotal;
                        tax += lineTax;

                        linePrice.textContent = product ? currency.format(product.price) : currency.format(0);
                        lineTotal.textContent = currency.format(lineSubtotal + lineTax);
                        note.textContent = product ? 'Available ' + product.stock + ' unit(s), tax ' + product.tax + '%' : '';

                        if (product && quantity > product.stock) {
                            quantityInput.setCustomValidity('Only ' + product.stock + ' unit(s) available.');
                        } else {
                            quantityInput.setCustomValidity('');
                        }
                    });

                    subtotalOutput.textContent = currency.format(subtotal);
                    taxOutput.textContent = currency.format(tax);
                    totalOutput.textContent = currency.format(subtotal + tax);
                }

                function bindRow(row) {
                    row.querySelectorAll('input, select').forEach(function (field) {
                        field.addEventListener('input', function () {
                            updateTotals();
                            validateField(field);
                        });

                        field.addEventListener('change', function () {
                            updateTotals();
                            validateField(field);
                        });
                    });

                    row.querySelector('[data-remove-row]').addEventListener('click', function () {
                        if (rows().length === 1) {
                            row.querySelector('[data-product-select]').value = '';
                            row.querySelector('[data-quantity-input]').value = 1;
                        } else {
                            row.remove();
                        }

                        renameRows();
                        updateTotals();
                    });
                }

                addRow.addEventListener('click', function () {
                    var row = template.content.firstElementChild.cloneNode(true);
                    items.appendChild(row);
                    bindRow(row);
                    renameRows();
                    updateTotals();
                    row.querySelector('[data-product-select]').focus();
                });

                rows().forEach(bindRow);
                renameRows();
                updateTotals();

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

                    updateTotals();

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
