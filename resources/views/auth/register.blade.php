<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business Sign-up - StockPilot</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="auth-ui">
    <main class="register-shell">
        <section class="brand-panel">
            <nav class="topbar">
                <a class="brand-mark" href="{{ route('login') }}"><span class="brand-icon">SP</span> StockPilot</a>
                <span class="status-pill">Multi-tenant SaaS</span>
            </nav>
            <div class="brand-copy">
                <p class="eyebrow">Business sign-up</p>
                <h1>Create a secure workspace for your store.</h1>
                <p class="lead">Each business gets isolated tenant data for products, suppliers, customers, purchases, billing, reports, and audit logs.</p>
            </div>
            <div class="insight-grid">
                <div><strong>&#8377;499</strong><span>Starter plan from day one</span></div>
                <div><strong>GST</strong><span>Tax-ready invoice setup</span></div>
                <div><strong>Roles</strong><span>Owner, manager, staff, warehouse, accountant</span></div>
            </div>
        </section>
        <section class="form-panel">
            <div class="form-header">
                <h2>Register business</h2>
                <p>Complete owner, store, and subscription details.</p>
                <p class="auth-switch">Already registered? <a href="{{ route('login') }}">Log in</a></p>
            </div>
            @if ($errors->any())
                <div class="error-summary"><strong>Check the form</strong><span>{{ $errors->first() }}</span></div>
            @endif
            <form class="register-form" method="POST" action="{{ route('register.store') }}">
                @csrf
                <div class="register-progress" aria-label="Registration steps">
                    <span>1 Business</span>
                    <span>2 Plan</span>
                    <span>3 Account</span>
                </div>

                <section class="auth-form-section">
                    <div class="auth-section-title">
                        <span>01</span>
                        <div>
                            <h3>Business details</h3>
                            <p>Used for tenant setup, invoices, and store profile.</p>
                        </div>
                    </div>

                    <div class="field-grid">
                        <label><span>Business name</span><input name="business_name" value="{{ old('business_name') }}" placeholder="Mobile World" required></label>
                        <label><span>Owner name</span><input name="owner_name" value="{{ old('owner_name') }}" placeholder="Ajay Kumar" required></label>
                    </div>
                    <div class="field-grid">
                        <label><span>Mobile number</span><input name="mobile" value="{{ old('mobile') }}" placeholder="+91 98765 43210" required></label>
                        <label><span>Email</span><input type="email" name="email" value="{{ old('email') }}" placeholder="owner@store.com" required></label>
                    </div>
                    <div class="field-grid">
                        <label><span>GST number</span><input name="gst_number" value="{{ old('gst_number') }}" placeholder="22AAAAA0000A1Z5"></label>
                        <label>
                            <span>Business category</span>
                            <select name="business_category" required>
                                <option value="">Select category</option>
                                @foreach ($businessCategories as $value => $label)
                                    <option value="{{ $value }}" @selected((string) old('business_category') === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label><span>Store address</span><textarea name="store_address" rows="3" placeholder="Shop number, street, city, state" required>{{ old('store_address') }}</textarea></label>
                </section>

                <fieldset class="auth-form-section">
                    <div class="auth-section-title">
                        <span>02</span>
                        <div>
                            <h3>Choose plan</h3>
                            <p>You can upgrade later from subscription settings.</p>
                        </div>
                    </div>
                    <div class="plan-options">
                        @foreach ($plans as $plan)
                            <label>
                                <input type="radio" name="plan" value="{{ $plan->id }}" @checked((int) old('plan', $plans->firstWhere('name', 'starter')?->id ?? $plans->first()?->id) === (int) $plan->id)>
                                <span>
                                    <b>{{ ucfirst($plan->name) }}</b>
                                    <small>&#8377;{{ number_format($plan->monthly_price) }}/month</small>
                                    <em>{{ $plan->features }}</em>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <section class="auth-form-section">
                    <div class="auth-section-title">
                        <span>03</span>
                        <div>
                            <h3>Account security</h3>
                            <p>Create the owner login for this business workspace.</p>
                        </div>
                    </div>
                    <div class="field-grid">
                        <label><span>Password</span><input type="password" name="password" placeholder="Minimum 8 characters" required></label>
                        <label><span>Confirm password</span><input type="password" name="password_confirmation" placeholder="Repeat password" required></label>
                    </div>
                </section>

                <button type="submit">Create workspace</button>
            </form>
        </section>
    </main>
</body>
</html>
