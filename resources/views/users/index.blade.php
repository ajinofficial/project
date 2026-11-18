@extends('layouts.admin', ['title' => __('common.users_57f2b1')])

@section('content')
    <section class="users-page product-overview">
        <header class="users-hero product-page-head">
            <div>
                <p class="eyebrow">{{ __('common.team_accounts_93a719') }}</p>
                <h1>{{ __('common.users_57f2b1') }}</h1>
                <p>{{ __('auth.create_staff_logins_for_this_tenant_account_creation_is_limited_by_the_b78870') }}</p>
            </div>
            <div class="users-plan-badge">
                <span>{{ __('common.plan_label', ['name' => __(ucfirst($tenant->plan?->name ?? __('settings.plan_ae2f98')))]) }}</span>
                <strong>{{ $userCount }} / {{ $userLimit ?? __('common.unlimited_b8bef3') }}</strong>
            </div>
        </header>

        @if ($errors->has('limit'))
            <div class="error-summary" role="alert">
                <span>{{ $errors->first('limit') }}</span>
            </div>
        @endif

        @if ($errors->has('delete'))
            <div class="error-summary" role="alert">
                <span>{{ $errors->first('delete') }}</span>
            </div>
        @endif

        <section class="users-stat-grid product-stat-grid">
            <article class="product-stat">
                <span>{{ __('common.active_accounts_5da705') }}</span>
                <strong>{{ number_format($userCount) }}</strong>
            </article>
            <article class="product-stat">
                <span>{{ __('settings.plan_limit_26b49d') }}</span>
                <strong>{{ $userLimit ?? __('common.unlimited_b8bef3') }}</strong>
            </article>
            <article class="product-stat">
                <span>{{ __('common.available_seats_abf4fd') }}</span>
                <strong>{{ $remainingUsers ?? __('common.unlimited_b8bef3') }}</strong>
            </article>
        </section>

        <section class="users-layout">
            <article class="users-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('common.create_account_aaf374') }}</p>
                        <h2>{{ __('common.new_user_a4ce18') }}</h2>
                    </div>
                </div>

                @if ($errors->any() && ! $errors->has('limit'))
                    <div class="error-summary" role="alert">
                        @foreach ($errors->all() as $error)
                            <span>{{ $error }}</span>
                        @endforeach
                    </div>
                @endif

                <form class="product-form" method="POST" action="{{ route('users.store') }}" data-user-create-form novalidate>
                    @csrf
                    <div class="error-summary" role="alert" data-user-form-summary hidden></div>
                    <label>
                        <span>{{ __('common.name_709a23') }}</span>
                        <input type="text" name="name" value="{{ old('name') }}" @class(['is-invalid' => $errors->has('name')]) required @disabled(! $canCreateUser)>
                        <small data-user-form-error="name">@error('name') {{ $message }} @enderror</small>
                    </label>
                    <label>
                        <span>{{ __('common.email_84add5') }}</span>
                        <input type="email" name="email" value="{{ old('email') }}" @class(['is-invalid' => $errors->has('email')]) required @disabled(! $canCreateUser)>
                        <small data-user-form-error="email">@error('email') {{ $message }} @enderror</small>
                    </label>
                    <div class="field-grid">
                        <label>
                            <span>{{ __('common.country_code_2eb6c4') }}</span>
                            <select name="country_code" @class(['is-invalid' => $errors->has('country_code')]) required @disabled(! $canCreateUser)>
                                @foreach ($countryCodes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('country_code', '+91') === $code)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <small data-user-form-error="country_code">@error('country_code') {{ $message }} @enderror</small>
                        </label>
                        <label>
                            <span>{{ __('common.phone_77064d') }}</span>
                            <input type="tel" name="phone" value="{{ old('phone') }}" inputmode="numeric" maxlength="15" pattern="[0-9]{6,15}" placeholder="9876543210" @class(['is-invalid' => $errors->has('phone')]) @disabled(! $canCreateUser)>
                            <small data-user-form-error="phone">@error('phone') {{ $message }} @enderror</small>
                        </label>
                        <label>
                            <span>{{ __('settings.role_c3f104') }}</span>
                            <select name="role" @class(['is-invalid' => $errors->has('role')]) required @disabled(! $canCreateUser)>
                                @foreach ($roles as $roleId => $roleLabel)
                                    <option value="{{ $roleId }}" @selected((int) old('role', \App\Models\User::ROLE_MANAGER) === (int) $roleId)>{{ __($roleLabel) }}</option>
                                @endforeach
                            </select>
                            <small data-user-form-error="role">@error('role') {{ $message }} @enderror</small>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>{{ __('auth.password_8be3c9') }}</span>
                            <span class="users-password-control">
                                <input type="password" name="password" @class(['is-invalid' => $errors->has('password')]) required @disabled(! $canCreateUser)>
                                <button type="button" data-password-toggle aria-label="{{ __('auth.show_password_044b85') }}" aria-pressed="false" @disabled(! $canCreateUser)>
                                    <svg class="users-password-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="users-password-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 6.2A11.8 11.8 0 0 1 12 6c6.5 0 10 6 10 6a16.7 16.7 0 0 1-3 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6c1.5 0 2.8-.3 4-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                                </button>
                            </span>
                            <small data-user-form-error="password">@error('password') {{ $message }} @enderror</small>
                        </label>
                        <label>
                            <span>{{ __('auth.confirm_password_4a7c56') }}</span>
                            <span class="users-password-control">
                                <input type="password" name="password_confirmation" @class(['is-invalid' => $errors->has('password_confirmation')]) required @disabled(! $canCreateUser)>
                                <button type="button" data-password-toggle aria-label="{{ __('auth.show_password_044b85') }}" aria-pressed="false" @disabled(! $canCreateUser)>
                                    <svg class="users-password-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="users-password-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 6.2A11.8 11.8 0 0 1 12 6c6.5 0 10 6 10 6a16.7 16.7 0 0 1-3 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6c1.5 0 2.8-.3 4-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                                </button>
                            </span>
                            <small data-user-form-error="password_confirmation">@error('password_confirmation') {{ $message }} @enderror</small>
                        </label>
                    </div>
                    <button class="product-save-button" type="submit" data-user-create-button @disabled(! $canCreateUser)>
                        <span class="product-save-button__idle">{{ __('common.create_user_bdbcfa') }}</span>
                        <span class="product-save-button__loading" aria-hidden="true">
                            <i></i>
                            {{ __('common.saving_369c53') }}
                        </span>
                    </button>
                    @unless ($canCreateUser)
                        <p class="users-limit-note">{{ __('settings.this_plan_has_reached_its_user_limit_260d18') }}</p>
                    @endunless
                </form>
            </article>

            <article class="users-card users-listing-card" data-users-listing>
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('settings.tenant_users_cb800b') }}</p>
                        <h2>{{ __('common.accounts_36bae3') }}</h2>
                    </div>
                </div>

                <div class="product-toolbar">
                    <form class="product-filter-form users-filter-form" method="GET" action="{{ route('users.index') }}" data-users-filter-form>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('common.search_name_email_phone_482b01') }}" data-users-filter>
                        <select name="role" data-users-filter>
                            <option value="">{{ __('settings.all_roles_0caca0') }}</option>
                            @foreach ($filterRoles as $roleId => $roleLabel)
                                <option value="{{ $roleId }}" @selected((string) request('role') === (string) $roleId)>{{ __($roleLabel) }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" aria-label="{{ __('common.users_per_page_4d4a1f') }}" data-users-filter>
                            @foreach ($perPageOptions as $option)
                                <option value="{{ $option }}" @selected($perPage === $option)>{{ __('common.per_page', ['count' => $option]) }}</option>
                            @endforeach
                        </select>
                        <a class="product-clear-filter" href="{{ route('users.index') }}" data-users-listing-link>{{ __('common.clear_719ea3') }}</a>
                    </form>
                </div>

                <div class="users-listing-results" data-users-listing-results>
                    <div class="product-listing-loader" data-users-listing-loader aria-live="polite" aria-hidden="true">
                        <span aria-hidden="true"></span>
                        <strong>{{ __('common.loading_users_352046') }}</strong>
                    </div>

                    <div class="table-wrap">
                        <table class="admin-table users-table">
                        <thead>
                            <tr>
                                <th>{{ __('common.user_9f8a23') }}</th>
                                <th>{{ __('settings.role_c3f104') }}</th>
                                <th>{{ __('common.phone_77064d') }}</th>
                                <th>{{ __('common.created_accf40') }}</th>
                                <th>{{ __('common.action_97c89a') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $teamUser)
                                <tr>
                                    <td>
                                        <div class="users-person">
                                            <span>{{ strtoupper(substr($teamUser->name, 0, 1)) }}</span>
                                            <div>
                                                <strong>{{ $teamUser->name }}</strong>
                                                <small>{{ $teamUser->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ __($teamUser->role_label) }}</td>
                                    <td>
                                        @if ($teamUser->phone)
                                            {{ str_starts_with($teamUser->phone, '+') ? $teamUser->phone : ($teamUser->country_code ?: '+91').' '.$teamUser->phone }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $teamUser->created_at?->translatedFormat('d M Y') }}</td>
                                    <td>
                                        @if ((int) $teamUser->role !== \App\Models\User::ROLE_OWNER)
                                            <form method="POST" action="{{ route('users.delete', $teamUser) }}" data-confirm data-confirm-title="{{ __('common.delete_user_9afe6c') }}" data-confirm-message="Delete {{ $teamUser->name }} from this tenant?" data-confirm-button="{{ __('common.delete_f6fdbe') }}">
                                                @csrf
                                                <button class="danger-button" type="submit">{{ __('common.delete_f6fdbe') }}</button>
                                            </form>
                                        @else
                                            <span class="users-owner-lock">{{ __('common.owner_89ff31') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">{{ $hasActiveFilters ? __('common.no_users_match_the_current_filters_df522d') : __('common.no_users_yet_26930b') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        </table>
                    </div>
                    @include('products.partials.pagination', ['paginator' => $users, 'itemLabel' => 'users'])
                </div>
            </article>
        </section>
    </section>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var input = button.closest('.users-password-control').querySelector('input');
                        var isVisible = input.type === 'text';

                        input.type = isVisible ? 'password' : 'text';
                        button.setAttribute('aria-label', isVisible ? window.t('Show password') : window.t("auth.hide_password_e40123"));
                        button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
                    });
                });

                var usersListingRequest = null;
                var usersSearchTimer = null;

                function usersUrl(form) {
                    var params = new URLSearchParams(new FormData(form));
                    Array.from(params.keys()).forEach(function (key) {
                        if (params.get(key) === '') params.delete(key);
                    });
                    return form.action + (params.toString() ? '?' + params.toString() : '');
                }

                function loadUsersListing(url, pushState) {
                    var listing = document.querySelector('[data-users-listing]');
                    if (!listing) { window.location.href = url; return; }
                    if (usersListingRequest) usersListingRequest.abort();

                    var controller = new AbortController();
                    usersListingRequest = controller;
                    listing.classList.add('is-loading');
                    listing.setAttribute('aria-busy', 'true');
                    listing.querySelector('[data-users-listing-loader]')?.setAttribute('aria-hidden', 'false');

                    fetch(url, {
                        headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: controller.signal
                    }).then(function (response) {
                        if (!response.ok) throw new Error(window.t("validation.unable_to_load_users_a471eb"));
                        return response.text();
                    }).then(function (html) {
                        var page = new DOMParser().parseFromString(html, 'text/html');
                        var next = page.querySelector('[data-users-listing]');
                        if (!next) throw new Error(window.t("validation.user_listing_was_not_found_e456be"));
                        listing.replaceWith(next);
                        document.title = page.title || document.title;
                        if (pushState !== false) window.history.pushState({ usersListing: true }, '', url);
                    }).catch(function (error) {
                        if (error.name !== 'AbortError') window.location.href = url;
                    }).finally(function () {
                        if (usersListingRequest === controller) usersListingRequest = null;
                    });
                }

                document.addEventListener('submit', function (event) {
                    var form = event.target.closest('[data-users-filter-form]');
                    if (form) { event.preventDefault(); loadUsersListing(usersUrl(form), true); }
                });

                document.addEventListener('change', function (event) {
                    var select = event.target.closest('[data-users-filter-form] select[data-users-filter]');
                    if (select) loadUsersListing(usersUrl(select.form), true);
                });

                document.addEventListener('input', function (event) {
                    var search = event.target.closest('[data-users-filter-form] input[type="search"]');
                    if (!search) return;
                    clearTimeout(usersSearchTimer);
                    usersSearchTimer = setTimeout(function () { loadUsersListing(usersUrl(search.form), true); }, 350);
                });

                document.addEventListener('click', function (event) {
                    var link = event.target.closest('[data-users-listing-link], [data-users-listing] .product-pagination a');
                    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                    event.preventDefault();
                    loadUsersListing(link.href, true);
                });

                window.addEventListener('popstate', function () {
                    if (window.location.pathname === new URL('{{ route('users.index') }}').pathname) {
                        loadUsersListing(window.location.href, false);
                    }
                });

                document.querySelectorAll('[data-user-create-form]').forEach(function (form) {
                    var summary = form.querySelector('[data-user-form-summary]');
                    var button = form.querySelector('[data-user-create-button]');

                    function clearErrors() {
                        form.querySelectorAll('[data-user-form-error]').forEach(function (error) {
                            error.textContent = '';
                        });

                        form.querySelectorAll('.is-invalid').forEach(function (field) {
                            field.classList.remove('is-invalid');
                            field.removeAttribute('aria-invalid');
                        });

                        summary.hidden = true;
                        summary.textContent = '';
                    }

                    function showErrors(errors, message) {
                        var hasFieldError = false;

                        Object.keys(errors || {}).forEach(function (fieldName) {
                            var field = form.elements.namedItem(fieldName);
                            var error = form.querySelector('[data-user-form-error="' + fieldName + '"]');
                            var fieldMessage = errors[fieldName][0];

                            if (field && error) {
                                field.classList.add('is-invalid');
                                field.setAttribute('aria-invalid', 'true');
                                error.textContent = fieldMessage;
                                hasFieldError = true;
                            }
                        });

                        if (!hasFieldError && message) {
                            summary.textContent = message;
                            summary.hidden = false;
                        }
                    }

                    form.addEventListener('submit', async function (event) {
                        event.preventDefault();
                        clearErrors();

                        button.disabled = true;
                        button.classList.add('is-loading');
                        button.setAttribute('aria-busy', 'true');

                        try {
                            var requestUrl = new URL(form.getAttribute('action'), window.App.baseUrl);
                            var response = await fetch(requestUrl, {
                                method: form.method,
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: new FormData(form)
                            });
                            var payload = await response.json();

                            if (!response.ok) {
                                showErrors(payload.errors, payload.message);
                                return;
                            }

                            window.location.assign(payload.redirect || '{{ route('users.index') }}');
                        } catch (error) {
                            summary.textContent = window.t("validation.unable_to_create_the_user_please_try_again_7853a5");
                            summary.hidden = false;
                        } finally {
                            button.disabled = false;
                            button.classList.remove('is-loading');
                            button.removeAttribute('aria-busy');
                        }
                    });
                });
            });
        </script>
    @endonce

    @include('products.partials.save-loader-script')
@endsection
