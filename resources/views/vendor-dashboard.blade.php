@extends('layouts.admin', ['title' => __('dashboard.vendor_dashboard_084ca1')])

@section('content')
    <section class="users-page clients-page">
        <header class="users-hero clients-hero">
            <div>
                <p class="eyebrow">{{ __('common.vendor_console_e4cec4') }}</p>
                <h1>{{ __('dashboard.vendor_dashboard_084ca1') }}</h1>
                <p>{{ __('settings.monitor_client_businesses_plan_usage_and_recent_registrations_from_one_36ea7d') }}</p>
            </div>
            <div class="users-plan-badge">
                <span>{{ __('common.total_clients_9c969e') }}</span>
                <strong>{{ number_format($stats['clients']) }}</strong>
            </div>
        </header>

        <section class="users-stat-grid clients-stat-grid">
            <article>
                <span>{{ __('settings.client_tenants_73cd85') }}</span>
                <strong>{{ number_format($stats['clients']) }}</strong>
            </article>
            <article>
                <span>{{ __('common.client_users_1db029') }}</span>
                <strong>{{ number_format($stats['users']) }}</strong>
            </article>
            <article>
                <span>{{ __('settings.plans_cf2e5f') }}</span>
                <strong>{{ number_format($stats['plans']) }}</strong>
            </article>
            <article>
                <span>{{ __('common.new_in_30_days_abaf59') }}</span>
                <strong>{{ number_format($stats['new_clients']) }}</strong>
            </article>
        </section>

        <section class="dashboard-chart-grid">
            <article class="dashboard-panel">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('settings.plans_cf2e5f') }}</p>
                        <h2>{{ __('settings.client_plan_usage_f497db') }}</h2>
                    </div>
                    <a href="{{ route('clients.index') }}">{{ __('common.all_clients_3a98d0') }}</a>
                </div>

                @forelse ($planBreakdown as $plan)
                    <div class="category-progress-row">
                        <div>
                            <strong>{{ __(ucfirst($plan->name)) }}</strong>
                            <span>{{ __('common.clients_count', ['count' => number_format($plan->clients_count)]) }}</span>
                        </div>
                        @php $width = min(100, max(6, round(($plan->clients_count / max(1, $stats['clients'])) * 100))); @endphp
                        <i><span style="width: {{ $width }}%"></span></i>
                    </div>
                @empty
                    <div class="empty-state tight-empty">{{ __('settings.no_plans_configured_9a5d5a') }}</div>
                @endforelse
            </article>

            <article class="dashboard-panel">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">{{ __('common.recent_76eec7') }}</p>
                        <h2>{{ __('common.latest_clients_584342') }}</h2>
                    </div>
                    <a href="{{ route('clients.index') }}">{{ __('common.manage_bf58d1') }}</a>
                </div>

                @forelse ($recentClients as $client)
                    <div class="stock-row">
                        <div>
                            <strong>{{ $client->business_name }}</strong>
                            <span>{{ $client->owner_name }} &middot; {{ ucfirst($client->plan?->name ?? __('settings.no_plan_e71f35')) }} &middot; {{ number_format($client->users_count) }} users</span>
                        </div>
                        <span>{{ $client->created_at?->translatedFormat('d M Y') }}</span>
                    </div>
                @empty
                    <div class="empty-state tight-empty">{{ __('common.no_client_businesses_yet_cda195') }}</div>
                @endforelse
            </article>
        </section>
    </section>
@endsection
