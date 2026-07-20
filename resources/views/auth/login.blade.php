<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Investrivo</title>
    @include('partials.app-base-url')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="login-page auth-ui">
    <main class="inventory-login-shell">
        <section class="login-visual-panel" aria-label="Inventory workspace summary">
            <nav class="login-brand">
                <span class="brand-logo" role="img" aria-label="InvestRivo logo"></span>
                <strong>Investrivo</strong>
            </nav>

            <div class="login-hero-copy">
                <p class="eyebrow">Investrivo inventory platform</p>
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
                        <div class="business-search" data-business-search-wrap>
                            <input type="search" value="{{ old('business_name', $oldBusiness?->business_name) }}" autocomplete="off" placeholder="Search business name" @class(['is-invalid' => $errors->has('tenant_id')]) required data-login-field="tenant_id" data-business-search data-business-url="{{ route('login.businesses') }}" aria-autocomplete="list" aria-expanded="false" aria-controls="business-results">
                            <input type="hidden" name="tenant_id" value="{{ old('tenant_id') }}" data-business-id>
                            <div class="business-search-results" id="business-results" data-business-list hidden></div>
                        </div>
                        <small data-login-error="tenant_id" @if (! $errors->has('tenant_id')) hidden @endif>{{ $errors->first('tenant_id') }}</small>
                    </label>

                    <label class="login-field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="staff@shop.com" @class(['is-invalid' => $errors->has('email')]) required data-login-field="email">
                        <small data-login-error="email" @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</small>
                    </label>

                    <label class="login-field">
                        <span>Password</span>
                        <div class="password-field">
                            <input type="password" name="password" autocomplete="current-password" placeholder="Enter password" @class(['is-invalid' => $errors->has('password')]) required data-login-field="password">
                            <button type="button" class="password-toggle" data-toggle-password aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <small data-login-error="password" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</small>
                    </label>

                    <label class="check-row">
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep this device signed in</span>
                    </label>

                    <a class="forgot-password-link" href="{{ route('password.request') }}">Forgot password?</a>
                    <button type="submit">Login</button>
                </form>
                @if (session('status'))
                    <p class="auth-status" role="status">{{ session('status') }}</p>
                @endif
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
        const businessSearch = document.querySelector('[data-business-search]');
        const businessId = document.querySelector('[data-business-id]');
        const businessList = document.querySelector('[data-business-list]');
        let businessSearchTimer;
        let businessSearchController;

        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.closest('.password-field')?.querySelector('input');

                if (! input) {
                    return;
                }

                const showPassword = input.type === 'password';
                input.type = showPassword ? 'text' : 'password';
                button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
                button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
            });
        });

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
                const value = field.matches('[data-business-search]')
                    ? businessId?.value.trim()
                    : field.value.trim();

                if (value !== '') {
                    return;
                }

                const label = field.closest('label')?.querySelector('span')?.textContent.trim() || 'This field';
                showLoginError(form, field.name || field.dataset.loginField, `${label} is required.`);
                firstInvalid = firstInvalid || field;
            });

            if (firstInvalid) {
                firstInvalid.focus();
                return;
            }

            if (button) {
                button.disabled = true;
                button.textContent = 'Login...';
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
                button.textContent = 'Login';
            }
        });

        function setBusinessList(open) {
            if (! businessList || ! businessSearch) {
                return;
            }

            businessList.hidden = ! open;
            businessSearch.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function renderBusinessResults(items, query) {
            if (! businessList) {
                return;
            }

            businessList.innerHTML = '';

            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'business-search-empty';
                empty.textContent = query ? 'No matching businesses found.' : 'Type a business name to search.';
                businessList.append(empty);
                setBusinessList(true);
                return;
            }

            items.forEach((business) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'business-search-option';
                option.dataset.businessOption = business.id;
                option.textContent = business.business_name;
                option.addEventListener('click', () => {
                    businessSearch.value = business.business_name;
                    businessId.value = business.id;
                    businessSearch.classList.remove('is-invalid');
                    setBusinessList(false);
                });
                businessList.append(option);
            });

            setBusinessList(true);
        }

        businessSearch?.addEventListener('input', () => {
            const query = businessSearch.value.trim();

            if (businessId) {
                businessId.value = '';
            }

            window.clearTimeout(businessSearchTimer);

            if (query.length < 2) {
                businessSearchController?.abort();
                renderBusinessResults([], query);
                return;
            }

            businessSearchTimer = window.setTimeout(async () => {
                businessSearchController?.abort();
                businessSearchController = new AbortController();

                try {
                    const url = new URL(businessSearch.dataset.businessUrl, window.location.origin);
                    url.searchParams.set('q', query);

                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: businessSearchController.signal,
                    });

                    const data = await response.json();
                    renderBusinessResults(data.data || [], query);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        renderBusinessResults([], query);
                    }
                }
            }, 250);
        });

        businessSearch?.addEventListener('focus', () => {
            if (businessList?.children.length) {
                setBusinessList(true);
            }
        });

        document.addEventListener('click', (event) => {
            if (! event.target.closest('[data-business-search-wrap]')) {
                setBusinessList(false);
            }
        });
    </script>
</body>
</html>
