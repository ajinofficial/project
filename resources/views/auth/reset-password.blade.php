<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a new password - Investrivo</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
</head>
<body class="auth-ui password-page">
    <main class="password-shell">
        <a class="password-brand" href="{{ route('login') }}"><span class="brand-logo"></span><strong>Investrivo</strong></a>
        <section class="password-card" aria-labelledby="password-title">
            <p class="eyebrow">Verified</p>
            <h1 id="password-title">Choose a new password</h1>
            <p>Use at least eight characters and keep it private.</p>
            <form class="register-form" method="POST" action="{{ route('password.update') }}" data-reset-password-form novalidate>
                @csrf
                <label>
                    <span>New password</span>
                    <div class="password-field">
                        <input class="@error('password') is-invalid @enderror" type="password" name="password" autocomplete="new-password" minlength="8" required autofocus data-reset-field="password">
                        <button type="button" class="password-toggle" data-toggle-password aria-label="Show new password" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    <small data-reset-error="password" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</small>
                </label>
                <label>
                    <span>Confirm new password</span>
                    <div class="password-field">
                        <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required data-reset-field="password_confirmation">
                        <button type="button" class="password-toggle" data-toggle-password aria-label="Show password confirmation" aria-pressed="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    <small data-reset-error="password_confirmation" hidden></small>
                </label>
                <button type="submit" data-reset-password-submit>
                    <span class="reset-password-submit__idle">Reset password</span>
                    <span class="reset-password-submit__loading" aria-hidden="true">
                        <span class="reset-password-submit__spinner"></span>
                        Resetting...
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
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
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
                showError('password', 'New password is required.');
            } else if (password.value.length < 8) {
                showError('password', 'New password must be at least 8 characters.');
            } else if (!/[A-Za-z]/.test(password.value) || !/\d/.test(password.value)) {
                showError('password', 'New password must include at least one letter and one number.');
            }

            if (!confirmation.value) {
                showError('password_confirmation', 'Password confirmation is required.');
            } else if (password.value !== confirmation.value) {
                showError('password_confirmation', 'The password confirmation does not match.');
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
