<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('dashboard.dashboard_d87f47') }} - Zyloah</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Zyloah-logo.png') }}">
    @include('partials.app-base-url')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
    @include('partials.localization')
</head>
<body class="admin-body inapp-body">
    <div @class(['inapp-shell', 'inapp-shell-full' => $hideSidebar ?? false])>
        @unless ($hideSidebar ?? false)
        <aside class="inapp-sidebar" aria-label="{{ __('common.admin_navigation_8ce001') }}">
            <a class="inapp-brand" href="{{ route(\App\Support\RolePermission::firstAccessibleRoute(auth()->user())) }}">
                <span class="inapp-brand-logo" role="img" aria-label="{{ __('common.zyloah_logo_216929') }}"></span>
                <span>
                    <b>{{ auth()->user()->tenant->business_name ?? 'Zyloah' }}</b>
                    <small>{{ (int) auth()->user()->tenant?->tenant_type === \App\Models\Tenant::TYPE_VENDOR ? __('common.vendor_console_711c5f') : __('products.saas_inventory_073f7c') }}</small>
                </span>
            </a>

            @php
                $sidebarMenus = \App\Support\RolePermission::MENUS;
                $canOpenMenu = fn (string $menu) => \App\Support\RolePermission::canAccess(auth()->user(), $menu);
                $sidebarIcon = function (string $menuKey): string {
                    return match ($menuKey) {
                        'services' => '<path d="m14 6 4 4M4 20l8-8M14 3a6 6 0 0 0-6 8L3 16l5 5 5-5a6 6 0 0 0 8-6l-4 3-4-4 3-4-2-2Z" />',
                        'vendor_dashboard' => '<path d="M4 21V7l8-4 8 4v14" /><path d="M9 21v-7h6v7" /><path d="M8 10h2" /><path d="M14 10h2" />',
                        'dashboard' => '<path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-4H4v4Z" />',
                        'clients' => '<path d="M3 21V7l9-4 9 4v14" /><path d="M9 21v-7h6v7" /><path d="M7 10h2" /><path d="M15 10h2" />',
                        'inventory' => '<path d="M4 7h16v13H4V7Z" /><path d="M8 7V4h8v3" /><path d="M8 12h8" />',
                        'stock' => '<path d="M4 7l8-4 8 4-8 4-8-4Z" /><path d="M4 7v10l8 4 8-4V7" /><path d="M12 11v10" />',
                        'billing' => '<path d="M6 3h12v18l-2-1-2 1-2-1-2 1-2-1-2 1V3Z" /><path d="M9 8h6" /><path d="M9 12h6" /><path d="M9 16h4" />',
                        'purchases' => '<path d="M6 7h15l-2 8H8L6 3H3" /><path d="M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" /><path d="M18 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />',
                        'expenses' => '<path d="M4 6h16v14H4V6Z" /><path d="M16 10h4v6h-4a3 3 0 0 1 0-6Z" /><path d="M7 6V4h10v2" />',
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
                <small>{{ __('common.main_62bce9') }}</small>
                @foreach ($sidebarMenus as $menuKey => $menu)
                    @if ($canOpenMenu($menuKey))
                        <a href="{{ route($menu['route']) }}" @class(['active' => request()->routeIs($menu['active'])])>
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img">{!! $sidebarIcon($menuKey) !!}</svg>
                            </span>
                            {{ __($menu['label']) }}
                        </a>
                    @endif
                @endforeach

                @if (
                    (int) auth()->user()->tenant?->tenant_type === \App\Models\Tenant::TYPE_CLIENT
                    && auth()->user()->tenant?->plan?->name === 'growth'
                )
                    <a href="{{ route('whatsapp.index') }}" @class(['active' => request()->routeIs('whatsapp.*')])>
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="img">
                                <path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5Z" />
                                <path d="M8.5 8.5c.5 3 2 4.5 5 5" />
                            </svg>
                        </span>
                        {{ __('common.whatsapp_b336fc') }}
                    </a>
                @endif

                @if ((int) auth()->user()->tenant?->tenant_type === \App\Models\Tenant::TYPE_VENDOR)
                    <a href="{{ route('email.index') }}" @class(['active' => request()->routeIs('email.*')])>
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="img">
                                <path d="M3 5h18v14H3V5Z" />
                                <path d="m3 7 9 6 9-6" />
                            </svg>
                        </span>
                        {{ __('common.email_84add5') }}
                    </a>
                @endif

                <small>{{ __('common.account_85dfa3') }}</small>
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    class="inapp-nav-form"
                    data-confirm
                    data-confirm-title="{{ __('common.logout_e43d61') }}"
                    data-confirm-message="{{ __('common.are_you_sure_you_want_to_logout_from_this_account_1f705e') }}"
                    data-confirm-button="{{ __('common.logout_e43d61') }}"
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
                        {{ __('common.logout_e43d61') }}
                    </button>
                </form>
            </nav>

            <div class="inapp-store-card">
                <small>{{ __('settings.current_plan_ec5cfb') }}</small>
                <strong>{{ __(auth()->user()->plan_label) }}</strong>
                <span>{{ __(auth()->user()->role_label) }}</span>
            </div>
        </aside>
        @endunless

        <main class="inapp-main">
            <header class="inapp-topbar">
                <strong class="inapp-top-title">{{ $title ?? __('dashboard.dashboard_d87f47') }}</strong>
                <div class="inapp-top-actions">
                    @include('partials.language-selector')
                    <details class="inapp-notification-menu">
                        <summary class="inapp-notification" aria-label="{{ __('notifications.notifications_753a22') }}">
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
                                <strong>{{ __('notifications.notifications_753a22') }}</strong>
                                @if (($navUnreadNotificationCount ?? 0) > 0)
                                    <form method="POST" action="{{ route('notifications.readAll') }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit">{{ __('notifications.mark_all_read_8958e2') }}</button>
                                    </form>
                                @endif
                            </div>
                            <div class="notification-list">
                                @forelse (($navNotifications ?? collect()) as $notification)
                                    <div @class(['notification-item', 'is-unread' => ! $notification->read_at])>
                                        <a href="{{ route('notifications.index', ['redirect_url' => \App\Support\RedirectUrl::encode(request()->fullUrl())]) }}">
                                            <strong>{{ $notification->title }}</strong>
                                            <span>{{ $notification->message }}</span>
                                            <small>{{ $notification->created_at->diffForHumans() }}</small>
                                        </a>
                                        @unless ($notification->read_at)
                                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit">{{ __('notifications.read_852b43') }}</button>
                                            </form>
                                        @endunless
                                    </div>
                                @empty
                                    <div class="notification-empty">{{ __('notifications.no_notifications_yet_5537c6') }}</div>
                                @endforelse
                            </div>
                            <a class="notification-view-all" href="{{ route('notifications.index', ['redirect_url' => \App\Support\RedirectUrl::encode(request()->fullUrl())]) }}">{{ __('notifications.view_all_notifications_61d4c4') }}</a>
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

    @php
        $trialTenant = auth()->user()?->tenant;
        $expiredFreeTrial = $trialTenant?->plan?->name === 'free_trial'
            && $trialTenant?->domain_expired_date
            && $trialTenant->domain_expired_date->endOfDay()->isPast();
        $upgradePlans = $expiredFreeTrial
            ? \App\Models\Plan::where('monthly_price', '>', 0)->orderBy('monthly_price')->get()
            : collect();
    @endphp

    @if ($expiredFreeTrial)
        <div class="trial-upgrade-modal" data-trial-upgrade-modal role="dialog" aria-modal="true" aria-labelledby="trial-upgrade-title">
            <div class="trial-upgrade-modal__backdrop"></div>
            <section class="trial-upgrade-modal__dialog">
                <p class="eyebrow">{{ __('common.free_trial_completed_846c8d') }}</p>
                <h2 id="trial-upgrade-title">{{ __('settings.choose_a_plan_to_continue_38e577') }}</h2>
                <p>{{ __('common.trial_ended', ['date' => $trialTenant->domain_expired_date->translatedFormat('d M Y'), 'business' => $trialTenant->business_name]) }}</p>

                @if ((int) auth()->user()->role === \App\Models\User::ROLE_OWNER)
                    <form method="POST" action="{{ route('plan.upgrade') }}" data-trial-upgrade-form>
                        @csrf
                        <div class="trial-upgrade-plans">
                            @foreach ($upgradePlans as $plan)
                                <label>
                                    <input type="radio" name="plan_id" value="{{ $plan->id }}" required>
                                    <span>
                                        <strong>{{ \Illuminate\Support\Str::headline($plan->name) }}</strong>
                                        <b>&#8377;{{ number_format($plan->monthly_price) }}<small>{{ __('dashboard.month_ab0cc3') }}</small></b>
                                        <em>{{ $plan->features }}</em>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <small data-trial-upgrade-message aria-live="polite"></small>
                        <button type="submit">{{ __('settings.save_and_upgrade_plan_38d953') }}</button>
                    </form>
                @else
                    <div class="trial-upgrade-contact">
                        <strong>{{ __('validation.owner_action_required_385103') }}</strong>
                        <span>{{ __('auth.ask_the_business_owner_to_log_in_and_choose_a_paid_plan_d5edb9') }}</span>
                    </div>
                @endif
            </section>
        </div>
    @endif

    @if (auth()->user() && ! auth()->user()->hasVerifiedEmail())
        <aside class="email-verification-popup" data-email-verification-popup aria-label="{{ __('auth.email_verification_reminder_143db4') }}">
            <button type="button" class="email-verification-popup__close" data-email-verification-close aria-label="{{ __('common.dismiss_70afe9') }}">&times;</button>
            <span class="email-verification-popup__icon" aria-hidden="true">&#9993;</span>
            <div>
                <strong>{{ __('auth.verify_your_email_84fdc7') }}</strong>
                <p>
                    {{ __('auth.send_a_six_digit_otp_to_922923') }}
                    <span class="email-verification-popup__address">
                        <b data-verification-email>{{ auth()->user()->email }}</b>
                        <button type="button" class="email-verification-popup__edit" data-email-edit-open aria-label="{{ __('common.edit_email_00a5cb') }}" title="{{ __('common.edit_email_00a5cb') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 20h4l11-11-4-4L4 16v4Z"></path>
                                <path d="m13.5 6.5 4 4"></path>
                            </svg>
                        </button>
                    </span>.
                </p>
                <form method="POST" action="{{ route('account.email.update') }}" data-email-edit-form hidden novalidate>
                    @csrf
                    @method('PATCH')
                    <input type="email" name="email" value="{{ auth()->user()->email }}" autocomplete="email" required aria-label="{{ __('auth.new_email_address_321c2a') }}">
                    <div class="email-verification-popup__actions">
                        <button type="submit">{{ __('common.save_email_8fe6cb') }}</button>
                        <button type="button" data-email-edit-cancel>{{ __('common.cancel_77dfd2') }}</button>
                    </div>
                    <small data-email-edit-message aria-live="polite"></small>
                </form>
                <form method="POST" action="{{ route('verification.send') }}" data-email-verification-form>
                    @csrf
                    <button type="submit">{{ __('auth.verify_dda6ac') }}</button>
                    <small data-email-verification-message aria-live="polite"></small>
                </form>
                <form method="POST" action="{{ route('verification.verify') }}" data-email-otp-form hidden novalidate>
                    @csrf
                    <input type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="{{ __('auth.enter_6_digit_otp_d269d8') }}" required>
                    <div class="email-verification-popup__actions">
                        <button type="submit">{{ __('auth.verify_otp_603a2e') }}</button>
                        <button type="button" data-email-otp-resend hidden>{{ __('auth.resend_otp_b707b6') }}</button>
                    </div>
                    <small data-email-otp-message aria-live="polite"></small>
                </form>
            </div>
        </aside>
    @endif

    <div class="page-loader" data-page-loader aria-live="polite" aria-hidden="true">
        <div class="page-loader__panel">
            <span class="page-loader__spinner" aria-hidden="true"></span>
            <strong>{{ __('common.loading_8f26c6') }}</strong>
        </div>
    </div>

    <script>
        (function () {
            var upgradeForm = document.querySelector('[data-trial-upgrade-form]');
            var verificationPopup = document.querySelector('[data-email-verification-popup]');
            var verificationForm = document.querySelector('[data-email-verification-form]');
            var otpForm = document.querySelector('[data-email-otp-form]');
            var resendButton = document.querySelector('[data-email-otp-resend]');
            var emailEditForm = document.querySelector('[data-email-edit-form]');
            var emailEditButton = document.querySelector('[data-email-edit-open]');
            var emailEditCancel = document.querySelector('[data-email-edit-cancel]');

            upgradeForm?.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = upgradeForm.querySelector('button[type="submit"]');
                var message = upgradeForm.querySelector('[data-trial-upgrade-message]');
                var selectedPlan = upgradeForm.querySelector('input[name="plan_id"]:checked');

                if (!selectedPlan) {
                    message.textContent = window.t("settings.choose_a_plan_to_continue_7f241f");
                    message.classList.add('is-error');
                    return;
                }

                button.disabled = true;
                button.textContent = window.t("common.upgrading_e7a06d");
                message.textContent = '';

                fetch(upgradeForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(upgradeForm)
                }).then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload.errors?.plan_id?.[0] || payload.message || window.t("validation.unable_to_upgrade_the_plan_90d51a"));
                        }
                        return payload;
                    });
                }).then(function (payload) {
                    window.location.assign(payload.redirect || window.location.href);
                }).catch(function (error) {
                    message.textContent = error.message;
                    message.classList.add('is-error');
                    button.disabled = false;
                    button.textContent = window.t("settings.save_and_upgrade_plan_38d953");
                });
            });

            emailEditButton?.addEventListener('click', function () {
                emailEditForm.hidden = false;
                verificationForm.hidden = true;
                otpForm.hidden = true;
                emailEditButton.hidden = true;
                emailEditForm.querySelector('input[name="email"]').focus();
            });

            emailEditCancel?.addEventListener('click', function () {
                emailEditForm.hidden = true;
                verificationForm.hidden = false;
                emailEditButton.hidden = false;
                emailEditForm.querySelector('[data-email-edit-message]').textContent = '';
            });

            emailEditForm?.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = emailEditForm.querySelector('button[type="submit"]');
                var message = emailEditForm.querySelector('[data-email-edit-message]');
                var emailInput = emailEditForm.querySelector('input[name="email"]');

                if (!emailInput.value.trim() || !emailInput.checkValidity()) {
                    message.textContent = window.t("auth.enter_a_valid_email_address_2e09ed");
                    message.classList.add('is-error');
                    emailInput.focus();
                    return;
                }

                button.disabled = true;
                button.textContent = window.t("common.saving_ae7e88");

                fetch(emailEditForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(emailEditForm)
                }).then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload.errors?.email?.[0] || payload.message || window.t("validation.unable_to_update_email_e5d2a0"));
                        }
                        return payload;
                    });
                }).then(function (payload) {
                    document.querySelector('[data-verification-email]').textContent = payload.email;
                    emailInput.value = payload.email;
                    message.textContent = '';
                    message.classList.remove('is-error');
                    emailEditForm.hidden = true;
                    emailEditButton.hidden = false;
                    verificationForm.hidden = false;
                    otpForm.hidden = true;
                    verificationForm.querySelector('[data-email-verification-message]').textContent = payload.message;
                }).catch(function (error) {
                    message.textContent = error.message;
                    message.classList.add('is-error');
                }).finally(function () {
                    button.disabled = false;
                    button.textContent = window.t("common.save_email_8fe6cb");
                });
            });

            document.querySelector('[data-email-verification-close]')?.addEventListener('click', function () {
                verificationPopup.hidden = true;
            });

            verificationForm?.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = verificationForm.querySelector('button[type="submit"]');
                var message = verificationForm.querySelector('[data-email-verification-message]');
                var activeMessage = otpForm.hidden
                    ? message
                    : otpForm.querySelector('[data-email-otp-message]');
                button.disabled = true;
                resendButton.disabled = true;
                button.textContent = window.t("common.sending_c338c1");

                fetch(verificationForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(verificationForm)
                }).then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) throw new Error(payload.message || window.t("auth.unable_to_send_verification_link_4046fe"));
                        return payload;
                    });
                }).then(function (payload) {
                    activeMessage.textContent = payload.message;
                    activeMessage.classList.remove('is-error');
                    verificationForm.hidden = true;
                    otpForm.hidden = false;
                    resendButton.hidden = false;
                    otpForm.querySelector('input[name="otp"]').focus();
                }).catch(function (error) {
                    activeMessage.textContent = error.message;
                    activeMessage.classList.add('is-error');
                }).finally(function () {
                    button.disabled = false;
                    resendButton.disabled = false;
                    button.textContent = window.t("auth.resend_otp_b707b6");
                });
            });

            resendButton?.addEventListener('click', function () {
                verificationForm.requestSubmit();
            });

            otpForm?.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = otpForm.querySelector('button[type="submit"]');
                var message = otpForm.querySelector('[data-email-otp-message]');
                var otpInput = otpForm.querySelector('input[name="otp"]');
                var otp = otpInput.value.trim();

                if (button.dataset.emailVerified === 'true') {
                    verificationPopup.hidden = true;
                    return;
                }

                if (! otp) {
                    message.textContent = window.t("auth.verification_code_is_required_93daff");
                    message.classList.add('is-error');
                    otpInput.focus();
                    return;
                }

                if (! /^\d{6}$/.test(otp)) {
                    message.textContent = window.t("auth.enter_a_valid_six_digit_verification_code_17f165");
                    message.classList.add('is-error');
                    otpInput.focus();
                    return;
                }

                button.disabled = true;
                button.textContent = window.t("auth.verifying_bf6ea5");

                fetch(otpForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(otpForm)
                }).then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) throw new Error(payload.message || window.t("auth.unable_to_verify_otp_0df4b3"));
                        return payload;
                    });
                }).then(function (payload) {
                    message.textContent = payload.message;
                    message.classList.remove('is-error');
                    otpInput.hidden = true;
                    resendButton.hidden = true;
                    button.dataset.emailVerified = 'true';
                    button.textContent = window.t("common.done_e9b450");
                }).catch(function (error) {
                    message.textContent = error.message;
                    message.classList.add('is-error');
                }).finally(function () {
                    button.disabled = false;
                    if (button.dataset.emailVerified !== 'true') {
                        button.textContent = window.t("auth.verify_otp_603a2e");
                    }
                });
            });

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

            (function initialiseDatePickers() {
                var monthNames = [window.t("common.january_7a22d7"), window.t("common.february_5c3a35"), window.t("common.march_433632"), window.t("common.april_a03939"), window.t("common.may_c94f47"), window.t("common.june_a9db90"), window.t("common.july_df97a4"), window.t("common.august_69d97c"), window.t("common.september_1c542e"), window.t("common.october_87206a"), window.t("common.november_3c5bf7"), window.t("common.december_65640c")];
                var weekdayNames = [window.t("common.sun_392a0d"), window.t("common.mon_f61d4e"), window.t("common.tue_b206a7"), window.t("common.wed_6c4b87"), window.t("common.thu_2c1631"), window.t("common.fri_4a31b5"), window.t("common.sat_d20602")];
                var activePicker = null;

                function pad(value) {
                    return String(value).padStart(2, '0');
                }

                function toIso(date) {
                    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
                }

                function parseIso(value) {
                    var parts = /^([0-9]{4})-([0-9]{2})-([0-9]{2})$/.exec(value || '');

                    if (! parts) {
                        return null;
                    }

                    var date = new Date(Number(parts[1]), Number(parts[2]) - 1, Number(parts[3]));
                    return toIso(date) === value ? date : null;
                }

                function closePicker(picker, restoreFocus) {
                    if (! picker || picker.calendar.hidden) {
                        return;
                    }

                    picker.calendar.hidden = true;
                    picker.field.setAttribute('aria-expanded', 'false');
                    activePicker = activePicker === picker ? null : activePicker;

                    if (restoreFocus) {
                        picker.field.focus();
                    }
                }

                function isUnavailable(picker, date) {
                    var iso = toIso(date);
                    return (picker.field.min && iso < picker.field.min) || (picker.field.max && iso > picker.field.max);
                }

                function render(picker) {
                    var selected = parseIso(picker.field.value);
                    var selectedIso = selected ? toIso(selected) : '';
                    var todayIso = toIso(new Date());
                    var firstDay = new Date(picker.viewYear, picker.viewMonth, 1);
                    var startDate = new Date(picker.viewYear, picker.viewMonth, 1 - firstDay.getDay());

                    picker.monthSelect.value = String(picker.viewMonth);
                    picker.yearSelect.value = String(picker.viewYear);
                    picker.days.replaceChildren();

                    for (var index = 0; index < 42; index += 1) {
                        var date = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + index);
                        var iso = toIso(date);
                        var button = document.createElement('button');

                        button.type = 'button';
                        button.className = 'common-calendar__day';
                        button.textContent = String(date.getDate());
                        button.dataset.date = iso;
                        button.setAttribute('aria-label', date.toLocaleDateString(undefined, {
                            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                        }));

                        if (date.getMonth() !== picker.viewMonth) button.classList.add('is-outside');
                        if (iso === todayIso) button.classList.add('is-today');
                        if (iso === selectedIso) {
                            button.classList.add('is-selected');
                            button.setAttribute('aria-current', 'date');
                        }

                        button.disabled = isUnavailable(picker, date);
                        picker.days.appendChild(button);
                    }
                }

                function addYearOption(picker, year) {
                    if (picker.yearSelect.querySelector('option[value="' + year + '"]')) {
                        return;
                    }

                    var option = document.createElement('option');
                    option.value = String(year);
                    option.textContent = String(year);
                    picker.yearSelect.appendChild(option);
                }

                function openPicker(picker) {
                    if (activePicker && activePicker !== picker) {
                        closePicker(activePicker, false);
                    }

                    var initial = parseIso(picker.field.value) || new Date();
                    picker.viewMonth = initial.getMonth();
                    picker.viewYear = initial.getFullYear();
                    addYearOption(picker, picker.viewYear);
                    render(picker);
                    picker.calendar.hidden = false;
                    picker.field.setAttribute('aria-expanded', 'true');
                    activePicker = picker;
                }

                document.querySelectorAll('input[type="date"]').forEach(function (field, pickerIndex) {
                    var wrapper = document.createElement('div');
                    var calendar = document.createElement('div');
                    var selectors = document.createElement('div');
                    var monthSelect = document.createElement('select');
                    var yearSelect = document.createElement('select');
                    var weekdays = document.createElement('div');
                    var days = document.createElement('div');
                    var currentYear = new Date().getFullYear();
                    var minDate = parseIso(field.min);
                    var maxDate = parseIso(field.max);
                    var minYear = minDate ? minDate.getFullYear() : currentYear - 100;
                    var maxYear = maxDate ? maxDate.getFullYear() : currentYear + 20;
                    var picker = {
                        field: field,
                        calendar: calendar,
                        monthSelect: monthSelect,
                        yearSelect: yearSelect,
                        days: days,
                        viewMonth: 0,
                        viewYear: currentYear
                    };

                    wrapper.className = 'common-date-picker';
                    field.parentNode.insertBefore(wrapper, field);
                    wrapper.appendChild(field);
                    field.setAttribute('aria-haspopup', 'dialog');
                    field.setAttribute('aria-expanded', 'false');
                    field.setAttribute('aria-controls', 'common-calendar-' + pickerIndex);

                    calendar.className = 'common-calendar';
                    calendar.id = 'common-calendar-' + pickerIndex;
                    calendar.setAttribute('role', 'dialog');
                    calendar.setAttribute('aria-label', window.t("common.choose_a_date_a8f6c4"));
                    calendar.hidden = true;
                    selectors.className = 'common-calendar__selectors';
                    monthSelect.setAttribute('aria-label', window.t("dashboard.month_082bc3"));
                    yearSelect.setAttribute('aria-label', window.t("dashboard.year_879e32"));

                    monthNames.forEach(function (month, index) {
                        var option = document.createElement('option');
                        option.value = String(index);
                        option.textContent = month;
                        monthSelect.appendChild(option);
                    });

                    for (var year = maxYear; year >= minYear; year -= 1) {
                        var yearOption = document.createElement('option');
                        yearOption.value = String(year);
                        yearOption.textContent = String(year);
                        yearSelect.appendChild(yearOption);
                    }

                    selectors.append(monthSelect, yearSelect);
                    weekdays.className = 'common-calendar__weekdays';
                    weekdayNames.forEach(function (weekday) {
                        var label = document.createElement('span');
                        label.textContent = weekday;
                        weekdays.appendChild(label);
                    });
                    days.className = 'common-calendar__days';
                    calendar.append(selectors, weekdays, days);
                    wrapper.appendChild(calendar);

                    field.addEventListener('pointerdown', function (event) {
                        if (event.button !== undefined && event.button !== 0) return;
                        event.preventDefault();
                        field.focus();
                        calendar.hidden ? openPicker(picker) : closePicker(picker, false);
                    });

                    field.addEventListener('click', function (event) {
                        event.preventDefault();
                    });

                    field.addEventListener('keydown', function (event) {
                        if (! ['Enter', ' ', 'ArrowDown'].includes(event.key)) return;
                        event.preventDefault();
                        openPicker(picker);
                        var selectedDay = days.querySelector('.is-selected:not(:disabled), .common-calendar__day:not(:disabled)');
                        if (selectedDay) selectedDay.focus();
                    });

                    monthSelect.addEventListener('change', function () {
                        picker.viewMonth = Number(monthSelect.value);
                        render(picker);
                    });

                    yearSelect.addEventListener('change', function () {
                        picker.viewYear = Number(yearSelect.value);
                        render(picker);
                    });

                    days.addEventListener('click', function (event) {
                        var day = event.target.closest('.common-calendar__day');
                        if (! day || day.disabled) return;

                        field.value = day.dataset.date;
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                        closePicker(picker, true);
                    });

                    days.addEventListener('keydown', function (event) {
                        var offsets = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
                        if (! Object.prototype.hasOwnProperty.call(offsets, event.key)) return;

                        var buttons = Array.from(days.querySelectorAll('.common-calendar__day'));
                        var next = buttons[buttons.indexOf(document.activeElement) + offsets[event.key]];
                        if (next && ! next.disabled) {
                            event.preventDefault();
                            next.focus();
                        }
                    });
                });

                document.addEventListener('pointerdown', function (event) {
                    if (activePicker && ! activePicker.calendar.parentElement.contains(event.target)) {
                        closePicker(activePicker, false);
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (activePicker && event.key === 'Escape') {
                        event.preventDefault();
                        closePicker(activePicker, true);
                    }
                });
            })();

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
                title.textContent = form.dataset.confirmTitle || window.t("common.confirm_action_50649c");
                message.textContent = form.dataset.confirmMessage || window.t("common.are_you_sure_you_want_to_continue_8de6e7");
                submitButton.textContent = form.dataset.confirmButton || window.t("common.confirm_04a212");
                cancelButtons.forEach(function (button) {
                    button.hidden = isBlockedAction;
                });

                modal.hidden = false;
                document.body.classList.add('confirm-modal-open');
                submitButton.focus();
            }

            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (! form.matches('form[data-confirm]')) return;
                if (form.dataset.confirmed === 'true') {
                    delete form.dataset.confirmed;
                    return;
                }

                event.preventDefault();
                openModal(form, event.submitter);
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
    @stack('scripts')
</body>
</html>
