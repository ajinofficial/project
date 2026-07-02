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

                <form class="product-form" method="POST" action="{{ route('users.store') }}" data-product-save-form>
                    @csrf
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name') }}" @class(['is-invalid' => $errors->has('name')]) required @disabled(! $canCreateUser)>
                        @error('name') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" @class(['is-invalid' => $errors->has('email')]) required @disabled(! $canCreateUser)>
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>
                    <div class="field-grid">
                        <label>
                            <span>Country code</span>
                            <select name="country_code" @class(['is-invalid' => $errors->has('country_code')]) required @disabled(! $canCreateUser)>
                                @foreach ($countryCodes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('country_code', '+91') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('country_code') <small>{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>Phone</span>
                            <input type="tel" name="phone" value="{{ old('phone') }}" inputmode="numeric" maxlength="15" pattern="[0-9]{6,15}" placeholder="9876543210" @class(['is-invalid' => $errors->has('phone')]) @disabled(! $canCreateUser)>
                            @error('phone') <small>{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>Role</span>
                            <select name="role" @class(['is-invalid' => $errors->has('role')]) required @disabled(! $canCreateUser)>
                                @foreach ($roles as $roleId => $roleLabel)
                                    <option value="{{ $roleId }}" @selected((int) old('role', \App\Models\User::ROLE_MANAGER) === (int) $roleId)>{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                            @error('role') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>Password</span>
                            <input type="password" name="password" @class(['is-invalid' => $errors->has('password')]) required @disabled(! $canCreateUser)>
                            @error('password') <small>{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>Confirm password</span>
                            <input type="password" name="password_confirmation" @class(['is-invalid' => $errors->has('password_confirmation')]) required @disabled(! $canCreateUser)>
                            @error('password_confirmation') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <button class="product-save-button" type="submit" data-product-save-button @disabled(! $canCreateUser)>
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

            <article class="users-card">
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
                        <a class="product-clear-filter" href="{{ route('users.index') }}">Clear</a>
                    </form>
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
            </article>
        </section>
    </section>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-users-filter-form]').forEach(function (form) {
                    function submitFilters() {
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
                        form.querySelectorAll('input, select').forEach(function (field) {
                            if (field.value === '') {
                                field.disabled = true;
                            }
                        });
                    });
                });
            });
        </script>
    @endonce

    @include('products.partials.save-loader-script')
@endsection
