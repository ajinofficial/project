@extends('layouts.admin', ['title' => 'Add Stock'])

@section('content')
    <style>
        .stock-form-workspace .stock-form-panel {
            max-width: 760px;
            margin: 0 auto;
            padding: 22px;
        }
        .stock-form-workspace .product-form {
            max-width: none;
        }
        .stock-adjust-hint {
            margin: -8px 0 0;
            color: var(--v-muted);
            font-size: 12px;
            line-height: 1.45;
        }
        .stock-price-preview {
            border: 1px solid var(--v-line);
            border-radius: 8px;
            padding: 12px;
            background: #f8fafc;
            color: var(--v-muted);
            font-size: 12px;
            line-height: 1.45;
        }
        .stock-price-preview strong {
            display: block;
            margin-top: 4px;
            color: var(--v-text);
            font-size: 16px;
        }
    </style>

    <section class="product-workspace stock-form-workspace">
        <header class="product-page-head">
            <div>
                <p class="eyebrow">Stock</p>
                <h1>Add stock</h1>
                <span>Use this form for stock receiving, recount corrections, and manual stock changes.</span>
            </div>
            <div class="product-head-actions">
                <a class="ghost-button" href="{{ route('stock.index') }}">Back to stock</a>
                <a class="ghost-button" href="{{ route('purchases.index') }}">Purchase entry</a>
            </div>
        </header>

        <section class="v-panel product-management-panel stock-form-panel">
            <div class="section-title"><div><p class="eyebrow">Stock control</p><h2>Stock add form</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('stock.adjust') }}" data-stock-adjust-form novalidate>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>Check the stock details</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <label>
                    <span>Product</span>
                    <select name="product_id" required data-stock-product>
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option
                                value="{{ $product->id }}"
                                data-purchase-price="{{ $product->purchase_price }}"
                                data-selling-price="{{ $product->price }}"
                                @selected((string) old('product_id') === (string) $product->id)
                            >
                                {{ $product->name }} - {{ $product->sku ?: 'SKU-'.$product->id }} - Stock {{ $product->inventory }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Quantity</span>
                    <input type="number" name="adjustment" min="1" step="1" value="{{ old('adjustment', 1) }}" required data-replace-on-focus>
                    @error('adjustment') <small>{{ $message }}</small> @enderror
                </label>
                <p class="stock-adjust-hint">For reductions or corrections, use a negative value from the stock ledger workflow if needed.</p>

                <div class="field-grid">
                    <label>
                        <span>Purchase price</span>
                        <input type="number" name="purchase_price" min="0" step="0.01" value="{{ old('purchase_price') }}" required data-stock-purchase-price data-replace-on-focus>
                        @error('purchase_price') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Profit percentage</span>
                        <input type="number" name="profit_percentage" min="0" max="100" step="0.01" value="{{ old('profit_percentage') }}" required data-stock-profit-percentage data-replace-on-focus>
                        @error('profit_percentage') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="stock-price-preview" aria-live="polite">
                    Selling price after profit
                    <strong data-stock-selling-preview>Enter purchase price and profit</strong>
                </div>

                <label>
                    <span>Notes</span>
                    <textarea name="notes" rows="4" placeholder="Supplier receipt, shelf recount, opening stock, or correction note">{{ old('notes') }}</textarea>
                    @error('notes') <small>{{ $message }}</small> @enderror
                </label>

                <button class="product-save-button" type="submit" data-stock-adjust-submit>
                    <span class="product-save-button__idle">Add stock</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        Adding
                    </span>
                </button>
            </form>
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-stock-adjust-form]').forEach(function (form) {
                var productSelect = form.querySelector('[data-stock-product]');
                var purchasePriceInput = form.querySelector('[data-stock-purchase-price]');
                var profitPercentageInput = form.querySelector('[data-stock-profit-percentage]');
                var sellingPreview = form.querySelector('[data-stock-selling-preview]');
                var currency = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' });

                function numberValue(field) {
                    var value = parseFloat(field && field.value ? field.value : '');

                    return Number.isFinite(value) ? value : null;
                }

                function syncProductPricing() {
                    if (!productSelect || !purchasePriceInput || purchasePriceInput.value !== '') {
                        return;
                    }

                    var selected = productSelect.options[productSelect.selectedIndex];

                    if (selected && selected.dataset.purchasePrice) {
                        purchasePriceInput.value = parseFloat(selected.dataset.purchasePrice).toFixed(2);
                    }
                }

                function updateSellingPreview() {
                    if (!sellingPreview) {
                        return;
                    }

                    var purchasePrice = numberValue(purchasePriceInput);
                    var profitPercentage = numberValue(profitPercentageInput);

                    if (purchasePrice === null || profitPercentage === null) {
                        sellingPreview.textContent = 'Enter purchase price and profit';
                        return;
                    }

                    sellingPreview.textContent = currency.format(purchasePrice + (purchasePrice * (profitPercentage / 100)));
                }

                if (productSelect) {
                    productSelect.addEventListener('change', function () {
                        syncProductPricing();
                        updateSellingPreview();
                    });
                }

                [purchasePriceInput, profitPercentageInput].forEach(function (field) {
                    if (field) {
                        field.addEventListener('input', updateSellingPreview);
                    }
                });

                syncProductPricing();
                updateSellingPreview();

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

                form.addEventListener('submit', function (event) {
                    var firstInvalid = null;

                    form.querySelectorAll('input, select, textarea').forEach(function (field) {
                        if (!validateField(field) && !firstInvalid) {
                            firstInvalid = field;
                        }
                    });

                    if (firstInvalid) {
                        event.preventDefault();
                        firstInvalid.focus();
                        return;
                    }

                    var button = form.querySelector('[data-stock-adjust-submit]');

                    if (button) {
                        button.disabled = true;
                        button.classList.add('is-loading');
                        button.setAttribute('aria-busy', 'true');
                    }
                });
            });
        });
    </script>
@endsection
