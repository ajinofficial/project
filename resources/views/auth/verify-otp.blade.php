<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.verify_code_zyloah_a911d3') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Zyloah-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
    @include('partials.localization')
</head>
<body class="auth-ui password-page">
    <header class="auth-language-header">@include('partials.language-selector')</header>
    <main class="password-shell">
        <a class="password-brand" href="{{ route('login') }}"><span class="brand-logo"></span><strong>Zyloah</strong></a>
        <section class="password-card" aria-labelledby="password-title">
            <p class="eyebrow">{{ __('auth.account_recovery_a73c0b') }}</p>
            <h1 id="password-title">{{ __('common.check_your_email_fab9f4') }}</h1>
            <p>{{ __('validation.enter_the_six_digit_code_sent_to_9b9657') }} <strong>{{ $email }}</strong>{{ __('common.it_expires_in_10_minutes_4c8de8') }}</p>
            @if (session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
            <form class="register-form" method="POST" action="{{ route('password.otp.verify') }}" data-verify-code-form novalidate>
                @csrf
                <label>
                    <span>{{ __('auth.verification_code_80f2f2') }}</span>
                    <input class="otp-input @error('otp') is-invalid @enderror" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus aria-describedby="verification-code-error">
                    <small id="verification-code-error" data-verification-code-error @if (! $errors->has('otp')) hidden @endif>{{ $errors->first('otp') }}</small>
                </label>
                <button type="submit" data-verify-code-submit>
                    <span class="verify-code-submit__idle">{{ __('auth.verify_code_91a22c') }}</span>
                    <span class="verify-code-submit__loading" aria-hidden="true">
                        <span class="verify-code-submit__spinner"></span>
                        {{ __('auth.verifying_bf6ea5') }}
                    </span>
                </button>
            </form>
            <p class="auth-switch">{{ __('common.didn_t_get_a_code_edf900') }} <a href="{{ route('password.request') }}">{{ __('common.send_another_00e106') }}</a></p>
        </section>
    </main>
    <script>
        (() => {
            const form = document.querySelector('[data-verify-code-form]');
            const button = document.querySelector('[data-verify-code-submit]');
            const code = form?.querySelector('input[name="otp"]');
            const codeError = form?.querySelector('[data-verification-code-error]');

            if (!form || !button || !code || !codeError) return;

            const resetButton = () => {
                button.disabled = false;
                button.classList.remove('is-loading');
                button.removeAttribute('aria-busy');
            };

            form.addEventListener('submit', (event) => {
                code.classList.remove('is-invalid');
                code.removeAttribute('aria-invalid');
                codeError.textContent = '';
                codeError.hidden = true;

                let message = '';
                if (!code.value.trim()) {
                    message = window.t("auth.verification_code_is_required_93daff");
                } else if (!/^\d{6}$/.test(code.value.trim())) {
                    message = window.t("auth.enter_a_valid_six_digit_verification_code_17f165");
                }

                if (message) {
                    event.preventDefault();
                    code.classList.add('is-invalid');
                    code.setAttribute('aria-invalid', 'true');
                    codeError.textContent = message;
                    codeError.hidden = false;
                    code.focus();
                    return;
                }

                button.disabled = true;
                button.classList.add('is-loading');
                button.setAttribute('aria-busy', 'true');
            });

            window.addEventListener('pageshow', resetButton);
        })();
    </script>
</body>
</html>
