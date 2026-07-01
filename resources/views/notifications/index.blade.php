@extends('layouts.admin', ['title' => 'Notifications', 'hideSidebar' => true])

@section('content')
    <section class="admin-section notification-page" data-notification-page>
        <div class="section-title notification-page-head">
            <div>
                <a class="notification-back-button" href="{{ url()->previous() === url()->current() ? route('dashboard') : url()->previous() }}">
                    <span aria-hidden="true">&lt;</span> Back
                </a>
                <h2 class="notification-title">
                    <span class="notification-title-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" role="img">
                            <path d="M15 17H9m9-2V10a6 6 0 0 0-12 0v5l-2 2h16l-2-2Zm-7 5a2 2 0 0 0 2-2H9a2 2 0 0 0 2 2Z" />
                        </svg>
                    </span>
                    Notifications
                </h2>
                <p>Stock and activity alerts for this tenant.</p>
            </div>
            <div class="notification-page-actions">
                <nav class="notification-filter-tabs" aria-label="Notification filters">
                    <a href="{{ route('notifications.index') }}" @class(['active' => $filter === 'all'])>
                        All <span>{{ $allCount }}</span>
                    </a>
                    <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" @class(['active' => $filter === 'unread'])>
                        Unread <span>{{ $unreadCount }}</span>
                    </a>
                    <a href="{{ route('notifications.index', ['filter' => 'read']) }}" @class(['active' => $filter === 'read'])>
                        Read <span>{{ $readCount }}</span>
                    </a>
                </nav>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.readAll', ['filter' => $filter]) }}" data-notification-action>
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="filter-button">
                            <span class="notification-action-spinner" aria-hidden="true"></span>
                            <span>Mark all read</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="notification-summary-grid">
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}">
                <span>Unread</span>
                <strong>{{ $unreadCount }}</strong>
                <small>Need attention</small>
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'read']) }}">
                <span>Read</span>
                <strong>{{ $readCount }}</strong>
                <small>Already reviewed</small>
            </a>
            <a href="{{ route('notifications.index') }}">
                <span>Total</span>
                <strong>{{ $allCount }}</strong>
                <small>All alerts</small>
            </a>
        </div>

        <div class="notification-page-list">
            @forelse ($notifications as $notification)
                <article @class(['notification-page-item', 'is-unread' => ! $notification->read_at])>
                    <div>
                        <div class="notification-page-meta">
                            <small>{{ strtoupper(str_replace('_', ' ', $notification->type)) }}</small>
                            @if ($notification->read_at)
                                <span class="notification-state is-read">Read</span>
                            @else
                                <span class="notification-state is-unread">Unread</span>
                            @endif
                        </div>
                        <h3>{{ $notification->title }}</h3>
                        <p>{{ $notification->message }}</p>
                        <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('d M Y, h:i A') }}</time>
                    </div>
                    @unless ($notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', ['notification' => $notification, 'filter' => $filter]) }}" data-notification-action>
                            @csrf
                            @method('PATCH')
                            <button type="submit">
                                <span class="notification-action-spinner" aria-hidden="true"></span>
                                <span>Mark read</span>
                            </button>
                        </form>
                    @else
                        <span class="status-chip stock-ok">Read</span>
                    @endunless
                </article>
            @empty
                <div class="empty-state tight-empty">
                    @if ($filter === 'unread')
                        No unread notifications.
                    @elseif ($filter === 'read')
                        No read notifications.
                    @else
                        No notifications yet.
                    @endif
                </div>
            @endforelse
        </div>

        {{ $notifications->links() }}
    </section>

    <script>
        document.querySelectorAll('[data-notification-action]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');

                if (! button) {
                    return;
                }

                button.disabled = true;
                button.classList.add('is-loading');
                button.setAttribute('aria-busy', 'true');
            });
        });
    </script>
@endsection
