<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify code - Investrivo</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="auth-ui password-page">
    <main class="password-shell">
        <a class="password-brand" href="{{ route('login') }}"><span class="brand-logo"></span><strong>Investrivo</strong></a>
        <section class="password-card" aria-labelledby="password-title">
            <p class="eyebrow">Account recovery</p>
            <h1 id="password-title">Check your email</h1>
            <p>Enter the six-digit code sent to <strong>{{ $email }}</strong>. It expires in 10 minutes.</p>
            @if (session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
            <form class="register-form" method="POST" action="{{ route('password.otp.verify') }}" data-verify-code-form novalidate>
                @csrf
                <label>
                    <span>Verification code</span>
                    <input class="otp-input @error('otp') is-invalid @enderror" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus aria-describedby="verification-code-error">
                    <small id="verification-code-error" data-verification-code-error @if (! $errors->has('otp')) hidden @endif>{{ $errors->first('otp') }}</small>
                </label>
                <button type="submit" data-verify-code-submit>
                    <span class="verify-code-submit__idle">Verify code</span>
                    <span class="verify-code-submit__loading" aria-hidden="true">
                        <span class="verify-code-submit__spinner"></span>
                        Verifying...
                    </span>
                </button>
            </form>
            <p class="auth-switch">Didn’t get a code? <a href="{{ route('password.request') }}">Send another</a></p>
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
                    message = 'Verification code is required.';
                } else if (!/^\d{6}$/.test(code.value.trim())) {
                    message = 'Enter a valid six-digit verification code.';
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
