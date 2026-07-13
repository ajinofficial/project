@extends('layouts.admin', ['title' => 'Notifications', 'hideSidebar' => true])

@section('content')
    @php
        $queryState = array_filter(['filter' => $filter, 'search' => $search, 'type' => $type], fn ($value) => $value !== '' && $value !== 'all');
    @endphp
    <section class="admin-section notification-page" data-notification-page>
        <div class="section-title notification-page-head">
            <div>
                <a class="notification-back-button" href="{{ url()->previous() === url()->current() ? route(\App\Support\RolePermission::firstAccessibleRoute(auth()->user())) : url()->previous() }}">
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
                    <a href="{{ route('notifications.index', array_filter(['search' => $search, 'type' => $type])) }}" @class(['active' => $filter === 'all'])>
                        All <span>{{ $allCount }}</span>
                    </a>
                    <a href="{{ route('notifications.index', array_filter(['filter' => 'unread', 'search' => $search, 'type' => $type])) }}" @class(['active' => $filter === 'unread'])>
                        Unread <span>{{ $unreadCount }}</span>
                    </a>
                    <a href="{{ route('notifications.index', array_filter(['filter' => 'read', 'search' => $search, 'type' => $type])) }}" @class(['active' => $filter === 'read'])>
                        Read <span>{{ $readCount }}</span>
                    </a>
                </nav>
                @if ($unreadCount > 0)
                    <form
                        method="POST"
                        action="{{ route('notifications.readAll', ['filter' => $filter]) }}"
                        data-confirm
                        data-confirm-title="Mark all notifications as read?"
                        data-confirm-message="This will mark all {{ number_format($unreadCount) }} unread notifications as read."
                        data-confirm-button="Mark all read"
                    >
                        @csrf
                        @method('PATCH')
                        @foreach ($queryState as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <button type="submit" class="notification-read-all-button">
                            <span class="notification-read-all-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path><path d="m13 12 3 3 5-6"></path></svg>
                            </span>
                            <span class="notification-read-all-copy">
                                <strong>Mark all read</strong>
                                <small>{{ number_format($unreadCount) }} {{ Str::plural('alert', $unreadCount) }} pending</small>
                            </span>
                        </button>
                    </form>
                @else
                    <span class="notification-all-read-state">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
                        All caught up
                    </span>
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

        <section class="notification-tools" aria-label="Find notifications">
            <form method="GET" action="{{ route('notifications.index') }}" data-notification-filter-form>
                @if ($filter !== 'all')
                    <input type="hidden" name="filter" value="{{ $filter }}">
                @endif
                <label class="notification-search-field">
                    <span class="sr-only">Search notifications</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                    <input type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Search title, message, or type" data-notification-search>
                </label>
                <select name="type" aria-label="Notification type" data-notification-type>
                    <option value="">All types</option>
                    @foreach ($types as $notificationType)
                        <option value="{{ $notificationType }}" @selected($type === $notificationType)>{{ ucfirst(str_replace('_', ' ', $notificationType)) }}</option>
                    @endforeach
                </select>

                @if ($search !== '' || $type !== '')
                    <a class="product-clear-filter" href="{{ route('notifications.index', $filter === 'all' ? [] : ['filter' => $filter]) }}">Clear</a>
                @endif
            </form>
            <span>{{ number_format($notifications->total()) }} matching {{ Str::plural('notification', $notifications->total()) }}</span>
        </section>

        <div class="notification-page-list" data-notification-list>
            @forelse ($notifications as $notification)
                <article @class(['notification-page-item', 'is-unread' => ! $notification->read_at])>
                    @php
                        $iconClass = str_contains($notification->type, 'stock') ? 'is-stock' : (str_contains($notification->type, 'sale') ? 'is-sale' : 'is-activity');
                    @endphp
                    <span class="notification-type-icon {{ $iconClass }}" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 3 4 7v5c0 4.8 3.2 8 8 9 4.8-1 8-4.2 8-9V7l-8-4Z"></path><path d="M9 12h6M12 9v6"></path></svg>
                    </span>
                    <div class="notification-page-content">
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
                        <time datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('d M Y, h:i A') }}">{{ $notification->created_at->diffForHumans() }}</time>
                    </div>
                    <div class="notification-item-actions">
                        @unless ($notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}" data-notification-action>
                            @csrf
                            @method('PATCH')
                            @foreach ($queryState as $name => $value)
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endforeach
                            <button type="submit">
                                <span class="notification-action-spinner" aria-hidden="true"></span>
                                <span>Mark read</span>
                            </button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('notifications.unread', $notification) }}" data-notification-action>
                            @csrf
                            @method('PATCH')
                            @foreach ($queryState as $name => $value)
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endforeach
                            <button class="is-secondary" type="submit">
                                <span class="notification-action-spinner" aria-hidden="true"></span>
                                <span>Mark unread</span>
                            </button>
                        </form>
                        @endunless
                    </div>
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

        <div class="notification-pagination" data-notification-pagination data-next-page="{{ $notifications->nextPageUrl() }}">
            {{ $notifications->links() }}
        </div>
        <div class="notification-infinite-loader" data-notification-loader aria-live="polite" hidden>
            <span aria-hidden="true"></span>
            <strong>Loading notifications</strong>
        </div>
        <div class="notification-scroll-end" data-notification-scroll-end hidden>All notifications loaded.</div>
        <div class="notification-scroll-sentinel" data-notification-scroll-sentinel aria-hidden="true"></div>
    </section>

    <script>
        document.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-notification-action]');

            if (!form) {
                return;
            }

            var button = form.querySelector('button[type="submit"]');

            if (!button) {
                return;
            }

            button.disabled = true;
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');
        });

        var filterForm = document.querySelector('[data-notification-filter-form]');

        if (filterForm) {
            var typeFilter = filterForm.querySelector('[data-notification-type]');
            var searchFilter = filterForm.querySelector('[data-notification-search]');
            var filterTimer = null;

            function submitFilters() {
                filterForm.classList.add('is-filtering');

                if (typeof filterForm.requestSubmit === 'function') {
                    filterForm.requestSubmit();
                    return;
                }

                filterForm.submit();
            }

            if (typeFilter) {
                typeFilter.addEventListener('change', submitFilters);
            }

            if (searchFilter) {
                searchFilter.addEventListener('input', function () {
                    window.clearTimeout(filterTimer);
                    filterTimer = window.setTimeout(submitFilters, 400);
                });
                searchFilter.addEventListener('search', function () {
                    window.clearTimeout(filterTimer);
                    submitFilters();
                });
            }
        }

        (function initialiseNotificationScroll() {
            var list = document.querySelector('[data-notification-list]');
            var pagination = document.querySelector('[data-notification-pagination]');
            var loader = document.querySelector('[data-notification-loader]');
            var sentinel = document.querySelector('[data-notification-scroll-sentinel]');
            var endMessage = document.querySelector('[data-notification-scroll-end]');

            if (!list || !pagination || !loader || !sentinel || typeof window.fetch !== 'function') {
                return;
            }

            if (!('IntersectionObserver' in window)) {
                return;
            }

            var nextPage = pagination.dataset.nextPage || '';
            var loading = false;
            var observer = null;
            pagination.classList.add('is-enhanced');

            function showEndMessage() {
                pagination.hidden = true;
                sentinel.hidden = true;

                if (list.querySelector('.notification-page-item')) {
                    endMessage.hidden = false;
                }

                if (observer) {
                    observer.disconnect();
                }
            }

            function loadNextPage() {
                if (loading || !nextPage) {
                    if (!nextPage) showEndMessage();
                    return;
                }

                loading = true;
                loader.hidden = false;
                loader.classList.remove('is-error');
                loader.innerHTML = '<span aria-hidden="true"></span><strong>Loading notifications</strong>';

                fetch(nextPage, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Unable to load notifications.');
                        return response.text();
                    })
                    .then(function (html) {
                        var documentPage = new DOMParser().parseFromString(html, 'text/html');
                        var nextList = documentPage.querySelector('[data-notification-list]');
                        var nextPagination = documentPage.querySelector('[data-notification-pagination]');

                        if (!nextList || !nextPagination) throw new Error('Invalid notification response.');

                        nextList.querySelectorAll('.notification-page-item').forEach(function (item) {
                            list.appendChild(item);
                        });

                        nextPage = nextPagination.dataset.nextPage || '';
                        pagination.dataset.nextPage = nextPage;

                        if (!nextPage) showEndMessage();
                    })
                    .catch(function () {
                        loader.classList.add('is-error');
                        loader.innerHTML = '<strong>Could not load notifications.</strong><button type="button" data-notification-retry>Retry</button>';
                    })
                    .finally(function () {
                        loading = false;

                        if (!loader.classList.contains('is-error')) {
                            loader.hidden = true;
                        }
                    });
            }

            loader.addEventListener('click', function (event) {
                if (event.target.closest('[data-notification-retry]')) loadNextPage();
            });

            if (!nextPage) {
                pagination.hidden = true;
                sentinel.hidden = true;
                return;
            }

            observer = new IntersectionObserver(function (entries) {
                if (entries[0] && entries[0].isIntersecting) loadNextPage();
            }, { rootMargin: '240px 0px' });
            observer.observe(sentinel);
        })();
    </script>
@endsection
