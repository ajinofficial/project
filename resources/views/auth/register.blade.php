<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business Sign-up - StockPilot</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="auth-ui register-page">
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
                <div><strong>30 days</strong><span>Free trial for one owner account</span></div>
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
            <form class="register-form" method="POST" action="{{ route('register.store') }}" data-register-form novalidate>
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
                            <input class="@error('business_name') is-invalid @enderror" type="text" name="business_name" value="{{ old('business_name') }}" placeholder="Mobile World" autocomplete="organization" required data-register-field="business_name">
                            <small data-register-error="business_name" @if (! $errors->has('business_name')) hidden @endif>{{ $errors->first('business_name') }}</small>
                        </label>
                        <label>
                            <span>Owner name</span>
                            <input class="@error('owner_name') is-invalid @enderror" type="text" name="owner_name" value="{{ old('owner_name') }}" placeholder="Ajay Kumar" autocomplete="name" required data-register-field="owner_name">
                            <small data-register-error="owner_name" @if (! $errors->has('owner_name')) hidden @endif>{{ $errors->first('owner_name') }}</small>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>Country code</span>
                            <select class="@error('country_code') is-invalid @enderror" name="country_code" required data-register-field="country_code">
                                @foreach ($countryCodes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('country_code', '+91') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small data-register-error="country_code" @if (! $errors->has('country_code')) hidden @endif>{{ $errors->first('country_code') }}</small>
                        </label>
                        <label>
                            <span>Mobile number</span>
                            <input class="@error('mobile') is-invalid @enderror" type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="9876543210" autocomplete="tel-national" inputmode="numeric" pattern="[0-9]{6,15}" maxlength="15" required data-register-field="mobile">
                            <small data-register-error="mobile" @if (! $errors->has('mobile')) hidden @endif>{{ $errors->first('mobile') }}</small>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>Email</span>
                            <input class="@error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="owner@store.com" autocomplete="email" required data-register-field="email">
                            <small data-register-error="email" @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</small>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>GST number <em>Optional</em></span>
                            <input class="@error('gst_number') is-invalid @enderror" type="text" name="gst_number" value="{{ old('gst_number') }}" placeholder="22AAAAA0000A1Z5" maxlength="15" autocomplete="off" data-register-field="gst_number">
                            <small data-register-error="gst_number" @if (! $errors->has('gst_number')) hidden @endif>{{ $errors->first('gst_number') }}</small>
                        </label>
                        <label>
                            <span>Business category</span>
                            <select class="@error('business_category') is-invalid @enderror" name="business_category" required data-register-field="business_category">
                                <option value="">Select category</option>
                                @foreach ($businessCategories as $value => $label)
                                    <option value="{{ $value }}" @selected((string) old('business_category') === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small data-register-error="business_category" @if (! $errors->has('business_category')) hidden @endif>{{ $errors->first('business_category') }}</small>
                        </label>
                    </div>
                    <label>
                        <span>Store address</span>
                        <textarea class="@error('store_address') is-invalid @enderror" name="store_address" rows="3" placeholder="Shop number, street, city, state" autocomplete="street-address" required data-register-field="store_address">{{ old('store_address') }}</textarea>
                        <small data-register-error="store_address" @if (! $errors->has('store_address')) hidden @endif>{{ $errors->first('store_address') }}</small>
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
                                <input type="radio" name="plan" value="{{ $plan->id }}" @checked((int) old('plan', $plans->firstWhere('id', 4)?->id ?? $plans->firstWhere('name', 'free_trial')?->id ?? $plans->firstWhere('name', 'starter')?->id ?? $plans->first()?->id) === (int) $plan->id) required data-register-field="plan">
                                <span>
                                    <b>{{ \Illuminate\Support\Str::headline($plan->name) }}</b>
                                    <small>
                                        @if ((int) $plan->monthly_price === 0)
                                            30-day free trial
                                        @else
                                            &#8377;{{ number_format($plan->monthly_price) }}/month
                                        @endif
                                    </small>
                                    <em>{{ $plan->features }}</em>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <small data-register-error="plan" @if (! $errors->has('plan')) hidden @endif>{{ $errors->first('plan') }}</small>
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
                                <input class="@error('password') is-invalid @enderror" type="password" name="password" placeholder="Minimum 8 characters" autocomplete="new-password" minlength="8" required data-register-field="password">
                                <button type="button" class="password-toggle" data-toggle-password aria-label="Show password">Show</button>
                            </div>
                            <div class="password-meter" aria-hidden="true"><i></i></div>
                            <small class="field-hint" data-password-hint>Use at least 8 characters.</small>
                            <small data-register-error="password" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</small>
                        </label>
                        <label>
                            <span>Confirm password</span>
                            <div class="password-field">
                                <input class="@error('password_confirmation') is-invalid @enderror" type="password" name="password_confirmation" placeholder="Repeat password" autocomplete="new-password" minlength="8" required data-register-field="password_confirmation">
                                <button type="button" class="password-toggle" data-toggle-password aria-label="Show password confirmation">Show</button>
                            </div>
                            <small class="field-hint" data-confirm-hint>Both passwords must match.</small>
                            <small data-register-error="password_confirmation" @if (! $errors->has('password_confirmation')) hidden @endif>{{ $errors->first('password_confirmation') }}</small>
                        </label>
                    </div>
                </section>

                <label class="check-row terms-row">
                    <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted')) required data-register-field="terms_accepted">
                    <span>I confirm the business details are accurate and I am authorized to create this workspace.</span>
                </label>
                <small data-register-error="terms_accepted" @if (! $errors->has('terms_accepted')) hidden @endif>{{ $errors->first('terms_accepted') }}</small>

                <button type="submit" data-submit-button>Create account</button>
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

        const registerForm = document.querySelector('[data-register-form]');

        function fieldLabel(field) {
            if (field.type === 'radio') {
                return 'Plan';
            }

            if (field.type === 'checkbox') {
                return 'Confirmation';
            }

            return field.closest('label')?.querySelector('span')?.textContent.trim().replace(/\s+Optional$/, '') || 'This field';
        }

        function showRegisterError(form, name, message) {
            const fields = form.querySelectorAll(`[data-register-field="${name}"]`);
            const error = form.querySelector(`[data-register-error="${name}"]`);

            fields.forEach((field) => {
                field.classList.add('is-invalid');
                field.setAttribute('aria-invalid', 'true');
            });

            if (error) {
                error.textContent = message;
                error.hidden = false;
            }
        }

        function clearRegisterErrors(form) {
            form.querySelectorAll('[data-register-error]').forEach((error) => {
                error.textContent = '';
                error.hidden = true;
            });

            form.querySelectorAll('[data-register-field]').forEach((field) => {
                field.classList.remove('is-invalid');
                field.removeAttribute('aria-invalid');
            });
        }

        function firstField(form, name) {
            return form.querySelector(`[data-register-field="${name}"]`);
        }

        registerForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            updatePasswordFeedback();

            const form = event.currentTarget;
            const submitButton = form.querySelector('[data-submit-button]');
            let firstInvalid = null;

            clearRegisterErrors(form);

            form.querySelectorAll('[data-register-field]').forEach((field) => {
                if (field.disabled || field.type === 'radio') {
                    return;
                }

                const isEmptyCheckbox = field.type === 'checkbox' && !field.checked;
                const isEmptyField = field.type !== 'checkbox' && field.required && field.value.trim() === '';

                if (!isEmptyCheckbox && !isEmptyField) {
                    return;
                }

                showRegisterError(form, field.name, `${fieldLabel(field)} is required.`);
                firstInvalid = firstInvalid || field;
            });

            if (!form.querySelector('input[name="plan"]:checked')) {
                showRegisterError(form, 'plan', 'Plan is required.');
                firstInvalid = firstInvalid || firstField(form, 'plan');
            }

            if (password?.value && confirmation?.value && password.value !== confirmation.value) {
                showRegisterError(form, 'password_confirmation', 'Passwords do not match.');
                firstInvalid = firstInvalid || confirmation;
            }

            if (firstInvalid) {
                firstInvalid.focus();
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Creating workspace...';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                const data = await response.json();

                if (response.ok && data.redirect) {
                    window.location.assign(data.redirect);
                    return;
                }

                Object.entries(data.errors || {}).forEach(([name, messages]) => {
                    showRegisterError(form, name, messages[0] || data.message || 'This field is invalid.');
                    firstInvalid = firstInvalid || firstField(form, name);
                });

                firstInvalid?.focus();
            } catch (error) {
                HTMLFormElement.prototype.submit.call(form);
                return;
            }

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Create workspace';
            }
        });
    </script>
</body>
</html>
