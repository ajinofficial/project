@extends('layouts.admin', ['title' => 'Users'])

@section('content')
    <section class="users-page">
        <header class="users-hero">
            <div>
                <p class="eyebrow">Team accounts</p>
                <h1>Users</h1>
                <p>Create staff logins for this tenant. Account creation is limited by the current plan.</p>
            </div>
            <div class="users-plan-badge">
                <span>{{ ucfirst($tenant->plan?->name ?? 'Plan') }} plan</span>
                <strong>{{ $userCount }} / {{ $userLimit ?? 'Unlimited' }}</strong>
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

        <section class="users-stat-grid">
            <article>
                <span>Active accounts</span>
                <strong>{{ number_format($userCount) }}</strong>
            </article>
            <article>
                <span>Plan limit</span>
                <strong>{{ $userLimit ?? 'Unlimited' }}</strong>
            </article>
            <article>
                <span>Available seats</span>
                <strong>{{ $remainingUsers ?? 'Unlimited' }}</strong>
            </article>
        </section>

        <section class="users-layout">
            <article class="users-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Create account</p>
                        <h2>New user</h2>
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
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name') }}" @class(['is-invalid' => $errors->has('name')]) required @disabled(! $canCreateUser)>
                        <small data-user-form-error="name">@error('name') {{ $message }} @enderror</small>
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" @class(['is-invalid' => $errors->has('email')]) required @disabled(! $canCreateUser)>
                        <small data-user-form-error="email">@error('email') {{ $message }} @enderror</small>
                    </label>
                    <div class="field-grid">
                        <label>
                            <span>Country code</span>
                            <select name="country_code" @class(['is-invalid' => $errors->has('country_code')]) required @disabled(! $canCreateUser)>
                                @foreach ($countryCodes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('country_code', '+91') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small data-user-form-error="country_code">@error('country_code') {{ $message }} @enderror</small>
                        </label>
                        <label>
                            <span>Phone</span>
                            <input type="tel" name="phone" value="{{ old('phone') }}" inputmode="numeric" maxlength="15" pattern="[0-9]{6,15}" placeholder="9876543210" @class(['is-invalid' => $errors->has('phone')]) @disabled(! $canCreateUser)>
                            <small data-user-form-error="phone">@error('phone') {{ $message }} @enderror</small>
                        </label>
                        <label>
                            <span>Role</span>
                            <select name="role" @class(['is-invalid' => $errors->has('role')]) required @disabled(! $canCreateUser)>
                                @foreach ($roles as $roleId => $roleLabel)
                                    <option value="{{ $roleId }}" @selected((int) old('role', \App\Models\User::ROLE_MANAGER) === (int) $roleId)>{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                            <small data-user-form-error="role">@error('role') {{ $message }} @enderror</small>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>Password</span>
                            <span class="users-password-control">
                                <input type="password" name="password" @class(['is-invalid' => $errors->has('password')]) required @disabled(! $canCreateUser)>
                                <button type="button" data-password-toggle aria-label="Show password" aria-pressed="false" @disabled(! $canCreateUser)>
                                    <svg class="users-password-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="users-password-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 6.2A11.8 11.8 0 0 1 12 6c6.5 0 10 6 10 6a16.7 16.7 0 0 1-3 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6c1.5 0 2.8-.3 4-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                                </button>
                            </span>
                            <small data-user-form-error="password">@error('password') {{ $message }} @enderror</small>
                        </label>
                        <label>
                            <span>Confirm password</span>
                            <span class="users-password-control">
                                <input type="password" name="password_confirmation" @class(['is-invalid' => $errors->has('password_confirmation')]) required @disabled(! $canCreateUser)>
                                <button type="button" data-password-toggle aria-label="Show password" aria-pressed="false" @disabled(! $canCreateUser)>
                                    <svg class="users-password-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="users-password-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 6.2A11.8 11.8 0 0 1 12 6c6.5 0 10 6 10 6a16.7 16.7 0 0 1-3 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6c1.5 0 2.8-.3 4-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                                </button>
                            </span>
                            <small data-user-form-error="password_confirmation">@error('password_confirmation') {{ $message }} @enderror</small>
                        </label>
                    </div>
                    <button class="product-save-button" type="submit" data-user-create-button @disabled(! $canCreateUser)>
                        <span class="product-save-button__idle">Create user</span>
                        <span class="product-save-button__loading" aria-hidden="true">
                            <i></i>
                            Saving
                        </span>
                    </button>
                    @unless ($canCreateUser)
                        <p class="users-limit-note">This plan has reached its user limit.</p>
                    @endunless
                </form>
            </article>

            <article class="users-card users-listing-card" data-users-listing>
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Tenant users</p>
                        <h2>Accounts</h2>
                    </div>
                </div>

                <div class="product-toolbar">
                    <form class="product-filter-form users-filter-form" method="GET" action="{{ route('users.index') }}" data-users-filter-form>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone" data-users-filter>
                        <select name="role" data-users-filter>
                            <option value="">All roles</option>
                            @foreach ($filterRoles as $roleId => $roleLabel)
                                <option value="{{ $roleId }}" @selected((string) request('role') === (string) $roleId)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" aria-label="Users per page" data-users-filter>
                            @foreach ($perPageOptions as $option)
                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                            @endforeach
                        </select>
                        <a class="product-clear-filter" href="{{ route('users.index') }}" data-users-listing-link>Clear</a>
                    </form>
                </div>

                <div class="users-listing-results">
                    <div class="product-listing-loader" data-users-listing-loader aria-live="polite" aria-hidden="true">
                        <span aria-hidden="true"></span>
                        <strong>Loading users</strong>
                    </div>

                    <div class="table-wrap">
                        <table class="admin-table users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Created</th>
                                <th>Action</th>
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
                                    <td>{{ $teamUser->role_label }}</td>
                                    <td>
                                        @if ($teamUser->phone)
                                            {{ str_starts_with($teamUser->phone, '+') ? $teamUser->phone : ($teamUser->country_code ?: '+91').' '.$teamUser->phone }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $teamUser->created_at?->format('d M Y') }}</td>
                                    <td>
                                        @if ((int) $teamUser->role !== \App\Models\User::ROLE_OWNER)
                                            <form method="POST" action="{{ route('users.delete', $teamUser) }}" data-confirm data-confirm-title="Delete user" data-confirm-message="Delete {{ $teamUser->name }} from this tenant?" data-confirm-button="Delete">
                                                @csrf
                                                <button class="danger-button" type="submit">Delete</button>
                                            </form>
                                        @else
                                            <span class="users-owner-lock">Owner</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">{{ $hasActiveFilters ? 'No users match the current filters.' : 'No users yet.' }}</td>
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
                        button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                        button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
                    });
                });

                document.querySelectorAll('[data-users-filter-form]').forEach(function (form) {
                    var listing = form.closest('[data-users-listing]');
                    var loader = listing ? listing.querySelector('[data-users-listing-loader]') : null;

                    function showUsersListingLoader() {
                        if (!listing || !loader) {
                            return;
                        }

                        listing.classList.add('is-loading');
                        listing.setAttribute('aria-busy', 'true');
                        loader.setAttribute('aria-hidden', 'false');
                    }

                    function submitFilters() {
                        showUsersListingLoader();

                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                            return;
                        }

                        form.submit();
                    }

                    form.querySelectorAll('[data-users-filter]').forEach(function (field) {
                        field.addEventListener('change', submitFilters);
                        field.addEventListener('search', submitFilters);
                    });

                    form.addEventListener('submit', function () {
                        showUsersListingLoader();

                        form.querySelectorAll('input, select').forEach(function (field) {
                            if (field.value === '') {
                                field.disabled = true;
                            }
                        });
                    });

                    if (listing) {
                        listing.querySelectorAll('[data-users-listing-link], .product-pagination a').forEach(function (link) {
                            link.addEventListener('click', function (event) {
                                if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                                    return;
                                }

                                showUsersListingLoader();
                            });
                        });
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
                            summary.textContent = 'Unable to create the user. Please try again.';
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
