<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} - InApp Inventory</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ filemtime(public_path('css/register.css')) }}">
</head>
<body class="admin-body inapp-body">
    <div @class(['inapp-shell', 'inapp-shell-full' => $hideSidebar ?? false])>
        @unless ($hideSidebar ?? false)
        <aside class="inapp-sidebar" aria-label="Admin navigation">
            <a class="inapp-brand" href="{{ route('dashboard') }}">
                <span class="inapp-brand-mark">SP</span>
                <span><b>{{ auth()->user()->tenant->business_name ?? 'StockPilot' }}</b><small>SaaS Inventory</small></span>
            </a>

            @php
                $sidebarMenus = \App\Support\RolePermission::MENUS;
                $canOpenMenu = fn (string $menu) => \App\Support\RolePermission::canAccess(auth()->user(), $menu);
                $sidebarIcon = function (string $menuKey): string {
                    return match ($menuKey) {
                        'dashboard' => '<path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-4H4v4Z" />',
                        'inventory' => '<path d="M4 7h16v13H4V7Z" /><path d="M8 7V4h8v3" /><path d="M8 12h8" />',
                        'billing' => '<path d="M6 3h12v18l-2-1-2 1-2-1-2 1-2-1-2 1V3Z" /><path d="M9 8h6" /><path d="M9 12h6" /><path d="M9 16h4" />',
                        'purchases' => '<path d="M6 7h15l-2 8H8L6 3H3" /><path d="M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" /><path d="M18 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />',
                        'suppliers' => '<path d="M3 16V6h11v10H3Z" /><path d="M14 10h4l3 3v3h-7v-6Z" /><path d="M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" /><path d="M17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />',
                        'customers' => '<path d="M16 11a4 4 0 1 0-8 0" /><path d="M4 21a8 8 0 0 1 16 0" /><path d="M12 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z" />',
                        'returns' => '<path d="M9 7H4v5" /><path d="M4 12a8 8 0 1 0 3-6" /><path d="M4 7l5 5" />',
                        'reports' => '<path d="M4 20V4h16v16H4Z" /><path d="M8 16v-5" /><path d="M12 16V8" /><path d="M16 16v-3" />',
                        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
                        'setup' => '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.05.05a2 2 0 1 1-2.83 2.83l-.05-.05A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6l-.07.07a2 2 0 1 1-3.86 0L10 20a1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.88.34l-.05.05a2 2 0 1 1-2.83-2.83l.05-.05A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1l-.07-.07a2 2 0 1 1 0-3.86L4 10a1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.34-1.88l-.05-.05a2 2 0 1 1 2.83-2.83l.05.05A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6l.07-.07a2 2 0 1 1 3.86 0L14 4a1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.88-.34l.05-.05a2 2 0 1 1 2.83 2.83l-.05.05A1.7 1.7 0 0 0 19.4 9c.2.35.4.68.6 1l.07.07a2 2 0 1 1 0 3.86L20 14c-.2.32-.4.65-.6 1Z" />',
                        'role_permissions' => '<path d="M12 3l8 4v5c0 5-3.4 8-8 9-4.6-1-8-4-8-9V7l8-4Z" /><path d="M9 12l2 2 4-5" />',
                        default => '<path d="M5 5h14v14H5V5Z" />',
                    };
                };
            @endphp
            <nav class="inapp-nav">
                <small>Main</small>
                @foreach ($sidebarMenus as $menuKey => $menu)
                    @if ($canOpenMenu($menuKey))
                        <a href="{{ route($menu['route']) }}" @class(['active' => request()->routeIs($menu['active'])])>
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img">{!! $sidebarIcon($menuKey) !!}</svg>
                            </span>
                            {{ $menu['label'] }}
                        </a>
                    @endif
                @endforeach

                <small>Account</small>
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    class="inapp-nav-form"
                    data-confirm
                    data-confirm-title="Logout"
                    data-confirm-message="Are you sure you want to logout from this account?"
                    data-confirm-button="Logout"
                >
                    @csrf
                    <button type="submit">
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="img">
                                <path d="M10 17l5-5-5-5" />
                                <path d="M15 12H3" />
                                <path d="M21 3v18h-8" />
                            </svg>
                        </span>
                        Logout
                    </button>
                </form>
            </nav>

            <div class="inapp-store-card">
                <small>Current plan</small>
                <strong>{{ auth()->user()->plan_label }}</strong>
                <span>{{ auth()->user()->role_label }}</span>
            </div>
        </aside>
        @endunless

        <main class="inapp-main">
            <header class="inapp-topbar">
                <strong class="inapp-top-title">{{ $title ?? 'Dashboard' }}</strong>
                <div class="inapp-top-actions">
                    <details class="inapp-notification-menu">
                        <summary class="inapp-notification" aria-label="Notifications">
                            <span class="notification-bell" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img">
                                    <path d="M15 17H9m9-2V10a6 6 0 0 0-12 0v5l-2 2h16l-2-2Zm-7 5a2 2 0 0 0 2-2H9a2 2 0 0 0 2 2Z" />
                                </svg>
                            </span>
                            @if (($navUnreadNotificationCount ?? 0) > 0)
                                <span class="notification-badge">{{ $navUnreadNotificationCount > 99 ? '99+' : $navUnreadNotificationCount }}</span>
                            @endif
                        </summary>
                        <div class="notification-panel">
                            <div class="notification-panel-head">
                                <strong>Notifications</strong>
                                @if (($navUnreadNotificationCount ?? 0) > 0)
                                    <form method="POST" action="{{ route('notifications.readAll') }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            <div class="notification-list">
                                @forelse (($navNotifications ?? collect()) as $notification)
                                    <div @class(['notification-item', 'is-unread' => ! $notification->read_at])>
                                        <a href="{{ route('notifications.index') }}">
                                            <strong>{{ $notification->title }}</strong>
                                            <span>{{ $notification->message }}</span>
                                            <small>{{ $notification->created_at->diffForHumans() }}</small>
                                        </a>
                                        @unless ($notification->read_at)
                                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit">Read</button>
                                            </form>
                                        @endunless
                                    </div>
                                @empty
                                    <div class="notification-empty">No notifications yet.</div>
                                @endforelse
                            </div>
                            <a class="notification-view-all" href="{{ route('notifications.index') }}">View all notifications</a>
                        </div>
                    </details>
                    <span class="inapp-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                </div>
            </header>

            @if (session('status'))
                <div class="flash-message" role="status">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    @include('partials.confirm-modal')

    <div class="page-loader" data-page-loader aria-live="polite" aria-hidden="true">
        <div class="page-loader__panel">
            <span class="page-loader__spinner" aria-hidden="true"></span>
            <strong>Loading</strong>
        </div>
    </div>

    <script>
        (function () {
            var pageLoader = document.querySelector('[data-page-loader]');

            function showPageLoader() {
                if (! pageLoader) {
                    return;
                }

                pageLoader.classList.add('is-active');
                pageLoader.setAttribute('aria-hidden', 'false');
                document.body.classList.add('page-loader-open');
            }

            window.addEventListener('pageshow', function () {
                if (! pageLoader) {
                    return;
                }

                pageLoader.classList.remove('is-active');
                pageLoader.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('page-loader-open');
            });

            document.querySelectorAll('.inapp-nav a[href]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (event.defaultPrevented) {
                        return;
                    }

                    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }

                    var href = link.getAttribute('href') || '';

                    if (href === '' || href.startsWith('#') || link.target === '_blank' || link.hasAttribute('download')) {
                        return;
                    }

                    showPageLoader();
                });
            });

            document.querySelectorAll('.inapp-nav-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (event.defaultPrevented) {
                        return;
                    }

                    if (form.matches('[data-confirm]') && form.dataset.confirmed !== 'true') {
                        return;
                    }

                    showPageLoader();
                });
            });

            document.addEventListener('submit', function (event) {
                if (! event.target.matches('.inapp-nav-form')) {
                    return;
                }
            });

            var modal = document.querySelector('[data-confirm-modal]');
            if (! modal) {
                return;
            }

            var title = modal.querySelector('[data-confirm-title]');
            var message = modal.querySelector('[data-confirm-message]');
            var submitButton = modal.querySelector('[data-confirm-submit]');
            var cancelButtons = modal.querySelectorAll('[data-confirm-cancel]');
            var activeForm = null;
            var activeTrigger = null;
            var isBlockedAction = false;

            function closeModal() {
                modal.hidden = true;
                document.body.classList.remove('confirm-modal-open');
                cancelButtons.forEach(function (button) {
                    button.hidden = false;
                });

                if (activeTrigger) {
                    activeTrigger.focus();
                }

                activeForm = null;
                activeTrigger = null;
                isBlockedAction = false;
            }

            function openModal(form, trigger) {
                activeForm = form;
                activeTrigger = trigger || document.activeElement;
                isBlockedAction = form.dataset.confirmBlocked === 'true';
                title.textContent = form.dataset.confirmTitle || 'Confirm action';
                message.textContent = form.dataset.confirmMessage || 'Are you sure you want to continue?';
                submitButton.textContent = form.dataset.confirmButton || 'Confirm';
                cancelButtons.forEach(function (button) {
                    button.hidden = isBlockedAction;
                });

                modal.hidden = false;
                document.body.classList.add('confirm-modal-open');
                submitButton.focus();
            }

            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.confirmed === 'true') {
                        delete form.dataset.confirmed;
                        return;
                    }

                    event.preventDefault();
                    openModal(form, event.submitter);
                });
            });

            submitButton.addEventListener('click', function () {
                if (isBlockedAction) {
                    closeModal();
                    return;
                }

                if (! activeForm) {
                    return;
                }

                var form = activeForm;
                closeModal();
                showPageLoader();
                HTMLFormElement.prototype.submit.call(form);
            });

            cancelButtons.forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function (event) {
                if (! modal.hidden && event.key === 'Escape') {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>
