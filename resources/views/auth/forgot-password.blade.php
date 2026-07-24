<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password - Investrivo</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="auth-ui password-page">
    <main class="password-shell">
        <a class="password-brand" href="{{ route('login') }}"><span class="brand-logo"></span><strong>Investrivo</strong></a>
        <section class="password-card" aria-labelledby="password-title">
            <p class="eyebrow">Account recovery</p>
            <h1 id="password-title">Forgot your password?</h1>
            <p>Enter the email address linked to your account. We’ll send a six-digit verification code.</p>
            @if (session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
            <form class="register-form" method="POST" action="{{ route('password.email') }}" data-forgot-password-form novalidate>
                @csrf
                <label>
                    <span>Email address</span>
                    <input class="@error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="staff@shop.com" required autofocus aria-describedby="forgot-password-email-error">
                    <small id="forgot-password-email-error" data-forgot-password-email-error @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</small>
                </label>
                <button type="submit" data-forgot-password-submit>
                    <span class="forgot-password-submit__idle">Send verification code</span>
                    <span class="forgot-password-submit__loading" aria-hidden="true">
                        <span class="forgot-password-submit__spinner"></span>
                        Sending code...
                    </span>
                </button>
            </form>
            <p class="auth-switch"><a href="{{ route('login') }}">Back to login</a></p>
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
                    message = 'Email address is required.';
                } else if (!email.validity.valid) {
                    message = 'Enter a valid email address.';
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
