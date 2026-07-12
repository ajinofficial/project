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

        .invoice-view-button {
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            padding: 0 11px;
            color: #344054;
            background: #ffffff;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .invoice-view-button:hover {
            border-color: #bfdbfe;
            color: #2563eb;
            background: #eff6ff;
        }

        .invoice-view-button svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
        }

        .invoice-drawer-backdrop {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: none;
            background: rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(2px);
        }

        .invoice-drawer-backdrop.is-open {
            display: block;
        }

        .invoice-drawer {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 31;
            width: min(520px, 100vw);
            height: 100vh;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #d0d5dd;
            background: #ffffff;
            box-shadow: -24px 0 60px rgba(16, 24, 40, 0.22);
            transform: translateX(105%);
            transition: transform 180ms ease;
        }

        .invoice-drawer.is-open {
            transform: translateX(0);
        }

        .invoice-drawer-head,
        .invoice-drawer-actions {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            border-bottom: 1px solid #edf1f5;
            padding: 20px;
            background: #fbfdff;
        }

        .invoice-drawer-head h2 {
            margin: 4px 0 6px;
            color: var(--inapp-text);
            font-size: 24px;
        }

        .invoice-drawer-head span {
            color: var(--inapp-muted);
            font-size: 13px;
        }

        .invoice-drawer-close {
            width: 36px;
            height: 36px;
            min-height: 36px;
            display: grid;
            place-items: center;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            color: #344054;
            background: #ffffff;
        }

        .invoice-drawer-close:hover,
        .invoice-drawer-close:focus {
            border-color: #fecaca;
            color: #b42318;
            background: #fff7f7;
            outline: none;
        }

        .invoice-drawer-close svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
        }

        .invoice-drawer-body {
            display: grid;
            align-content: start;
            gap: 16px;
            overflow: auto;
            padding: 20px;
        }

        .invoice-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .invoice-detail-grid div,
        .invoice-total-box {
            border: 1px solid #edf1f5;
            border-radius: 8px;
            padding: 12px;
            background: #ffffff;
        }

        .invoice-detail-grid span,
        .invoice-total-box span {
            display: block;
            color: var(--inapp-muted);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .invoice-detail-grid strong,
        .invoice-total-box strong {
            display: block;
            margin-top: 5px;
            color: var(--inapp-text);
            overflow-wrap: anywhere;
        }

        .invoice-total-box {
            display: grid;
            gap: 8px;
            background: #f8fbff;
        }

        .invoice-total-box div {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .invoice-drawer-items {
            display: grid;
            gap: 10px;
        }

        .invoice-drawer-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            border: 1px solid #edf1f5;
            border-radius: 8px;
            padding: 12px;
            background: #ffffff;
        }

        .invoice-drawer-item strong,
        .invoice-drawer-item small {
            display: block;
        }

        .invoice-drawer-item small {
            margin-top: 4px;
            color: var(--inapp-muted);
            font-size: 12px;
        }

        .invoice-drawer-actions {
            align-items: center;
            justify-content: flex-end;
            border-top: 1px solid #edf1f5;
            border-bottom: 0;
            margin-top: auto;
        }

        .billing-invoice-table {
            min-width: 0;
        }

        .billing-invoice-table td::before {
            display: none;
        }

        .billing-invoice-panel.is-loading .billing-invoice-listing,
        .billing-invoice-panel.is-loading .product-pagination {
            opacity: 0.38;
            pointer-events: none;
        }

        .billing-invoice-listing {
            position: relative;
        }

        .billing-listing-loader {
            position: absolute;
            inset: 0;
            z-index: 4;
            display: none;
            min-height: 180px;
            place-items: center;
            align-content: center;
            gap: 10px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.76);
            backdrop-filter: blur(2px);
        }

        .billing-invoice-panel.is-loading .billing-listing-loader {
            display: grid;
        }

        .billing-listing-loader span {
            width: 34px;
            height: 34px;
            border: 4px solid #eef0e8;
            border-top-color: var(--v-lime);
            border-radius: 999px;
            animation: product-listing-spin 0.75s linear infinite;
        }

        .billing-listing-loader strong {
            color: #2f352b;
            font-size: 13px;
            font-weight: 900;
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
                        <input type="number" name="paid_amount" min="0.01" step="0.01" value="{{ old('paid_amount', 0) }}" required data-replace-on-focus>
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
        <article class="admin-section billing-invoice-panel" data-billing-listing>
            <div class="section-title"><div><p class="eyebrow">Invoices</p><h2>Recent invoices</h2></div></div>
            <div class="product-toolbar">
                <form class="product-filter-form billing-search-form" method="GET" action="{{ route('sales.index') }}" data-billing-search-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search invoice, customer, product" data-billing-search>
                    <a class="product-clear-filter" href="{{ route('sales.index') }}" data-billing-listing-link>Clear</a>
                </form>
            </div>
            <div class="table-wrap billing-invoice-listing">
                <div class="billing-listing-loader" data-billing-listing-loader aria-live="polite" aria-hidden="true">
                    <span aria-hidden="true"></span>
                    <strong>Loading invoices</strong>
                </div>
                <table class="admin-table billing-invoice-table"><thead><tr><th>Invoice</th><th>Customer</th><th>Items</th><th>Total</th><th>Paid</th><th>Action</th></tr></thead><tbody>
                @forelse ($orders as $order)
                    @php
                        $invoiceItems = $order->items->map(function ($item) {
                            $lineSubtotal = (float) $item->selling_price * (int) $item->quantity;
                            $lineTotal = $lineSubtotal + ($lineSubtotal * ((float) $item->tax_percentage / 100));

                            return [
                                'product' => $item->product->name ?? 'Product',
                                'sku' => $item->product->sku ?: ($item->product->barcode ?? 'No SKU'),
                                'quantity' => (int) $item->quantity,
                                'rate' => number_format((float) $item->selling_price, 2),
                                'tax' => number_format((float) $item->tax_percentage, 2),
                                'total' => number_format($lineTotal, 2),
                            ];
                        })->values();
                    @endphp
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
                        <td data-label="Action">
                            <button
                                type="button"
                                class="invoice-view-button"
                                data-invoice-view
                                data-invoice-number="{{ $order->invoice_number }}"
                                data-invoice-customer="{{ $order->customer->name ?? 'Walk-in' }}"
                                data-invoice-date="{{ $order->created_at->format('d M Y, h:i A') }}"
                                data-invoice-subtotal="{{ number_format($order->subtotal, 2) }}"
                                data-invoice-tax="{{ number_format($order->tax_amount, 2) }}"
                                data-invoice-total="{{ number_format($order->total_amount, 2) }}"
                                data-invoice-paid="{{ number_format($order->paid_amount, 2) }}"
                                data-invoice-payment="{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}"
                                data-invoice-items="{{ base64_encode($invoiceItems->toJson()) }}"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <span>View</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No invoices yet.</td></tr>
                @endforelse
            </tbody></table></div>
            @include('products.partials.pagination', ['paginator' => $orders, 'itemLabel' => 'invoices'])
        </article>
    </section>

    <div class="invoice-drawer-backdrop" data-invoice-backdrop></div>
    <aside class="invoice-drawer" data-invoice-drawer aria-hidden="true">
        <div class="invoice-drawer-head">
            <div>
                <p class="eyebrow">Invoice details</p>
                <h2 data-invoice-drawer-number>Invoice</h2>
                <span data-invoice-drawer-date></span>
            </div>
            <button type="button" class="invoice-drawer-close" data-close-invoice aria-label="Close invoice details">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
        <div class="invoice-drawer-body">
            <div class="invoice-detail-grid">
                <div><span>Customer</span><strong data-invoice-drawer-customer></strong></div>
                <div><span>Payment</span><strong data-invoice-drawer-payment></strong></div>
            </div>
            <div>
                <p class="eyebrow">Products</p>
                <div class="invoice-drawer-items" data-invoice-drawer-items></div>
            </div>
            <div class="invoice-total-box">
                <div><span>Subtotal</span><strong data-invoice-drawer-subtotal></strong></div>
                <div><span>Tax</span><strong data-invoice-drawer-tax></strong></div>
                <div><span>Total</span><strong data-invoice-drawer-total></strong></div>
                <div><span>Paid</span><strong data-invoice-drawer-paid></strong></div>
            </div>
        </div>
    </aside>

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
            var invoiceDrawer = document.querySelector('[data-invoice-drawer]');
            var invoiceBackdrop = document.querySelector('[data-invoice-backdrop]');
            var billingListing = document.querySelector('[data-billing-listing]');
            var billingListingLoader = document.querySelector('[data-billing-listing-loader]');

            function showBillingListingLoader() {
                if (!billingListing || !billingListingLoader) {
                    return;
                }

                billingListing.classList.add('is-loading');
                billingListingLoader.setAttribute('aria-hidden', 'false');
            }

            function money(value) {
                return '\u20B9' + value;
            }

            function closeInvoiceDrawer() {
                invoiceDrawer?.classList.remove('is-open');
                invoiceDrawer?.setAttribute('aria-hidden', 'true');
                invoiceBackdrop?.classList.remove('is-open');
            }

            document.querySelectorAll('[data-invoice-view]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var items = [];

                    try {
                        items = JSON.parse(atob(button.dataset.invoiceItems || 'W10='));
                    } catch (error) {
                        items = [];
                    }

                    document.querySelector('[data-invoice-drawer-number]').textContent = button.dataset.invoiceNumber || 'Invoice';
                    document.querySelector('[data-invoice-drawer-date]').textContent = button.dataset.invoiceDate || '';
                    document.querySelector('[data-invoice-drawer-customer]').textContent = button.dataset.invoiceCustomer || 'Walk-in';
                    document.querySelector('[data-invoice-drawer-payment]').textContent = button.dataset.invoicePayment || '-';
                    document.querySelector('[data-invoice-drawer-subtotal]').textContent = money(button.dataset.invoiceSubtotal || '0.00');
                    document.querySelector('[data-invoice-drawer-tax]').textContent = money(button.dataset.invoiceTax || '0.00');
                    document.querySelector('[data-invoice-drawer-total]').textContent = money(button.dataset.invoiceTotal || '0.00');
                    document.querySelector('[data-invoice-drawer-paid]').textContent = money(button.dataset.invoicePaid || '0.00');

                    var list = document.querySelector('[data-invoice-drawer-items]');
                    list.innerHTML = '';

                    items.forEach(function (item) {
                        var row = document.createElement('div');
                        row.className = 'invoice-drawer-item';
                        row.innerHTML = '<div><strong></strong><small></small></div><strong></strong>';
                        row.querySelector('strong').textContent = item.product || 'Product';
                        row.querySelector('small').textContent = (item.sku || 'No SKU') + ' - Qty ' + item.quantity + ' - Rate ' + money(item.rate || '0.00') + ' - Tax ' + (item.tax || '0.00') + '%';
                        row.lastElementChild.textContent = money(item.total || '0.00');
                        list.appendChild(row);
                    });

                    if (items.length === 0) {
                        var empty = document.createElement('div');
                        empty.className = 'empty-state product-empty';
                        empty.textContent = 'No invoice items found.';
                        list.appendChild(empty);
                    }

                    invoiceDrawer?.classList.add('is-open');
                    invoiceDrawer?.setAttribute('aria-hidden', 'false');
                    invoiceBackdrop?.classList.add('is-open');
                });
            });

            document.querySelectorAll('[data-close-invoice], [data-invoice-backdrop]').forEach(function (button) {
                button.addEventListener('click', closeInvoiceDrawer);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeInvoiceDrawer();
                }
            });

            document.querySelectorAll('[data-billing-search-form]').forEach(function (form) {
                var search = form.querySelector('[data-billing-search]');

                if (!search) {
                    return;
                }

                function submitSearch() {
                    showBillingListingLoader();

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

                form.addEventListener('submit', function () {
                    showBillingListingLoader();
                });
            });

            document.querySelectorAll('[data-billing-listing-link], .billing-invoice-panel .product-pagination a').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }

                    showBillingListingLoader();
                });
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
