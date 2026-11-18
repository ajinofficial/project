<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.login_zyloah_417ae1') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Zyloah-logo.png') }}">
    @include('partials.app-base-url')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
    @include('partials.localization')
</head>
<body class="login-page auth-ui">
    <main class="inventory-login-shell">
        <section class="login-visual-panel" aria-label="{{ __('products.inventory_workspace_summary_2a3b1d') }}">
            <nav class="login-brand">
                <span class="brand-logo" role="img" aria-label="{{ __('common.zyloah_logo_216929') }}"></span>
                <strong>Zyloah</strong>
                @include('partials.language-selector')
            </nav>

            <div class="login-hero-copy">
                <p class="eyebrow">{{ __('products.zyloah_inventory_platform_731d7c') }}</p>
                <h1>{{ __('common.your_business_8608f4') }}<br><span>{{ __('common.in_sync_48eda4') }}</span></h1>
                <p>{{ __('products.bring_stock_sales_and_everyday_operations_together_one_clear_view_to_k_aa93c7') }}</p>
            </div>

            <div class="login-stat-grid">
                <div>
                    <strong>{{ __('common.live_65c821') }}</strong>
                    <span>{{ __('products.stock_movement_cbaacf') }}</span>
                </div>
                <div>
                    <strong>{{ __('common.low_a12494') }}</strong>
                    <span>{{ __('notifications.reorder_alerts_d99459') }}</span>
                </div>
                <div>
                    <strong>SKU</strong>
                    <span>{{ __('common.catalog_control_1e16d8') }}</span>
                </div>
            </div>
        </section>

        <section class="login-form-panel" aria-labelledby="login-title">
            <div class="login-card">
                <div class="login-welcome">
                    <span class="brand-logo" role="img" aria-label="{{ __('common.zyloah_logo_216929') }}"></span>
                    <p class="eyebrow">{{ __('common.your_zyloah_workspace_779d6b') }}</p>
                    <p class="login-intro">{{ __('auth.log_in_to_manage_your_business_3e7477') }}</p>
                </div>

                <form class="register-form login-form" method="POST" action="{{ route('login.store') }}" data-login-form novalidate>
                    @csrf
                    <label class="login-field">
                        <span>{{ __('common.business_name_a8e390') }}</span>
                        <div class="business-search" data-business-search-wrap>
                            <input type="search" value="{{ old('business_name', $oldBusiness?->business_name) }}" autocomplete="off" placeholder="{{ __('common.search_business_name_804023') }}" @class(['is-invalid' => $errors->has('tenant_id')]) required data-login-field="tenant_id" data-business-search data-business-url="{{ route('login.businesses') }}" aria-autocomplete="list" aria-expanded="false" aria-controls="business-results">
                            <input type="hidden" name="tenant_id" value="{{ old('tenant_id') }}" data-business-id>
                            <div class="business-search-results" id="business-results" data-business-list hidden></div>
                        </div>
                        <small data-login-error="tenant_id" @if (! $errors->has('tenant_id')) hidden @endif>{{ $errors->first('tenant_id') }}</small>
                    </label>

                    <label class="login-field">
                        <span>{{ __('common.email_84add5') }}</span>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="{{ __('common.enter_email_2561af') }}" @class(['is-invalid' => $errors->has('email')]) required data-login-field="email">
                        <small data-login-error="email" @if (! $errors->has('email')) hidden @endif>{{ $errors->first('email') }}</small>
                    </label>

                    <label class="login-field">
                        <span>{{ __('auth.password_8be3c9') }}</span>
                        <div class="password-field">
                            <input type="password" name="password" autocomplete="current-password" placeholder="{{ __('auth.enter_password_570591') }}" @class(['is-invalid' => $errors->has('password')]) required data-login-field="password">
                            <button type="button" class="password-toggle" data-toggle-password aria-label="{{ __('auth.show_password_044b85') }}" aria-pressed="false">
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
                        <span>{{ __('common.keep_me_logged_in_a18b47') }}</span>
                    </label>

                    <a class="forgot-password-link" href="{{ route('password.request') }}">{{ __('auth.forgot_password_4c29f7') }}</a>
                    <button type="submit">{{ __('auth.login_4e5a28') }}</button>
                </form>
                @if (session('status'))
                    <p class="auth-status" role="status">{{ session('status') }}</p>
                @endif
                <!-- <p class="auth-switch">{{ __('common.new_business_24ecf0') }} <a href="{{ route('register') }}">{{ __('common.create_an_account_3f4f54') }}</a></p> -->
                <!-- <div class="auth-footnote">
                    <span>{{ __('settings.tenant_isolated_4cc4c2') }}</span>
                    <span>{{ __('settings.role_based_access_889bfc') }}</span>
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
                button.setAttribute('aria-label', showPassword ? window.t("auth.hide_password_e40123") : window.t("auth.show_password_044b85"));
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

                const label = field.closest('label')?.querySelector('span')?.textContent.trim() || window.t("common.this_field_f89fd4");
                showLoginError(form, field.name || field.dataset.loginField, window.t('validation.required', {attribute: label}));
                firstInvalid = firstInvalid || field;
            });

            if (firstInvalid) {
                firstInvalid.focus();
                return;
            }

            if (button) {
                button.disabled = true;
                button.textContent = window.t("auth.login_964fb1");
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

                if (response.status === 429) {
                    const seconds = response.headers.get('Retry-After') || 60;
                    showLoginError(form, 'password', window.t('auth.too_many_attempts', {seconds: seconds}));
                }

                if (response.ok && data.redirect) {
                    window.location.assign(data.redirect);
                    return;
                }

                Object.entries(data.errors || {}).forEach(([name, messages]) => {
                    showLoginError(form, name, messages[0] || data.message || window.t("validation.this_field_is_invalid_d3c603"));
                });
            } catch (error) {
                HTMLFormElement.prototype.submit.call(form);
                return;
            }

            if (button) {
                button.disabled = false;
                button.textContent = window.t("auth.login_4e5a28");
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
                empty.textContent = query ? window.t("common.no_matching_businesses_found_fb07b2") : window.t("common.type_a_business_name_to_search_60c956");
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
