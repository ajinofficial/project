@extends('layouts.admin', ['title' => 'Store Setup'])

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
                <p class="eyebrow">Store settings</p>
                <h1>{{ $tenant->business_name }}</h1>
                <p>Configure billing defaults, tax, low-stock alerts, and staff role permissions for this tenant.</p>
            </div>
            <div class="setup-hero-panel">
                <div class="setup-progress-ring" style="--setup-progress: {{ $setupCompletionPercent }}%" aria-label="Setup completion {{ $setupCompletionPercent }} percent">
                    <strong>{{ $setupCompletionPercent }}%</strong>
                    <span>Ready</span>
                </div>
                <div>
                    <span>{{ $tenant->business_category_label }}</span>
                    <strong data-prefix-preview>{{ $settings['invoice_prefix'] }}</strong>
                    <small>Invoice prefix</small>
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
                        <p class="eyebrow">Billing defaults</p>
                        <h2>Invoice and tax setup</h2>
                    </div>
                    <a href="{{ route(\App\Support\RolePermission::firstAccessibleRoute(auth()->user())) }}">Skip for now</a>
                </div>

                <div class="setup-field-grid">
                    <label class="setup-field">
                        <span>Currency</span>
                        <select name="currency" @class(['is-invalid' => $errors->has('currency')]) required>
                            <option value="INR" @selected($settings['currency'] === 'INR')>&#8377; INR</option>
                        </select>
                        <small>Used across invoices and dashboard totals.</small>
                        @error('currency')<small class="field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="setup-field">
                        <span>Default tax percentage</span>
                        <div class="setup-input-affix">
                            <input type="number" name="default_tax_percentage" min="0" max="99.99" step="0.01" value="{{ $settings['default_tax_percentage'] }}" @class(['is-invalid' => $errors->has('default_tax_percentage')]) data-tax-input required>
                            <span>%</span>
                        </div>
                        <small>Applied to new products and purchase entries.</small>
                        @error('default_tax_percentage')<small class="field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="setup-field">
                        <span>Low-stock alert threshold</span>
                        <input type="number" name="low_stock_threshold" min="1" max="9999" value="{{ $settings['low_stock_threshold'] }}" @class(['is-invalid' => $errors->has('low_stock_threshold')]) data-stock-input required>
                        <small>Default minimum stock level for new items.</small>
                        @error('low_stock_threshold')<small class="field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="setup-field">
                        <span>Invoice prefix</span>
                        <input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] }}" maxlength="12" pattern="[A-Za-z0-9-]+" autocomplete="off" @class(['is-invalid' => $errors->has('invoice_prefix')]) data-prefix-input required>
                        <small>Use letters, numbers, or hyphens. Example: <span data-invoice-example>{{ $settings['invoice_prefix'] ?: 'INV' }}-{{ now()->format('Ymd') }}001</span></small>
                        @error('invoice_prefix')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>

                <div class="setup-preview-strip" aria-live="polite">
                    <div>
                        <span>Invoice preview</span>
                        <strong data-invoice-preview>{{ $settings['invoice_prefix'] ?: 'INV' }}-{{ now()->format('Ymd') }}001</strong>
                    </div>
                    <div>
                        <span>Product tax default</span>
                        <strong><b data-tax-preview>{{ $settings['default_tax_percentage'] }}</b>%</strong>
                    </div>
                    <div>
                        <span>Low-stock alert</span>
                        <strong><b data-stock-preview>{{ $settings['low_stock_threshold'] }}</b> units</strong>
                    </div>
                </div>
            </section>

            <aside class="setup-card setup-summary-card">
                <div class="setup-card-head">
                    <p class="eyebrow">Store profile</p>
                    <h2>Business details</h2>
                </div>
                <div class="setup-summary-item">
                    <span>Owner</span>
                    <strong>{{ $tenant->owner_name }}</strong>
                </div>
                <div class="setup-summary-item">
                    <span>Mobile</span>
                    <strong>{{ $tenant->mobile }}</strong>
                </div>
                <div class="setup-summary-item">
                    <span>Email</span>
                    <strong>{{ $tenant->email }}</strong>
                </div>
                <div class="setup-summary-address">
                    <span>Store address</span>
                    <p>{{ $tenant->store_address }}</p>
                </div>
            </aside>

            <section class="setup-card setup-roles-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Access control</p>
                        <h2>Role permissions</h2>
                    </div>
                    <a href="{{ route('role-permissions.index') }}">Manage permissions</a>
                </div>

                <div class="setup-role-grid">
                    @foreach ($rolePermissions as $role => $permissions)
                        <article>
                            <strong>{{ str_replace('_', ' ', $role) }}</strong>
                            <small>{{ count($permissions) }} menu{{ count($permissions) === 1 ? '' : 's' }}</small>
                            <div>
                                @foreach (array_slice($permissions, 0, 4) as $permission)
                                    <span>{{ $menus[$permission]['label'] ?? str_replace('_', ' ', $permission) }}</span>
                                @endforeach
                                @if (count($permissions) > 4)
                                    <span>+{{ count($permissions) - 4 }} more</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="setup-save-bar">
                <div>
                    <strong data-save-title>Store setup is current</strong>
                    <span data-save-copy>Changes apply to future invoices, alerts, and product defaults.</span>
                </div>
                <button type="submit" data-save-button disabled>Save setup</button>
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
                saveTitle.textContent = changed ? 'Unsaved setup changes' : 'Store setup is current';
                saveCopy.textContent = changed ? 'Review the previews and save when ready.' : 'Changes apply to future invoices, alerts, and product defaults.';
                form.classList.toggle('has-unsaved-changes', changed);
            }

            [prefixInput, taxInput, stockInput].forEach(function (field) {
                if (! field) {
                    return;
                }

                field.addEventListener('input', function () {
                    updatePreview();
                    updateSaveState();
                });
            });

            form.addEventListener('submit', function () {
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';
            });

            updatePreview();
            initialState = new FormData(form);
            updateSaveState();
        })();
    </script>
@endsection
