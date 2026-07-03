@extends('layouts.admin', ['title' => 'Vendor Dashboard'])

@section('content')
    <section class="users-page clients-page">
        <header class="users-hero clients-hero">
            <div>
                <p class="eyebrow">Vendor console</p>
                <h1>Vendor Dashboard</h1>
                <p>Monitor client businesses, plan usage, and recent registrations from one place.</p>
            </div>
            <div class="users-plan-badge">
                <span>Total clients</span>
                <strong>{{ number_format($stats['clients']) }}</strong>
            </div>
        </header>

        <section class="users-stat-grid clients-stat-grid">
            <article>
                <span>Client tenants</span>
                <strong>{{ number_format($stats['clients']) }}</strong>
            </article>
            <article>
                <span>Client users</span>
                <strong>{{ number_format($stats['users']) }}</strong>
            </article>
            <article>
                <span>Plans</span>
                <strong>{{ number_format($stats['plans']) }}</strong>
            </article>
            <article>
                <span>New in 30 days</span>
                <strong>{{ number_format($stats['new_clients']) }}</strong>
            </article>
        </section>

        <section class="dashboard-chart-grid">
            <article class="dashboard-panel">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Plans</p>
                        <h2>Client plan usage</h2>
                    </div>
                    <a href="{{ route('clients.index') }}">All clients</a>
                </div>

                @forelse ($planBreakdown as $plan)
                    <div class="category-progress-row">
                        <div>
                            <strong>{{ ucfirst($plan->name) }}</strong>
                            <span>{{ number_format($plan->clients_count) }} clients</span>
                        </div>
                        @php $width = min(100, max(6, round(($plan->clients_count / max(1, $stats['clients'])) * 100))); @endphp
                        <i><span style="width: {{ $width }}%"></span></i>
                    </div>
                @empty
                    <div class="empty-state tight-empty">No plans configured.</div>
                @endforelse
            </article>

            <article class="dashboard-panel">
                <div class="section-title">
                    <div>
                        <p class="eyebrow">Recent</p>
                        <h2>Latest clients</h2>
                    </div>
                    <a href="{{ route('clients.index') }}">Manage</a>
                </div>

                @forelse ($recentClients as $client)
                    <div class="stock-row">
                        <div>
                            <strong>{{ $client->business_name }}</strong>
                            <span>{{ $client->owner_name }} &middot; {{ ucfirst($client->plan?->name ?? 'No plan') }} &middot; {{ number_format($client->users_count) }} users</span>
                        </div>
                        <span>{{ $client->created_at?->format('d M Y') }}</span>
                    </div>
                @empty
                    <div class="empty-state tight-empty">No client businesses yet.</div>
                @endforelse
            </article>
        </section>
    </section>
@endsection
