@extends('layouts.admin', ['title' => 'Role Permissions'])

@section('content')
    <section class="permission-page">
        <header class="permission-hero">
            <div>
                <p class="eyebrow">Access control</p>
                <h1>Role permissions</h1>
                <p>Open a role, check the menus it can access, then save. Owner always keeps full access.</p>
            </div>
            <a class="ghost-button" href="{{ route('setup.index') }}">Back to setup</a>
        </header>

        <form method="POST" action="{{ route('role-permissions.update') }}" class="permission-form" data-permission-form>
            @csrf
            @method('PUT')

            <section class="permission-card">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Role list</p>
                        <h2>Manage menu access</h2>
                    </div>
                </div>

                <div class="permission-role-list">
                    @foreach ($roles as $roleId => $roleLabel)
                        @php
                            $roleKey = \App\Support\RolePermission::roleKey((int) $roleId);
                            $isOwner = (int) $roleId === \App\Models\User::ROLE_OWNER;
                            $selectedMenus = $isOwner ? array_keys($menus) : ($permissions[$roleKey] ?? []);
                        @endphp

                        <article class="permission-role-card">
                            <div class="permission-role-avatar">{{ strtoupper(substr($roleLabel, 0, 2)) }}</div>
                            <div>
                                <strong>{{ $roleLabel }}</strong>
                                <span>{{ $isOwner ? 'Full access' : count($selectedMenus).' menu permissions' }}</span>
                            </div>
                            <button type="button" class="permission-edit-button" data-open-permission="{{ $roleKey }}" aria-label="Edit {{ $roleLabel }} permissions">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                                </svg>
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="permission-drawer-backdrop" data-permission-backdrop></div>

            @foreach ($roles as $roleId => $roleLabel)
                @php
                    $roleKey = \App\Support\RolePermission::roleKey((int) $roleId);
                    $isOwner = (int) $roleId === \App\Models\User::ROLE_OWNER;
                @endphp

                <aside class="permission-drawer" data-permission-drawer="{{ $roleKey }}" aria-hidden="true">
                    <div class="permission-drawer-head">
                        <div>
                            <p class="eyebrow">Menu access</p>
                            <h2>{{ $roleLabel }}</h2>
                            <span>{{ $isOwner ? 'Owner access cannot be reduced.' : 'Choose sidebar menus for this role.' }}</span>
                        </div>
                        <button type="button" data-close-permission aria-label="Close permissions panel">x</button>
                    </div>

                    <div class="permission-drawer-list">
                        @foreach ($menus as $menuKey => $menu)
                            <label class="permission-drawer-check">
                                <input
                                    type="checkbox"
                                    name="permissions[{{ $roleKey }}][]"
                                    value="{{ $menuKey }}"
                                    @checked($isOwner || in_array($menuKey, $permissions[$roleKey] ?? [], true))
                                    @disabled($isOwner)
                                >
                                <span>{{ $menu['abbr'] }}</span>
                                <div>
                                    <strong>{{ $menu['label'] }}</strong>
                                    <small>{{ $menu['route'] }}</small>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="permission-drawer-actions">
                        <button type="button" class="ghost-button" data-close-permission>Cancel</button>
                        <button type="submit">Save permissions</button>
                    </div>
                </aside>
            @endforeach
        </form>
    </section>

    <script>
        document.querySelectorAll('[data-open-permission]').forEach((button) => {
            button.addEventListener('click', () => {
                const role = button.dataset.openPermission;
                document.querySelectorAll('[data-permission-drawer]').forEach((drawer) => {
                    drawer.classList.toggle('is-open', drawer.dataset.permissionDrawer === role);
                    drawer.setAttribute('aria-hidden', drawer.dataset.permissionDrawer === role ? 'false' : 'true');
                });
                document.querySelector('[data-permission-backdrop]')?.classList.add('is-open');
            });
        });

        document.querySelectorAll('[data-close-permission], [data-permission-backdrop]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-permission-drawer]').forEach((drawer) => {
                    drawer.classList.remove('is-open');
                    drawer.setAttribute('aria-hidden', 'true');
                });
                document.querySelector('[data-permission-backdrop]')?.classList.remove('is-open');
            });
        });
    </script>
@endsection
