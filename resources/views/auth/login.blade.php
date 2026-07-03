<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - StockPilot Inventory</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="login-page auth-ui">
    <main class="inventory-login-shell">
        <section class="login-visual-panel" aria-label="Inventory workspace summary">
            <nav class="login-brand">
                <span class="brand-icon">SP</span>
                <strong>StockPilot</strong>
            </nav>

            <div class="login-hero-copy">
                <p class="eyebrow">Shop inventory SaaS</p>
                <h1>Control stock, catalog status, and reorder work from one desk.</h1>
                <p>Built for store teams that need fast SKU lookup, low-stock visibility, and reliable daily inventory operations.</p>
            </div>

            <div class="login-stat-grid">
                <div>
                    <strong>Live</strong>
                    <span>Stock movement</span>
                </div>
                <div>
                    <strong>Low</strong>
                    <span>Reorder alerts</span>
                </div>
                <div>
                    <strong>SKU</strong>
                    <span>Catalog control</span>
                </div>
            </div>
        </section>

        <section class="login-form-panel" aria-labelledby="login-title">
            <div class="login-card">
                
                        <h1 id="login-title">Log in to inventory</h1>
                        

                <form class="register-form login-form" method="POST" action="{{ route('login.store') }}" data-login-form novalidate>
                    @csrf
                    <label class="login-field">
                        <span>Business name</span>
                        <select name="tenant_id" @class(['is-invalid' => $errors->has('tenant_id')]) required data-login-field="tenant_id">
                            <option value="">Select business</option>
                            @foreach ($businesses as $business)
                                <option value="{{ $business->id }}" @selected((string) old('tenant_id') === (string) $business->id)>
                                    {{ $business->business_name }}
                                </option>
                            @endforeach
                        </select>
                        <small data-login-error="tenant_id" @if (! $errors->has('tenant_id')) hidden @endif>{{ $errors->first('tenant_id') }}</small>
                    </label>

                    <label class="login-field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="staff@shop.com" @class(['is-invalid' => $errors->has('email')]) required data-login-field="email">
                        <small data-login-error="email" @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</small>
                    </label>

                    <label class="login-field">
                        <span>Password</span>
                        <input type="password" name="password" autocomplete="current-password" placeholder="Enter password" @class(['is-invalid' => $errors->has('password')]) required data-login-field="password">
                        <small data-login-error="password" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</small>
                    </label>

                    <label class="check-row">
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep this device signed in</span>
                    </label>

                    <button type="submit">Login</button>
                </form>
                <p class="auth-switch">New business? <a href="{{ route('register') }}">Create an account</a></p>
                <!-- <div class="auth-footnote">
                    <span>Tenant isolated</span>
                    <span>Role based access</span>
                </div> -->
            </div>
        </section>
    </main>

    <script>
        const loginForm = document.querySelector('[data-login-form]');

        function showLoginError(form, name, message) {
            const field = form.querySelector(`[data-login-field="${name}"]`);
            const error = form.querySelector(`[data-login-error="${name}"]`);

            if (field) {
                field.classList.add('is-invalid');
            }

            if (error) {
                error.textContent = message;
                error.hidden = false;
            }
        }

        function clearLoginErrors(form) {
            document.querySelectorAll('[data-login-error]').forEach((error) => {
                error.textContent = '';
                error.hidden = true;
            });

            document.querySelectorAll('[data-login-field]').forEach((field) => {
                field.classList.remove('is-invalid');
            });
        }

        loginForm?.addEventListener('submit', async function (event) {
            event.preventDefault();

            const form = event.currentTarget;
            const button = form.querySelector('button[type="submit"]');
            let firstInvalid = null;

            clearLoginErrors(form);

            form.querySelectorAll('[data-login-field]').forEach((field) => {
                if (field.value.trim() !== '') {
                    return;
                }

                const label = field.closest('label')?.querySelector('span')?.textContent.trim() || 'This field';
                showLoginError(form, field.name, `${label} is required.`);
                firstInvalid = firstInvalid || field;
            });

            if (firstInvalid) {
                firstInvalid.focus();
                return;
            }

            if (button) {
                button.disabled = true;
                button.textContent = 'Opening dashboard...';
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
                    showLoginError(form, name, messages[0] || data.message || 'This field is invalid.');
                });
            } catch (error) {
                HTMLFormElement.prototype.submit.call(form);
                return;
            }

            if (button) {
                button.disabled = false;
                button.textContent = 'Open dashboard';
            }
        });
    </script>
</body>
</html>
