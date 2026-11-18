<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.business_sign_up_zyloah_7f972f') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Zyloah-logo.png') }}">
    @include('partials.app-base-url')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
    @include('partials.localization')
</head>
<body class="auth-ui register-page">
    <header class="auth-language-header">@include('partials.language-selector')</header>
    <main class="register-shell">
        <section class="brand-panel">
            <nav class="topbar">
                <a class="brand-mark" href="{{ route('login') }}"><span class="brand-logo" role="img" aria-label="{{ __('common.zyloah_logo_216929') }}"></span> Zyloah</a>
                <span class="status-pill">{{ __('settings.multi_tenant_saas_4044c1') }}</span>
            </nav>
            <div class="brand-copy">
                <p class="eyebrow">{{ __('auth.business_sign_up_10418d') }}</p>
                <h1>{{ __('common.create_a_secure_workspace_for_your_store_76eeeb') }}</h1>
                <p class="lead">{{ __('products.each_business_gets_isolated_tenant_data_for_products_suppliers_custome_be3a40') }}</p>
            </div>
            <div class="insight-grid">
                <div><strong>{{ __('common.30_days_7d4278') }}</strong><span>{{ __('common.free_trial_for_one_owner_account_8d4912') }}</span></div>
                <div><strong>GST</strong><span>{{ __('orders.tax_ready_invoice_setup_86af63') }}</span></div>
                <div><strong>{{ __('settings.roles_47dcc2') }}</strong><span>{{ __('common.owner_manager_staff_warehouse_accountant_8f1a39') }}</span></div>
            </div>
        </section>
        <section class="form-panel">
            <div class="form-header">
                <h2>{{ __('auth.register_business_e15744') }}</h2>
                <p>{{ __('common.complete_owner_store_and_subscription_details_414f9b') }}</p>
                <p class="auth-switch">{{ __('auth.already_registered_b35d2d') }} <a href="{{ route('login') }}">{{ __('auth.log_in_f7c400') }}</a></p>
            </div>
            @if ($errors->any())
                <div class="error-summary" role="alert">
                    <strong>{{ __('common.check_the_form_48e029') }}</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif
            <form class="register-form" method="POST" action="{{ route('register.store') }}" data-register-form data-availability-url="{{ route('register.availability') }}" novalidate>
                @csrf
                <!-- <div class="register-progress" aria-label="{{ __('common.registration_steps_2af018') }}">
                    <span>{{ __('common.1_business_dceaeb') }}</span>
                    <span>{{ __('settings.2_plan_f7cc38') }}</span>
                    <span>{{ __('common.3_account_43fe48') }}</span>
                </div> -->

                <section class="auth-form-section">
                    <div class="auth-section-title">
                        <span>01</span>
                        <div>
                            <h3>{{ __('common.business_details_edbf43') }}</h3>
                            <p>{{ __('orders.used_for_tenant_setup_invoices_and_store_profile_afd9c4') }}</p>
                        </div>
                    </div>

                    <div class="field-grid">
                        <label>
                            <span>{{ __('common.business_name_a8e390') }}</span>
                            <input class="@error('business_name') is-invalid @enderror" type="text" name="business_name" value="{{ old('business_name') }}" placeholder="Mobile World" autocomplete="organization" required data-register-field="business_name" data-availability-field>
                            <small data-register-error="business_name" @if (! $errors->has('business_name')) hidden @endif>{{ $errors->first('business_name') }}</small>
                        </label>
                        <label>
                            <span>{{ __('common.owner_name_eb102d') }}</span>
                            <input class="@error('owner_name') is-invalid @enderror" type="text" name="owner_name" value="{{ old('owner_name') }}" placeholder="Ajay Kumar" autocomplete="name" required data-register-field="owner_name">
                            <small data-register-error="owner_name" @if (! $errors->has('owner_name')) hidden @endif>{{ $errors->first('owner_name') }}</small>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>{{ __('common.country_code_2eb6c4') }}</span>
                            <select class="@error('country_code') is-invalid @enderror" name="country_code" required data-register-field="country_code">
                                @foreach ($countryCodes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('country_code', '+91') === $code)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <small data-register-error="country_code" @if (! $errors->has('country_code')) hidden @endif>{{ $errors->first('country_code') }}</small>
                        </label>
                        <label>
                            <span>{{ __('common.mobile_number_8e5c12') }}</span>
                            <input class="@error('mobile') is-invalid @enderror" type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="9876543210" autocomplete="tel-national" inputmode="numeric" pattern="[0-9]{6,15}" maxlength="15" required data-register-field="mobile">
                            <small data-register-error="mobile" @if (! $errors->has('mobile')) hidden @endif>{{ $errors->first('mobile') }}</small>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>{{ __('common.email_84add5') }}</span>
                            <input class="@error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="owner@store.com" autocomplete="email" required data-register-field="email" data-availability-field>
                            <small data-register-error="email" @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</small>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>{{ __('common.gst_number_c8482e') }} <em>{{ __('common.optional_0c6c41') }}</em></span>
                            <input class="@error('gst_number') is-invalid @enderror" type="text" name="gst_number" value="{{ old('gst_number') }}" placeholder="22AAAAA0000A1Z5" maxlength="15" autocomplete="off" data-register-field="gst_number">
                            <small data-register-error="gst_number" @if (! $errors->has('gst_number')) hidden @endif>{{ $errors->first('gst_number') }}</small>
                        </label>
                        <label>
                            <span>{{ __('products.business_category_4f3a06') }}</span>
                            <select class="@error('business_category') is-invalid @enderror" name="business_category" required data-register-field="business_category">
                                <option value="">{{ __('products.select_category_a2a5ca') }}</option>
                                @foreach ($businessCategories as $value => $label)
                                    <option value="{{ $value }}" @selected((string) old('business_category') === (string) $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <small data-register-error="business_category" @if (! $errors->has('business_category')) hidden @endif>{{ $errors->first('business_category') }}</small>
                        </label>
                    </div>
                    <label>
                        <span>{{ __('common.store_address_934589') }}</span>
                        <textarea class="@error('store_address') is-invalid @enderror" name="store_address" rows="3" placeholder="{{ __('common.shop_number_street_city_state_7b082b') }}" autocomplete="street-address" required data-register-field="store_address">{{ old('store_address') }}</textarea>
                        <small data-register-error="store_address" @if (! $errors->has('store_address')) hidden @endif>{{ $errors->first('store_address') }}</small>
                    </label>
                </section>

                <fieldset class="auth-form-section">
                    <div class="auth-section-title">
                        <span>02</span>
                        <div>
                            <h3>{{ __('settings.choose_plan_6c5465') }}</h3>
                            <p>{{ __('settings.you_can_upgrade_later_from_subscription_settings_7c6cea') }}</p>
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
                            <h3>{{ __('common.account_security_0afc73') }}</h3>
                            <p>{{ __('auth.create_the_owner_login_for_this_business_workspace_7251d2') }}</p>
                        </div>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>{{ __('auth.password_8be3c9') }}</span>
                            <div class="password-field">
                                <input class="@error('password') is-invalid @enderror" type="password" name="password" placeholder="{{ __('common.minimum_8_characters_77c666') }}" autocomplete="new-password" minlength="8" required data-register-field="password">
                                <button type="button" class="password-toggle" data-toggle-password aria-label="{{ __('auth.show_password_044b85') }}" aria-pressed="false">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                            <div class="password-meter" aria-hidden="true"><i></i></div>
                            <small class="field-hint" data-password-hint>{{ __('common.use_at_least_8_characters_d76968') }}</small>
                            <small data-register-error="password" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</small>
                        </label>
                        <label>
                            <span>{{ __('auth.confirm_password_4a7c56') }}</span>
                            <div class="password-field">
                                <input class="@error('password_confirmation') is-invalid @enderror" type="password" name="password_confirmation" placeholder="{{ __('auth.repeat_password_366c24') }}" autocomplete="new-password" minlength="8" required data-register-field="password_confirmation">
                                <button type="button" class="password-toggle" data-toggle-password aria-label="{{ __('auth.show_password_confirmation_c4411f') }}" aria-pressed="false">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                            <small class="field-hint" data-confirm-hint>{{ __('auth.both_passwords_must_match_08f861') }}</small>
                            <small data-register-error="password_confirmation" @if (! $errors->has('password_confirmation')) hidden @endif>{{ $errors->first('password_confirmation') }}</small>
                        </label>
                    </div>
                </section>

                <label class="check-row terms-row">
                    <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted')) required data-register-field="terms_accepted">
                    <span>{{ __('common.i_confirm_the_business_details_are_accurate_and_i_am_authorized_to_cre_a47532') }}</span>
                </label>
                <small data-register-error="terms_accepted" @if (! $errors->has('terms_accepted')) hidden @endif>{{ $errors->first('terms_accepted') }}</small>

                <button type="submit" data-submit-button>{{ __('common.create_account_aaf374') }}</button>
            </form>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.closest('.password-field')?.querySelector('input');
                if (!input) return;
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                button.setAttribute('aria-label', isHidden ? window.t("auth.hide_password_e40123") : window.t("auth.show_password_044b85"));
                button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
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
            passwordHint.textContent = score >= 3 ? window.t("common.strong_enough_for_account_security_cff661") : window.t('auth.password_hint');

            if (confirmation.value) {
                const matches = password.value === confirmation.value;
                confirmation.setCustomValidity(matches ? '' : window.t("auth.passwords_do_not_match_f7c3cd"));
                confirmHint.textContent = matches ? window.t("auth.passwords_match_e49874") : window.t("auth.passwords_do_not_match_f7c3cd");
                confirmHint.classList.toggle('is-danger', !matches);
            }
        }

        password?.addEventListener('input', updatePasswordFeedback);
        confirmation?.addEventListener('input', updatePasswordFeedback);

        const registerForm = document.querySelector('[data-register-form]');

        function fieldLabel(field) {
            if (field.type === 'radio') {
                return window.t("settings.plan_ae2f98");
            }

            if (field.type === 'checkbox') {
                return window.t("common.confirmation_3424ed");
            }

            return field.closest('label')?.querySelector('span')?.textContent.trim().replace(/\s+Optional$/, '') || window.t("common.this_field_f89fd4");
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

        const availabilityTimers = new Map();
        const availabilityRequests = new Map();

        async function checkAvailability(field) {
            const value = field.value.trim();
            const error = registerForm?.querySelector(`[data-register-error="${field.name}"]`);

            if (!registerForm || !value || (field.type === 'email' && !field.validity.valid)) {
                return;
            }

            availabilityRequests.get(field.name)?.abort();
            const controller = new AbortController();
            availabilityRequests.set(field.name, controller);

            if (error) {
                error.textContent = window.t("common.checking_availability_f77ca5");
                error.classList.remove('is-success');
                error.hidden = false;
            }

            try {
                const body = new FormData();
                body.append('_token', registerForm.querySelector('input[name="_token"]').value);
                body.append('field', field.name);
                body.append('value', value);

                const response = await fetch(registerForm.dataset.availabilityUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                    signal: controller.signal,
                });
                const data = await response.json();

                if (field.value.trim() !== value) {
                    return;
                }

                field.setCustomValidity(data.available ? '' : data.message);
                field.classList.toggle('is-invalid', !data.available);
                field.setAttribute('aria-invalid', data.available ? 'false' : 'true');

                if (error) {
                    error.textContent = data.message;
                    error.classList.toggle('is-success', data.available);
                    error.hidden = false;
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    field.setCustomValidity('');
                    error?.classList.remove('is-success');
                    if (error) error.hidden = true;
                }
            }
        }

        registerForm?.querySelectorAll('[data-availability-field]').forEach((field) => {
            field.addEventListener('input', () => {
                window.clearTimeout(availabilityTimers.get(field.name));
                availabilityRequests.get(field.name)?.abort();
                field.setCustomValidity('');
                field.classList.remove('is-invalid');
                field.removeAttribute('aria-invalid');

                const error = registerForm.querySelector(`[data-register-error="${field.name}"]`);
                error?.classList.remove('is-success');
                if (error) {
                    error.textContent = '';
                    error.hidden = true;
                }

                availabilityTimers.set(field.name, window.setTimeout(() => checkAvailability(field), 500));
            });

            field.addEventListener('blur', () => {
                window.clearTimeout(availabilityTimers.get(field.name));
                checkAvailability(field);
            });
        });

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

                showRegisterError(form, field.name, window.t('validation.required', {attribute: fieldLabel(field)}));
                firstInvalid = firstInvalid || field;
            });

            if (!form.querySelector('input[name="plan"]:checked')) {
                showRegisterError(form, 'plan', window.t("validation.plan_is_required_223a1a"));
                firstInvalid = firstInvalid || firstField(form, 'plan');
            }

            if (password?.value && confirmation?.value && password.value !== confirmation.value) {
                showRegisterError(form, 'password_confirmation', window.t("auth.passwords_do_not_match_f7c3cd"));
                firstInvalid = firstInvalid || confirmation;
            }

            if (password?.value && password.value.length < 8) {
                showRegisterError(form, 'password', window.t('auth.password_min'));
                firstInvalid = firstInvalid || password;
            } else if (password?.value && (!/[A-Za-z]/.test(password.value) || !/\d/.test(password.value))) {
                showRegisterError(form, 'password', window.t("auth.password_must_include_at_least_one_letter_and_one_number_cfe47c"));
                firstInvalid = firstInvalid || password;
            }

            if (firstInvalid) {
                firstInvalid.focus();
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = window.t("common.creating_workspace_8dea91");
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
                    showRegisterError(form, name, messages[0] || data.message || window.t("validation.this_field_is_invalid_d3c603"));
                    firstInvalid = firstInvalid || firstField(form, name);
                });

                firstInvalid?.focus();
            } catch (error) {
                HTMLFormElement.prototype.submit.call(form);
                return;
            }

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = window.t("common.create_workspace_8e0938");
            }
        });
    </script>
</body>
</html>
