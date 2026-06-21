@extends('layouts.admin', ['title' => 'Notifications', 'hideSidebar' => true])

@section('content')
    <section class="admin-section">
        <div class="section-title">
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
            @if ($notifications->whereNull('read_at')->isNotEmpty())
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="filter-button">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="notification-page-list">
            @forelse ($notifications as $notification)
                <article @class(['notification-page-item', 'is-unread' => ! $notification->read_at])>
                    <div>
                        <small>{{ strtoupper(str_replace('_', ' ', $notification->type)) }}</small>
                        <h3>{{ $notification->title }}</h3>
                        <p>{{ $notification->message }}</p>
                        <span>{{ $notification->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @unless ($notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit">Mark read</button>
                        </form>
                    @else
                        <span class="status-chip stock-ok">Read</span>
                    @endunless
                </article>
            @empty
                <div class="empty-state tight-empty">No notifications yet.</div>
            @endforelse
        </div>

        {{ $notifications->links() }}
    </section>
@endsection
