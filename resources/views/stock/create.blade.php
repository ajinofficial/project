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
            font-size: var(--text-xs);
            line-height: 1.45;
        }
        .stock-price-preview {
            border: 1px solid var(--v-line);
            border-radius: 8px;
            padding: 12px;
            background: #f8fafc;
            color: var(--v-muted);
            font-size: var(--text-xs);
            line-height: 1.45;
        }
        .stock-price-preview strong {
            display: block;
            margin-top: 4px;
            color: var(--v-text);
            font-size: var(--text-base);
        }
        .searchable-select__loader {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            color: var(--v-muted);
            font-size: var(--text-xs);
            font-weight: 700;
        }
        .searchable-select__loader::before {
            content: "";
            width: 15px;
            height: 15px;
            border: 2px solid #d8e2ef;
            border-top-color: var(--v-primary, #2f80ed);
            border-radius: 50%;
            animation: searchable-select-rotate .7s linear infinite;
        }
        @keyframes searchable-select-rotate {
            to { transform: rotate(360deg); }
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

                <label class="searchable-select" data-searchable-select data-searchable-select-url="{{ route('stock.products') }}">
                    <span>Product</span>
                    <input type="search" class="searchable-select__search" placeholder="Search or select product" autocomplete="off" role="combobox" aria-label="Search products" aria-expanded="false" data-searchable-select-input>
                    <div class="searchable-select__menu" role="listbox" data-searchable-select-menu hidden></div>
                    <select class="searchable-select__native" name="product_id" required data-stock-product tabindex="-1" aria-hidden="true">
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
                    <small class="searchable-select__status" data-searchable-select-status aria-live="polite"></small>
                    @error('product_id') <small>{{ $message }}</small> @enderror
                </label>

                <div class="field-grid">
                    <label>
                        <span>Quantity</span>
                        <input type="number" name="adjustment" min="1" step="1" value="{{ old('adjustment', 1) }}" required data-replace-on-focus>
                        @error('adjustment') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Stock date</span>
                        <input type="date" name="stock_date" value="{{ old('stock_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required data-date-picker>
                        @error('stock_date') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
                <p class="stock-adjust-hint">For reductions or corrections, use a negative value from the stock ledger workflow if needed.</p>

                <div class="field-grid">
                    <label>
                        <span>Purchase price</span>
                        <input type="number" name="purchase_price" min="0" step="0.01" value="{{ old('purchase_price') }}" required data-stock-purchase-price data-replace-on-focus>
                        @error('purchase_price') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Profit type</span>
                        <select name="profit_type" required data-stock-profit-type>
                            <option value="percentage" @selected(old('profit_type', 'percentage') === 'percentage')>Percentage (%)</option>
                            <option value="amount" @selected(old('profit_type') === 'amount')>Fixed amount per unit (₹)</option>
                        </select>
                        @error('profit_type') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="field-grid">
                    @php($profitIsAmount = old('profit_type', 'percentage') === 'amount')
                    <label>
                        <span data-stock-profit-label>{{ $profitIsAmount ? 'Profit amount per unit (₹)' : 'Profit percentage' }}</span>
                        <input type="number" name="{{ $profitIsAmount ? 'profit_amount' : 'profit_percentage' }}" min="0" max="{{ $profitIsAmount ? '99999999.99' : '100' }}" step="0.01" value="{{ old($profitIsAmount ? 'profit_amount' : 'profit_percentage') }}" required data-stock-profit-value data-replace-on-focus>
                        @error('profit_percentage') <small>{{ $message }}</small> @enderror
                        @error('profit_amount') <small>{{ $message }}</small> @enderror
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
                var profitInput = form.querySelector('[data-stock-profit-value]');
                var profitTypeInput = form.querySelector('[data-stock-profit-type]');
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
                    var isAmount = profitTypeInput.value === 'amount';
                    var profit = numberValue(profitInput);

                    if (purchasePrice === null || profit === null || purchasePrice < 0 || profit < 0 || (!isAmount && profit > 100)) {
                        sellingPreview.textContent = 'Enter purchase price and profit';
                        return;
                    }

                    var sellingPrice = purchasePrice + (isAmount ? profit : purchasePrice * profit / 100);
                    sellingPreview.textContent = sellingPrice > 99999999.99
                        ? 'Selling price is too high'
                        : currency.format(sellingPrice);
                }

                function syncProfitType() {
                    var isAmount = profitTypeInput.value === 'amount';
                    profitInput.name = isAmount ? 'profit_amount' : 'profit_percentage';
                    profitInput.max = isAmount ? '99999999.99' : '100';
                    form.querySelector('[data-stock-profit-label]').textContent = isAmount ? 'Profit amount per unit (₹)' : 'Profit percentage';
                    var error = existingErrorElement(profitInput);
                    if (error) {
                        error.textContent = '';
                        error.hidden = true;
                    }
                    profitInput.classList.remove('is-invalid');
                    profitInput.removeAttribute('aria-invalid');
                    updateSellingPreview();
                }
                profitTypeInput.addEventListener('change', function () {
                    profitInput.value = '0';
                    syncProfitType();
                });
                syncProfitType();

                if (productSelect) {
                    productSelect.addEventListener('change', function () {
                        syncProductPricing();
                        updateSellingPreview();
                    });
                }

                [purchasePriceInput, profitInput].forEach(function (field) {
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

    @include('partials.searchable-select-script')
@endsection
