<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business Sign-up - StockPilot</title>
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
                <div class="error-summary" role="alert">
                    <strong>Check the form</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif
            <form class="register-form" method="POST" action="{{ route('register.store') }}">
                @csrf
                <!-- <div class="register-progress" aria-label="Registration steps">
                    <span>1 Business</span>
                    <span>2 Plan</span>
                    <span>3 Account</span>
                </div> -->

                <section class="auth-form-section">
                    <div class="auth-section-title">
                        <span>01</span>
                        <div>
                            <h3>Business details</h3>
                            <p>Used for tenant setup, invoices, and store profile.</p>
                        </div>
                    </div>

                    <div class="field-grid">
                        <label>
                            <span>Business name</span>
                            <input class="@error('business_name') is-invalid @enderror" name="business_name" value="{{ old('business_name') }}" placeholder="Mobile World" autocomplete="organization" required>
                            @error('business_name')<small>{{ $message }}</small>@enderror
                        </label>
                        <label>
                            <span>Owner name</span>
                            <input class="@error('owner_name') is-invalid @enderror" name="owner_name" value="{{ old('owner_name') }}" placeholder="Ajay Kumar" autocomplete="name" required>
                            @error('owner_name')<small>{{ $message }}</small>@enderror
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>Mobile number</span>
                            <input class="@error('mobile') is-invalid @enderror" type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="+91 98765 43210" autocomplete="tel" inputmode="tel" required>
                            @error('mobile')<small>{{ $message }}</small>@enderror
                        </label>
                        <label>
                            <span>Email</span>
                            <input class="@error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="owner@store.com" autocomplete="email" required>
                            @error('email')<small>{{ $message }}</small>@enderror
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>GST number <em>Optional</em></span>
                            <input class="@error('gst_number') is-invalid @enderror" name="gst_number" value="{{ old('gst_number') }}" placeholder="22AAAAA0000A1Z5" maxlength="15" autocomplete="off">
                            @error('gst_number')<small>{{ $message }}</small>@enderror
                        </label>
                        <label>
                            <span>Business category</span>
                            <select class="@error('business_category') is-invalid @enderror" name="business_category" required>
                                <option value="">Select category</option>
                                @foreach ($businessCategories as $value => $label)
                                    <option value="{{ $value }}" @selected((string) old('business_category') === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('business_category')<small>{{ $message }}</small>@enderror
                        </label>
                    </div>
                    <label>
                        <span>Store address</span>
                        <textarea class="@error('store_address') is-invalid @enderror" name="store_address" rows="3" placeholder="Shop number, street, city, state" autocomplete="street-address" required>{{ old('store_address') }}</textarea>
                        @error('store_address')<small>{{ $message }}</small>@enderror
                    </label>
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
                                <input type="radio" name="plan" value="{{ $plan->id }}" @checked((int) old('plan', $plans->firstWhere('name', 'starter')?->id ?? $plans->first()?->id) === (int) $plan->id) required>
                                <span>
                                    <b>{{ ucfirst($plan->name) }}</b>
                                    <small>&#8377;{{ number_format($plan->monthly_price) }}/month</small>
                                    <em>{{ $plan->features }}</em>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('plan')<small>{{ $message }}</small>@enderror
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
                        <label>
                            <span>Password</span>
                            <div class="password-field">
                                <input class="@error('password') is-invalid @enderror" type="password" name="password" placeholder="Minimum 8 characters" autocomplete="new-password" minlength="8" required>
                                <button type="button" class="password-toggle" data-toggle-password aria-label="Show password">Show</button>
                            </div>
                            <div class="password-meter" aria-hidden="true"><i></i></div>
                            <small class="field-hint" data-password-hint>Use at least 8 characters.</small>
                            @error('password')<small>{{ $message }}</small>@enderror
                        </label>
                        <label>
                            <span>Confirm password</span>
                            <div class="password-field">
                                <input class="@error('password') is-invalid @enderror" type="password" name="password_confirmation" placeholder="Repeat password" autocomplete="new-password" minlength="8" required>
                                <button type="button" class="password-toggle" data-toggle-password aria-label="Show password confirmation">Show</button>
                            </div>
                            <small class="field-hint" data-confirm-hint>Both passwords must match.</small>
                        </label>
                    </div>
                </section>

                <label class="check-row terms-row">
                    <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted')) required>
                    <span>I confirm the business details are accurate and I am authorized to create this workspace.</span>
                </label>
                @error('terms_accepted')<small>{{ $message }}</small>@enderror

                <button type="submit" data-submit-button>Create workspace</button>
            </form>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.previousElementSibling;
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                button.textContent = isHidden ? 'Hide' : 'Show';
            });
        });

        const password = document.querySelector('input[name="password"]');
        const confirmation = document.querySelector('input[name="password_confirmation"]');
        const meter = document.querySelector('.password-meter i');
        const passwordHint = document.querySelector('[data-password-hint]');
        const confirmHint = document.querySelector('[data-confirm-hint]');

        function updatePasswordFeedback() {
            if (!password || !confirmation || !meter) {
                return;
            }

            const value = password.value;
            const score = [
                value.length >= 8,
                /[a-z]/.test(value) && /[A-Z]/.test(value),
                /\d/.test(value),
                /[^A-Za-z0-9]/.test(value),
            ].filter(Boolean).length;

            meter.style.width = `${Math.max(score, value ? 1 : 0) * 25}%`;
            meter.dataset.score = String(score);
            passwordHint.textContent = score >= 3 ? 'Strong enough for account security.' : 'Use 8+ characters with letters and numbers.';

            if (confirmation.value) {
                const matches = password.value === confirmation.value;
                confirmation.setCustomValidity(matches ? '' : 'Passwords do not match.');
                confirmHint.textContent = matches ? 'Passwords match.' : 'Passwords do not match.';
                confirmHint.classList.toggle('is-danger', !matches);
            }
        }

        password?.addEventListener('input', updatePasswordFeedback);
        confirmation?.addEventListener('input', updatePasswordFeedback);

        document.querySelector('.register-form')?.addEventListener('submit', (event) => {
            updatePasswordFeedback();

            if (!event.currentTarget.checkValidity()) {
                event.preventDefault();
                event.currentTarget.reportValidity();
                return;
            }

            const submitButton = document.querySelector('[data-submit-button]');
            submitButton.disabled = true;
            submitButton.textContent = 'Creating workspace...';
        });
    </script>
</body>
</html>
