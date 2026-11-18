<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.forgot_password_zyloah_024b72') }}</title>
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
            <h1 id="password-title">{{ __('auth.forgot_your_password_a2f060') }}</h1>
            <p>{{ __('auth.enter_the_email_address_linked_to_your_account_we_ll_send_a_six_digit__e906ea') }}</p>
            @if (session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
            <form class="register-form" method="POST" action="{{ route('password.email') }}" data-forgot-password-form novalidate>
                @csrf
                <label>
                    <span>{{ __('common.email_84add5') }}</span>
                    <input class="@error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="{{ __('common.enter_email_2561af') }}" required autofocus aria-describedby="forgot-password-email-error">
                    <small id="forgot-password-email-error" data-forgot-password-email-error @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</small>
                </label>
                <button type="submit" data-forgot-password-submit>
                    <span class="forgot-password-submit__idle">{{ __('auth.send_verification_code_cfbbd9') }}</span>
                    <span class="forgot-password-submit__loading" aria-hidden="true">
                        <span class="forgot-password-submit__spinner"></span>
                        {{ __('common.sending_code_1db1fe') }}
                    </span>
                </button>
            </form>
            <p class="auth-switch"><a href="{{ route('login') }}">{{ __('auth.back_to_login_4b6756') }}</a></p>
        </section>
    </main>
    <script>
        (() => {
            const form = document.querySelector('[data-forgot-password-form]');
            const button = document.querySelector('[data-forgot-password-submit]');
            const email = form?.querySelector('input[name="email"]');
            const emailError = form?.querySelector('[data-forgot-password-email-error]');

            if (!form || !button || !email || !emailError) return;

            const resetButton = () => {
                button.disabled = false;
                button.classList.remove('is-loading');
                button.removeAttribute('aria-busy');
            };

            form.addEventListener('submit', (event) => {
                email.classList.remove('is-invalid');
                email.removeAttribute('aria-invalid');
                emailError.textContent = '';
                emailError.hidden = true;

                let message = '';
                if (!email.value.trim()) {
                    message = window.t("auth.email_address_is_required_240bd7");
                } else if (!email.validity.valid) {
                    message = window.t("auth.enter_a_valid_email_address_2e09ed");
                }

                if (message) {
                    event.preventDefault();
                    email.classList.add('is-invalid');
                    email.setAttribute('aria-invalid', 'true');
                    emailError.textContent = message;
                    emailError.hidden = false;
                    email.focus();
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
