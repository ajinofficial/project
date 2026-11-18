<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.choose_a_new_password_zyloah_eedf54') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Zyloah-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
    @include('partials.localization')
</head>
<body class="auth-ui password-page">
    <header class="auth-language-header">@include('partials.language-selector')</header>
    <main class="password-shell">
        <a class="password-brand" href="{{ route('login') }}"><span class="brand-logo"></span><strong>Zyloah</strong></a>
        <section class="password-card" aria-labelledby="password-title">
            <p class="eyebrow">{{ __('common.verified_aed3b8') }}</p>
            <h1 id="password-title">{{ __('auth.choose_a_new_password_3f657f') }}</h1>
            <p>{{ __('common.use_at_least_eight_characters_and_keep_it_private_106a9a') }}</p>
            <form class="register-form" method="POST" action="{{ route('password.update') }}" data-reset-password-form novalidate>
                @csrf
                <label>
                    <span>{{ __('auth.new_password_d850ee') }}</span>
                    <div class="password-field">
                        <input class="@error('password') is-invalid @enderror" type="password" name="password" autocomplete="new-password" minlength="8" required autofocus data-reset-field="password">
                        <button type="button" class="password-toggle" data-toggle-password aria-label="{{ __('auth.show_new_password_9a5697') }}" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    <small data-reset-error="password" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</small>
                </label>
                <label>
                    <span>{{ __('auth.confirm_new_password_f85039') }}</span>
                    <div class="password-field">
                        <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required data-reset-field="password_confirmation">
                        <button type="button" class="password-toggle" data-toggle-password aria-label="{{ __('auth.show_password_confirmation_c4411f') }}" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    <small data-reset-error="password_confirmation" hidden></small>
                </label>
                <button type="submit" data-reset-password-submit>
                    <span class="reset-password-submit__idle">{{ __('auth.reset_password_5c4bc9') }}</span>
                    <span class="reset-password-submit__loading" aria-hidden="true">
                        <span class="reset-password-submit__spinner"></span>
                        {{ __('common.resetting_ee41f7') }}
                    </span>
                </button>
            </form>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.closest('.password-field')?.querySelector('input');
                if (!input) return;

                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', show ? 'true' : 'false');
                button.setAttribute('aria-label', show ? window.t("auth.hide_password_e40123") : window.t("auth.show_password_044b85"));
            });
        });

        const form = document.querySelector('[data-reset-password-form]');

        form?.addEventListener('submit', (event) => {
            const password = form.querySelector('[name="password"]');
            const confirmation = form.querySelector('[name="password_confirmation"]');
            const submitButton = form.querySelector('[data-reset-password-submit]');
            const errors = {
                password: form.querySelector('[data-reset-error="password"]'),
                password_confirmation: form.querySelector('[data-reset-error="password_confirmation"]'),
            };
            let firstInvalid = null;

            Object.values(errors).forEach((error) => {
                error.textContent = '';
                error.hidden = true;
            });
            form.querySelectorAll('[data-reset-field]').forEach((field) => {
                field.classList.remove('is-invalid');
                field.removeAttribute('aria-invalid');
            });

            const showError = (field, message) => {
                const input = form.querySelector(`[name="${field}"]`);
                errors[field].textContent = message;
                errors[field].hidden = false;
                input.classList.add('is-invalid');
                input.setAttribute('aria-invalid', 'true');
                firstInvalid ||= input;
            };

            if (!password.value) {
                showError('password', window.t("auth.new_password_is_required_d2eb9a"));
            } else if (password.value.length < 8) {
                showError('password', window.t("auth.new_password_must_be_at_least_8_characters_f36bc7"));
            } else if (!/[A-Za-z]/.test(password.value) || !/\d/.test(password.value)) {
                showError('password', window.t("auth.new_password_must_include_at_least_one_letter_and_one_number_216242"));
            }

            if (!confirmation.value) {
                showError('password_confirmation', window.t("auth.password_confirmation_is_required_5a5b6d"));
            } else if (password.value !== confirmation.value) {
                showError('password_confirmation', window.t("auth.the_password_confirmation_does_not_match_d54123"));
            }

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
                return;
            }

            submitButton.disabled = true;
            submitButton.classList.add('is-loading');
            submitButton.setAttribute('aria-busy', 'true');
        });

        window.addEventListener('pageshow', () => {
            const submitButton = form?.querySelector('[data-reset-password-submit]');
            if (!submitButton) return;

            submitButton.disabled = false;
            submitButton.classList.remove('is-loading');
            submitButton.removeAttribute('aria-busy');
        });
    </script>
</body>
</html>
