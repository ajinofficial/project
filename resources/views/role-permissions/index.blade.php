@extends('layouts.admin', ['title' => 'Role Permissions'])

@section('content')
    @php
        $permissionDefaults = \App\Support\RolePermission::defaults();
        $roleKeys = collect(array_keys($roles))
            ->map(fn ($role) => \App\Support\RolePermission::roleKey((int) $role))
            ->values();
    @endphp

    <section class="permission-page">
        <header class="permission-hero">
            <div>
                <p class="eyebrow">Access control</p>
                <h1>Role permissions</h1>
                <p>Configure sidebar access by role. Owner always keeps every menu enabled.</p>
            </div>
        </header>

        @if ($errors->any())
            <div class="error-summary" role="alert">
                @foreach ($errors->all() as $error)
                    <span>{{ $error }}</span>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('role-permissions.update') }}" class="permission-form" data-permission-form>
            @csrf
            @method('PUT')

            <section class="permission-summary-grid">
                @foreach ($roles as $roleId => $roleLabel)
                    @php
                        $roleKey = \App\Support\RolePermission::roleKey((int) $roleId);
                        $isOwner = (int) $roleId === \App\Models\User::ROLE_OWNER;
                        $selectedMenus = $isOwner ? array_keys($menus) : ($permissions[$roleKey] ?? []);
                    @endphp

                    <button type="button" class="permission-summary-card" data-open-permission="{{ $roleKey }}" data-summary-card="{{ $roleKey }}">
                        <span>{{ strtoupper(substr($roleLabel, 0, 2)) }}</span>
                        <strong>
                            <b data-summary-count="{{ $roleKey }}">{{ count($selectedMenus) }}</b>
                            <em>/ {{ count($menus) }}</em>
                        </strong>
                        <small>{{ $roleLabel }}</small>
                        <i style="--permission-progress: {{ count($menus) > 0 ? round((count($selectedMenus) / count($menus)) * 100) : 0 }}%" data-summary-meter="{{ $roleKey }}"></i>
                    </button>
                @endforeach
            </section>

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

                        <article class="permission-role-card" data-role-card="{{ $roleKey }}">
                            <div class="permission-role-avatar">{{ strtoupper(substr($roleLabel, 0, 2)) }}</div>
                            <div>
                                <strong>{{ $roleLabel }}</strong>
                                <span data-role-count="{{ $roleKey }}">{{ $isOwner ? 'Full access' : count($selectedMenus).' menu permissions' }}</span>
                                <div class="permission-chip-list" data-role-chips="{{ $roleKey }}">
                                    @foreach (array_slice($selectedMenus, 0, 4) as $menuKey)
                                        <small>{{ $menus[$menuKey]['label'] ?? $menuKey }}</small>
                                    @endforeach
                                    @if (count($selectedMenus) > 4)
                                        <small>+{{ count($selectedMenus) - 4 }} more</small>
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="permission-edit-button" data-open-permission="{{ $roleKey }}" aria-label="Edit {{ $roleLabel }} permissions">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                                </svg>
                                <span>Edit</span>
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
                        <button type="button" data-close-permission aria-label="Close permissions panel">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="permission-drawer-tools">
                        <input type="search" placeholder="Search menus" data-permission-search="{{ $roleKey }}">
                        <strong data-drawer-count="{{ $roleKey }}">{{ $isOwner ? count($menus) : count($permissions[$roleKey] ?? []) }} selected</strong>
                    </div>

                    @unless ($isOwner)
                        <div class="permission-bulk-actions">
                            <button type="button" data-permission-select="{{ $roleKey }}" data-permission-action="all">Select all</button>
                            <button type="button" data-permission-select="{{ $roleKey }}" data-permission-action="default">Use default</button>
                            <button type="button" data-permission-select="{{ $roleKey }}" data-permission-action="none">Clear</button>
                        </div>
                    @endunless

                    <div class="permission-empty-search" data-permission-empty="{{ $roleKey }}" hidden>
                        No menus match your search.
                    </div>

                    <div class="permission-drawer-list">
                        @foreach ($menus as $menuKey => $menu)
                            @php
                                $defaultChecked = $isOwner || in_array($menuKey, $permissionDefaults[$roleKey] ?? [], true);
                            @endphp
                            <label class="permission-drawer-check" data-permission-menu="{{ $roleKey }}" data-menu-label="{{ strtolower($menu['label']) }}">
                                <input
                                    type="checkbox"
                                    name="permissions[{{ $roleKey }}][]"
                                    value="{{ $menuKey }}"
                                    data-permission-checkbox="{{ $roleKey }}"
                                    data-menu-key="{{ $menuKey }}"
                                    data-menu-name="{{ $menu['label'] }}"
                                    data-default-checked="{{ $defaultChecked ? 'true' : 'false' }}"
                                    @checked($isOwner || in_array($menuKey, $permissions[$roleKey] ?? [], true))
                                    @disabled($isOwner)
                                >
                                <span>{{ $menu['abbr'] }}</span>
                                <div>
                                    <strong>{{ $menu['label'] }}</strong>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="permission-drawer-actions">
                        <button type="button" class="ghost-button" data-close-permission>Cancel</button>
                        <button type="submit" data-permission-submit>
                            <span class="permission-save-spinner" aria-hidden="true"></span>
                            <span>Save permissions</span>
                        </button>
                    </div>
                </aside>
            @endforeach

        </form>
    </section>

    <script>
        (function () {
            const form = document.querySelector('[data-permission-form]');

            if (! form) {
                return;
            }

            const roleKeys = @json($roleKeys);
            const initialState = {};

            form.querySelectorAll('[data-permission-checkbox]').forEach((checkbox) => {
                initialState[checkbox.dataset.permissionCheckbox + ':' + checkbox.value] = checkbox.checked;
            });

            function roleCheckboxes(role) {
                return Array.from(form.querySelectorAll(`[data-permission-checkbox="${role}"]`));
            }

            function checkedMenus(role) {
                return roleCheckboxes(role)
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => ({
                        key: checkbox.dataset.menuKey,
                        name: checkbox.dataset.menuName,
                    }));
            }

            function updateRole(role) {
                const selected = checkedMenus(role);
                const total = selected.length;
                const summaryCount = form.querySelector(`[data-summary-count="${role}"]`);
                const roleCount = form.querySelector(`[data-role-count="${role}"]`);
                const drawerCount = form.querySelector(`[data-drawer-count="${role}"]`);
                const chips = form.querySelector(`[data-role-chips="${role}"]`);

                if (summaryCount) {
                    summaryCount.textContent = total;
                }

                const summaryMeter = form.querySelector(`[data-summary-meter="${role}"]`);

                if (summaryMeter) {
                    const maxMenus = {{ count($menus) }};
                    const progress = maxMenus > 0 ? Math.round((total / maxMenus) * 100) : 0;
                    summaryMeter.style.setProperty('--permission-progress', `${progress}%`);
                }

                if (drawerCount) {
                    drawerCount.textContent = `${total} selected`;
                }

                if (roleCount) {
                    roleCount.textContent = role === 'owner' ? 'Full access' : `${total} menu${total === 1 ? '' : 's'} selected`;
                }

                if (chips) {
                    chips.innerHTML = '';

                    selected.slice(0, 4).forEach((menu) => {
                        const chip = document.createElement('small');
                        chip.textContent = menu.name;
                        chips.appendChild(chip);
                    });

                    if (selected.length > 4) {
                        const overflow = document.createElement('small');
                        overflow.textContent = `+${selected.length - 4} more`;
                        chips.appendChild(overflow);
                    }

                    if (selected.length === 0) {
                        const empty = document.createElement('small');
                        empty.textContent = 'No access';
                        empty.className = 'is-empty';
                        chips.appendChild(empty);
                    }
                }
            }

            function hasChanges() {
                return Array.from(form.querySelectorAll('[data-permission-checkbox]')).some((checkbox) => {
                    return initialState[checkbox.dataset.permissionCheckbox + ':' + checkbox.value] !== checkbox.checked;
                });
            }

            function updateSaveState() {
                const changed = hasChanges();

                form.classList.toggle('has-unsaved-changes', changed);
            }

            function refreshSearch(role) {
                const search = form.querySelector(`[data-permission-search="${role}"]`);
                const term = (search?.value || '').trim().toLowerCase();
                let visible = 0;

                form.querySelectorAll(`[data-permission-menu="${role}"]`).forEach((item) => {
                    const isVisible = term === '' || item.dataset.menuLabel.includes(term);
                    item.hidden = ! isVisible;
                    visible += isVisible ? 1 : 0;
                });

                const empty = form.querySelector(`[data-permission-empty="${role}"]`);

                if (empty) {
                    empty.hidden = visible > 0;
                }
            }

            function openDrawer(role) {
                document.querySelectorAll('[data-permission-drawer]').forEach((drawer) => {
                    drawer.classList.toggle('is-open', drawer.dataset.permissionDrawer === role);
                    drawer.setAttribute('aria-hidden', drawer.dataset.permissionDrawer === role ? 'false' : 'true');
                });
                document.querySelector('[data-permission-backdrop]')?.classList.add('is-open');
                form.querySelector(`[data-permission-search="${role}"]`)?.focus();
            }

            function closeDrawers() {
                document.querySelectorAll('[data-permission-drawer]').forEach((drawer) => {
                    drawer.classList.remove('is-open');
                    drawer.setAttribute('aria-hidden', 'true');
                });
                document.querySelector('[data-permission-backdrop]')?.classList.remove('is-open');
            }

            document.querySelectorAll('[data-open-permission]').forEach((button) => {
                button.addEventListener('click', () => openDrawer(button.dataset.openPermission));
            });

            document.querySelectorAll('[data-close-permission], [data-permission-backdrop]').forEach((button) => {
                button.addEventListener('click', closeDrawers);
            });

            document.querySelectorAll('[data-permission-search]').forEach((input) => {
                input.addEventListener('input', () => refreshSearch(input.dataset.permissionSearch));
            });

            document.querySelectorAll('[data-permission-select]').forEach((button) => {
                button.addEventListener('click', () => {
                    const role = button.dataset.permissionSelect;
                    const action = button.dataset.permissionAction;

                    roleCheckboxes(role).forEach((checkbox) => {
                        if (checkbox.disabled) {
                            return;
                        }

                        checkbox.checked = action === 'all'
                            ? true
                            : action === 'default'
                                ? checkbox.dataset.defaultChecked === 'true'
                                : false;
                    });

                    updateRole(role);
                    updateSaveState();
                });
            });

            form.querySelectorAll('[data-permission-checkbox]').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    updateRole(checkbox.dataset.permissionCheckbox);
                    updateSaveState();
                });
            });

            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-permission-submit]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('is-loading');
                    button.setAttribute('aria-busy', 'true');
                });

            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeDrawers();
                }
            });

            roleKeys.forEach((role) => {
                updateRole(role);
                refreshSearch(role);
            });
            updateSaveState();
        })();
    </script>
@endsection
