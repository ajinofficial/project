@extends('layouts.admin', ['title' => __('products.add_stock_492af0')])

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
                <p class="eyebrow">{{ __('products.stock_bcecf4') }}</p>
                <h1>{{ __('products.add_stock_64db16') }}</h1>
                <span>{{ __('products.use_this_form_for_stock_receiving_recount_corrections_and_manual_stock_b3bd04') }}</span>
            </div>
            <div class="product-head-actions">
                <a class="ghost-button" href="{{ route('stock.index') }}">{{ __('products.back_to_stock_16922d') }}</a>
                <a class="ghost-button" href="{{ route('purchases.index') }}">{{ __('orders.purchase_entry_523322') }}</a>
            </div>
        </header>

        <section class="v-panel product-management-panel stock-form-panel">
            <div class="section-title"><div><p class="eyebrow">{{ __('products.stock_control_3a6cb5') }}</p><h2>{{ __('products.stock_add_form_7a1ca8') }}</h2></div></div>
            <form class="product-form" method="POST" action="{{ route('stock.adjust') }}" data-stock-adjust-form novalidate>
                @csrf

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>{{ __('products.check_the_stock_details_6b84a4') }}</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <label class="searchable-select" data-searchable-select data-searchable-select-url="{{ route('stock.products') }}">
                    <span>{{ __('products.product_dd3b86') }}</span>
                    <input type="search" class="searchable-select__search" placeholder="{{ __('products.search_or_select_product_8ab7d7') }}" autocomplete="off" role="combobox" aria-label="{{ __('products.search_products_4412bf') }}" aria-expanded="false" data-searchable-select-input>
                    <div class="searchable-select__menu" role="listbox" data-searchable-select-menu hidden></div>
                    <select class="searchable-select__native" name="product_id" required data-stock-product tabindex="-1" aria-hidden="true">
                        <option value="">{{ __('products.select_product_596d90') }}</option>
                        @foreach ($products as $product)
                            <option
                                value="{{ $product->id }}"
                                data-purchase-price="{{ $product->purchase_price }}"
                                data-selling-price="{{ $product->price }}"
                                @selected((string) old('product_id') === (string) $product->id)
                            >
                                {{ __('common.stock_label', ['name' => $product->localized('name'), 'sku' => $product->sku ?: 'SKU-'.$product->id, 'stock' => $product->inventory]) }}
                            </option>
                        @endforeach
                    </select>
                    <small class="searchable-select__status" data-searchable-select-status aria-live="polite"></small>
                    @error('product_id') <small>{{ $message }}</small> @enderror
                </label>

                <div class="field-grid">
                    <label>
                        <span>{{ __('common.quantity_44f6af') }}</span>
                        <input type="number" name="adjustment" min="1" step="1" value="{{ old('adjustment', 1) }}" required data-replace-on-focus>
                        @error('adjustment') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>{{ __('products.stock_date_c91846') }}</span>
                        <input type="date" name="stock_date" value="{{ old('stock_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required data-date-picker>
                        @error('stock_date') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
                <p class="stock-adjust-hint">{{ __('products.for_reductions_or_corrections_use_a_negative_value_from_the_stock_ledg_134eca') }}</p>

                <div class="field-grid">
                    <label>
                        <span>{{ __('orders.purchase_price_b21bc6') }}</span>
                        <input type="number" name="purchase_price" min="0" step="0.01" value="{{ old('purchase_price') }}" required data-stock-purchase-price data-replace-on-focus>
                        @error('purchase_price') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>{{ __('products.profit_type_90013b') }}</span>
                        <select name="profit_type" required data-stock-profit-type>
                            <option value="percentage" @selected(old('profit_type', 'percentage') === 'percentage')>{{ __('common.percentage_a92697') }}</option>
                            <option value="amount" @selected(old('profit_type') === 'amount')>{{ __('common.fixed_amount_per_unit_7df184') }}</option>
                        </select>
                        @error('profit_type') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="field-grid">
                    @php($profitIsAmount = old('profit_type', 'percentage') === 'amount')
                    <label>
                        <span data-stock-profit-label>{{ $profitIsAmount ? __('products.profit_amount_per_unit') : __('products.profit_percentage_5a009b') }}</span>
                        <input type="number" name="{{ $profitIsAmount ? 'profit_amount' : 'profit_percentage' }}" min="0" max="{{ $profitIsAmount ? '99999999.99' : '100' }}" step="0.01" value="{{ old($profitIsAmount ? 'profit_amount' : 'profit_percentage') }}" required data-stock-profit-value data-replace-on-focus>
                        @error('profit_percentage') <small>{{ $message }}</small> @enderror
                        @error('profit_amount') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="stock-price-preview" aria-live="polite">
                    {{ __('products.selling_price_after_profit_fcadc7') }}
                    <strong data-stock-selling-preview>{{ __('products.enter_purchase_price_and_profit_018f5c') }}</strong>
                </div>

                <label>
                    <span>{{ __('common.notes_704400') }}</span>
                    <textarea name="notes" rows="4" placeholder="{{ __('products.supplier_receipt_shelf_recount_opening_stock_or_correction_note_703602') }}">{{ old('notes') }}</textarea>
                    @error('notes') <small>{{ $message }}</small> @enderror
                </label>

                <button class="product-save-button" type="submit" data-stock-adjust-submit>
                    <span class="product-save-button__idle">{{ __('products.add_stock_64db16') }}</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        {{ __('common.adding_48a693') }}
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
                        sellingPreview.textContent = window.t("products.enter_purchase_price_and_profit_018f5c");
                        return;
                    }

                    var sellingPrice = purchasePrice + (isAmount ? profit : purchasePrice * profit / 100);
                    sellingPreview.textContent = sellingPrice > 99999999.99
                        ? window.t("validation.selling_price_is_too_high_41d35e")
                        : currency.format(sellingPrice);
                }

                function syncProfitType() {
                    var isAmount = profitTypeInput.value === 'amount';
                    profitInput.name = isAmount ? 'profit_amount' : 'profit_percentage';
                    profitInput.max = isAmount ? '99999999.99' : '100';
                    form.querySelector('[data-stock-profit-label]').textContent = isAmount ? window.t('products.profit_amount_per_unit') : window.t("products.profit_percentage_5a009b");
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

                    return labelText ? labelText.textContent.trim() : window.t("common.this_field_f89fd4");
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
                        ? window.t('validation.required', {attribute: fieldLabel(field)})
                        : window.localizedValidationMessage(field);
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
