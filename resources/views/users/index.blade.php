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

                <form class="product-form" method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name') }}" required @disabled(! $canCreateUser)>
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" required @disabled(! $canCreateUser)>
                    </label>
                    <div class="field-grid">
                        <label>
                            <span>Phone</span>
                            <input type="text" name="phone" value="{{ old('phone') }}" @disabled(! $canCreateUser)>
                        </label>
                        <label>
                            <span>Role</span>
                            <select name="role" required @disabled(! $canCreateUser)>
                                @foreach ($roles as $roleId => $roleLabel)
                                    <option value="{{ $roleId }}" @selected((int) old('role', \App\Models\User::ROLE_MANAGER) === (int) $roleId)>{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="field-grid">
                        <label>
                            <span>Password</span>
                            <input type="password" name="password" required @disabled(! $canCreateUser)>
                        </label>
                        <label>
                            <span>Confirm password</span>
                            <input type="password" name="password_confirmation" required @disabled(! $canCreateUser)>
                        </label>
                    </div>
                    <button type="submit" @disabled(! $canCreateUser)>Create user</button>
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
                                    <td>{{ $teamUser->phone ?: '-' }}</td>
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
                                    <td colspan="5">No users yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $users->links() }}
            </article>
        </section>
    </section>
@endsection
