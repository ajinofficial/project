<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} - InApp Inventory</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
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
            @endphp
            <nav class="inapp-nav">
                <small>Main</small>
                @foreach ($sidebarMenus as $menuKey => $menu)
                    @if ($canOpenMenu($menuKey))
                        <a href="{{ route($menu['route']) }}" @class(['active' => request()->routeIs($menu['active'])])>
                            <span>{{ $menu['abbr'] }}</span> {{ $menu['label'] }}
                        </a>
                    @endif
                @endforeach

                <small>Account</small>
                <form method="POST" action="{{ route('logout') }}" class="inapp-nav-form">
                    @csrf
                    <button type="submit"><span>LO</span> Logout</button>
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
                <button class="inapp-menu-button" type="button" aria-label="Menu">☰</button>
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
</body>
</html>
