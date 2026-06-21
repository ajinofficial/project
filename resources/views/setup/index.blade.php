@extends('layouts.admin', ['title' => 'Store Setup'])

@section('content')
    <section class="setup-page">
        <header class="setup-hero">
            <div>
                <p class="eyebrow">Store settings</p>
                <h1>{{ $tenant->business_name }}</h1>
                <p>Configure billing defaults, tax, low-stock alerts, and staff role permissions for this tenant.</p>
            </div>
            <div class="setup-hero-meta">
                <span>{{ $tenant->business_category_label }}</span>
                <strong>{{ $tenant->invoice_prefix ?: 'INV' }}</strong>
                <small>Invoice prefix</small>
            </div>
        </header>

        <form class="setup-layout" method="POST" action="{{ route('setup.update') }}">
            @csrf
            @method('PUT')

            <section class="setup-card setup-main-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Billing defaults</p>
                        <h2>Invoice and tax setup</h2>
                    </div>
                    <a href="{{ route('dashboard') }}">Skip for now</a>
                </div>

                <div class="setup-field-grid">
                    <label>
                        <span>Currency</span>
                        <select name="currency" required>
                            <option value="INR" @selected(old('currency', $tenant->currency) === 'INR')>&#8377; INR</option>
                        </select>
                        <small>Used across invoices and dashboard totals.</small>
                    </label>

                    <label>
                        <span>Default tax percentage</span>
                        <input type="number" name="default_tax_percentage" min="0" max="99.99" step="0.01" value="{{ old('default_tax_percentage', $tenant->default_tax_percentage) }}" required>
                        <small>Applied to new products and purchase entries.</small>
                    </label>

                    <label>
                        <span>Low-stock alert threshold</span>
                        <input type="number" name="low_stock_threshold" min="1" max="9999" value="{{ old('low_stock_threshold', $tenant->low_stock_threshold) }}" required>
                        <small>Default minimum stock level for new items.</small>
                    </label>

                    <label>
                        <span>Invoice prefix</span>
                        <input name="invoice_prefix" value="{{ old('invoice_prefix', $tenant->invoice_prefix) }}" maxlength="12" required>
                        <small>Example: {{ old('invoice_prefix', $tenant->invoice_prefix) ?: 'INV' }}-{{ now()->format('Ymd') }}001</small>
                    </label>
                </div>
            </section>

            <aside class="setup-card setup-summary-card">
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
                    @foreach (($tenant->role_permissions ?? []) as $role => $permissions)
                        <article>
                            <strong>{{ str_replace('_', ' ', ucfirst($role)) }}</strong>
                            <div>
                                @foreach ($permissions as $permission)
                                    <span>{{ str_replace('_', ' ', $permission) }}</span>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="setup-save-bar">
                <div>
                    <strong>Ready to save setup?</strong>
                    <span>Changes apply to future invoices, alerts, and product defaults.</span>
                </div>
                <button type="submit">Save setup</button>
            </div>
        </form>
    </section>
@endsection
