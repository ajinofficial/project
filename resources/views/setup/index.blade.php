@extends('layouts.admin', ['title' => __('settings.store_setup_755c06')])

@section('content')
    @php
        $settings = [
            'currency' => old('currency', $tenant->currency ?: 'INR'),
            'default_tax_percentage' => old('default_tax_percentage', $tenant->default_tax_percentage ?? 18),
            'low_stock_threshold' => old('low_stock_threshold', $tenant->low_stock_threshold ?? 10),
            'invoice_prefix' => old('invoice_prefix', $tenant->invoice_prefix ?: 'INV'),
        ];

        $rolePermissions = \App\Support\RolePermission::normalize($tenant->role_permissions ?? []);
        $menus = \App\Support\RolePermission::MENUS;
        $setupCompletion = collect([
            filled($tenant->business_name),
            filled($tenant->owner_name),
            filled($tenant->mobile),
            filled($tenant->email),
            filled($tenant->store_address),
            filled($settings['invoice_prefix']),
            filled($settings['default_tax_percentage']),
            filled($settings['low_stock_threshold']),
        ])->filter()->count();
        $setupCompletionPercent = (int) round(($setupCompletion / 8) * 100);
    @endphp

    <section class="setup-page">
        <header class="setup-hero">
            <div>
                <p class="eyebrow">{{ __('settings.store_settings_62bebf') }}</p>
                <h1>{{ $tenant->business_name }}</h1>
                <p>{{ __('notifications.configure_billing_defaults_tax_low_stock_alerts_and_staff_role_permiss_43f87b') }}</p>
            </div>
            <div class="setup-hero-panel">
                <div class="setup-progress-ring" style="--setup-progress: {{ $setupCompletionPercent }}%" aria-label="Setup completion {{ $setupCompletionPercent }} percent">
                    <strong>{{ $setupCompletionPercent }}%</strong>
                    <span>{{ __('common.ready_20c7c5') }}</span>
                </div>
                <div>
                    <span>{{ __($tenant->business_category_label) }}</span>
                    <strong data-prefix-preview>{{ $settings['invoice_prefix'] }}</strong>
                    <small>{{ __('orders.invoice_prefix_faa2b6') }}</small>
                </div>
            </div>
        </header>

        @if ($errors->any())
            <div class="error-summary" role="alert">
                @foreach ($errors->all() as $error)
                    <span>{{ $error }}</span>
                @endforeach
            </div>
        @endif

        <form class="setup-layout" method="POST" action="{{ route('setup.update') }}" data-setup-form>
            @csrf
            @method('PUT')

            <section class="setup-card setup-main-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('orders.billing_defaults_ddba22') }}</p>
                        <h2>{{ __('orders.invoice_and_tax_setup_ef59e1') }}</h2>
                    </div>
                    <a href="{{ route(\App\Support\RolePermission::firstAccessibleRoute(auth()->user())) }}">{{ __('common.skip_for_now_6fc096') }}</a>
                </div>

                <div class="setup-field-grid">
                    <label class="setup-field">
                        <span>{{ __('common.currency_e070de') }}</span>
                        <select name="currency" @class(['is-invalid' => $errors->has('currency')]) required>
                            <option value="INR" @selected($settings['currency'] === 'INR')>&#8377; INR</option>
                        </select>
                        <small>{{ __('orders.used_across_invoices_and_dashboard_totals_e905ad') }}</small>
                        @error('currency')<small class="field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="setup-field">
                        <span>{{ __('common.default_tax_percentage_8ebbbd') }}</span>
                        <div class="setup-input-affix">
                            <input type="number" name="default_tax_percentage" min="0" max="99.99" step="0.01" value="{{ $settings['default_tax_percentage'] }}" @class(['is-invalid' => $errors->has('default_tax_percentage')]) data-tax-input required>
                            <span>%</span>
                        </div>
                        <small>{{ __('products.applied_to_new_products_and_purchase_entries_e2d878') }}</small>
                        @error('default_tax_percentage')<small class="field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="setup-field">
                        <span>{{ __('notifications.low_stock_alert_threshold_7c69fa') }}</span>
                        <input type="number" name="low_stock_threshold" min="1" max="9999" value="{{ $settings['low_stock_threshold'] }}" @class(['is-invalid' => $errors->has('low_stock_threshold')]) data-stock-input required>
                        <small>{{ __('products.default_minimum_stock_level_for_new_items_528ea9') }}</small>
                        @error('low_stock_threshold')<small class="field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="setup-field">
                        <span>{{ __('orders.invoice_prefix_faa2b6') }}</span>
                        <input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] }}" maxlength="12" pattern="[A-Za-z0-9-]+" autocomplete="off" @class(['is-invalid' => $errors->has('invoice_prefix')]) data-prefix-input required>
                        <small>{{ __('common.use_letters_numbers_or_hyphens_example_ffb2e6') }} <span data-invoice-example>{{ $settings['invoice_prefix'] ?: 'INV' }}-{{ now()->format('Ymd') }}001</span></small>
                        @error('invoice_prefix')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>

                <div class="setup-preview-strip" aria-live="polite">
                    <div>
                        <span>{{ __('orders.invoice_preview_e6cf1d') }}</span>
                        <strong data-invoice-preview>{{ $settings['invoice_prefix'] ?: 'INV' }}-{{ now()->format('Ymd') }}001</strong>
                    </div>
                    <div>
                        <span>{{ __('products.product_tax_default_3b68c6') }}</span>
                        <strong><b data-tax-preview>{{ $settings['default_tax_percentage'] }}</b>%</strong>
                    </div>
                    <div>
                        <span>{{ __('notifications.low_stock_alert_93aa7e') }}</span>
                        <strong><b data-stock-preview>{{ $settings['low_stock_threshold'] }}</b> {{ __('common.units_917625') }}</strong>
                    </div>
                </div>
            </section>

            <aside class="setup-card setup-summary-card">
                <div class="setup-card-head">
                    <p class="eyebrow">{{ __('common.store_profile_ba2ce6') }}</p>
                    <h2>{{ __('common.business_details_edbf43') }}</h2>
                </div>
                <div class="setup-summary-item">
                    <span>{{ __('common.owner_89ff31') }}</span>
                    <strong>{{ $tenant->owner_name }}</strong>
                </div>
                <div class="setup-summary-item">
                    <span>{{ __('common.mobile_b1d702') }}</span>
                    <strong>{{ $tenant->mobile }}</strong>
                </div>
                <div class="setup-summary-item">
                    <span>{{ __('common.email_84add5') }}</span>
                    <strong>{{ $tenant->email }}</strong>
                </div>
                <div class="setup-summary-address">
                    <span>{{ __('common.store_address_934589') }}</span>
                    <p>{{ $tenant->store_address }}</p>
                </div>
            </aside>

            <section class="setup-card setup-roles-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('common.access_control_b3e3bb') }}</p>
                        <h2>{{ __('settings.role_permissions_4b9cd8') }}</h2>
                    </div>
                    <a href="{{ route('role-permissions.index') }}">{{ __('settings.manage_permissions_3fcf22') }}</a>
                </div>

                <div class="setup-role-grid">
                    @foreach ($rolePermissions as $role => $permissions)
                        <article>
                            <strong>{{ __(ucfirst(str_replace('_', ' ', $role))) }}</strong>
                            <small>{{ __('common.menus_count', ['count' => count($permissions)]) }}</small>
                            <div>
                                @foreach (array_slice($permissions, 0, 4) as $permission)
                                    <span>{{ __($menus[$permission]['label'] ?? ucfirst(str_replace('_', ' ', $permission))) }}</span>
                                @endforeach
                                @if (count($permissions) > 4)
                                    <span>{{ __('common.more_count', ['count' => count($permissions) - 4]) }}</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="setup-save-bar">
                <div>
                    <strong data-save-title>{{ __('settings.store_setup_is_current_dbf891') }}</strong>
                    <span data-save-copy>{{ __('notifications.changes_apply_to_future_invoices_alerts_and_product_defaults_4b5675') }}</span>
                </div>
                <button type="submit" data-save-button disabled>{{ __('settings.save_setup_1a0067') }}</button>
            </div>
        </form>
    </section>

    <script>
        (function () {
            var form = document.querySelector('[data-setup-form]');

            if (! form) {
                return;
            }

            var prefixInput = form.querySelector('[data-prefix-input]');
            var taxInput = form.querySelector('[data-tax-input]');
            var stockInput = form.querySelector('[data-stock-input]');
            var saveButton = form.querySelector('[data-save-button]');
            var saveTitle = form.querySelector('[data-save-title]');
            var saveCopy = form.querySelector('[data-save-copy]');
            var today = '{{ now()->format('Ymd') }}';
            var initialState;
            var fields = Array.from(form.querySelectorAll('input, select'));

            form.noValidate = true;

            function validationMessage(field) {
                var label = field.closest('.setup-field');
                var labelText = label?.querySelector(':scope > span')?.textContent.trim() || window.t("common.this_field_f89fd4");

                if (field.validity.valueMissing) {
                    return window.t('validation.required', {attribute: labelText});
                }

                if (field.validity.rangeUnderflow) {
                    return window.t('validation.min.numeric', {attribute: labelText, min: field.min});
                }

                if (field.validity.rangeOverflow) {
                    return window.t('validation.max.numeric', {attribute: labelText, max: field.max});
                }

                if (field.validity.patternMismatch) {
                    return window.t("orders.invoice_prefix_may_only_contain_letters_numbers_and_hyphens_bc7cac");
                }

                return window.localizedValidationMessage(field);
            }

            function validateField(field) {
                var label = field.closest('.setup-field');
                var error = label?.querySelector('[data-validation-error]');

                if (field.checkValidity()) {
                    field.classList.remove('is-invalid');
                    field.removeAttribute('aria-invalid');

                    if (error) {
                        error.remove();
                    }

                    return true;
                }

                if (! error) {
                    error = document.createElement('small');
                    error.dataset.validationError = '';
                    error.setAttribute('role', 'alert');
                    label.appendChild(error);
                }

                error.textContent = validationMessage(field);
                field.classList.add('is-invalid');
                field.setAttribute('aria-invalid', 'true');

                return false;
            }

            function fieldValue(name) {
                return (form.elements[name]?.value || '').trim();
            }

            function updatePreview() {
                var prefix = fieldValue('invoice_prefix').toUpperCase() || 'INV';
                var tax = fieldValue('default_tax_percentage') || '0';
                var stock = fieldValue('low_stock_threshold') || '0';
                var invoiceNumber = prefix + '-' + today + '001';

                if (prefixInput && prefixInput.value !== prefixInput.value.toUpperCase()) {
                    prefixInput.value = prefixInput.value.toUpperCase();
                }

                document.querySelectorAll('[data-prefix-preview]').forEach(function (item) {
                    item.textContent = prefix;
                });
                document.querySelectorAll('[data-invoice-example], [data-invoice-preview]').forEach(function (item) {
                    item.textContent = invoiceNumber;
                });
                document.querySelectorAll('[data-tax-preview]').forEach(function (item) {
                    item.textContent = tax;
                });
                document.querySelectorAll('[data-stock-preview]').forEach(function (item) {
                    item.textContent = stock;
                });
            }

            function hasChanges() {
                var currentState = new FormData(form);

                if (! initialState) {
                    return false;
                }

                for (var pair of currentState.entries()) {
                    if (initialState.get(pair[0]) !== pair[1]) {
                        return true;
                    }
                }

                return false;
            }

            function updateSaveState() {
                var changed = hasChanges();

                saveButton.disabled = ! changed;
                saveTitle.textContent = changed ? window.t("settings.unsaved_setup_changes_52f53c") : window.t("settings.store_setup_is_current_dbf891");
                saveCopy.textContent = changed ? window.t("common.review_the_previews_and_save_when_ready_1bd2ff") : window.t("notifications.changes_apply_to_future_invoices_alerts_and_product_defaults_4b5675");
                form.classList.toggle('has-unsaved-changes', changed);
            }

            [prefixInput, taxInput, stockInput].forEach(function (field) {
                if (! field) {
                    return;
                }

                field.addEventListener('input', function () {
                    validateField(field);
                    updatePreview();
                    updateSaveState();
                });

                field.addEventListener('blur', function () {
                    validateField(field);
                });
            });

            form.addEventListener('submit', function (event) {
                var firstInvalid = null;

                fields.forEach(function (field) {
                    if (! validateField(field) && ! firstInvalid) {
                        firstInvalid = field;
                    }
                });

                if (firstInvalid) {
                    event.preventDefault();
                    firstInvalid.focus();
                    saveTitle.textContent = window.t("common.please_correct_the_highlighted_fields_b479ae");
                    saveCopy.textContent = window.t("validation.required_fields_are_marked_with_a_red_error_message_e5c912");
                    return;
                }

                saveButton.disabled = true;
                saveButton.textContent = window.t("common.saving_ae7e88");
            });

            updatePreview();
            initialState = new FormData(form);
            updateSaveState();
        })();
    </script>
@endsection
