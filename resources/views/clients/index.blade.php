@extends('layouts.admin', ['title' => 'Clients'])

@section('content')
    <section class="users-page clients-page">
        <header class="users-hero clients-hero">
            <div>
                <p class="eyebrow">Vendor console</p>
                <h1>Clients</h1>
                <p>Manage registered client businesses from one place. Use filters to find a business, owner, email, mobile number, or plan.</p>
            </div>
            <div class="users-plan-badge">
                <span>Client tenants</span>
                <strong>{{ number_format($stats['clients']) }}</strong>
            </div>
        </header>

        <section class="users-stat-grid clients-stat-grid">
            <article>
                <span>Total clients</span>
                <strong>{{ number_format($stats['clients']) }}</strong>
            </article>
            <article>
                <span>Client users</span>
                <strong>{{ number_format($stats['users']) }}</strong>
            </article>
            <article>
                <span>Starter plan</span>
                <strong>{{ number_format($stats['starter']) }}</strong>
            </article>
            <article>
                <span>Premium plan</span>
                <strong>{{ number_format($stats['premium']) }}</strong>
            </article>
        </section>

        <article class="users-card clients-card">
            <div class="section-title">
                <div>
                    <p class="eyebrow">Client listing</p>
                    <h2>Businesses</h2>
                </div>
            </div>

            <div class="product-toolbar">
                <form class="product-filter-form clients-filter-form" method="GET" action="{{ route('clients.index') }}" data-clients-filter-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search business, owner, email, mobile" data-clients-filter>
                    <select name="plan_id" data-clients-filter>
                        <option value="">All plans</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) request('plan_id') === (string) $plan->id)>{{ ucfirst($plan->name) }}</option>
                        @endforeach
                    </select>
                    <select name="per_page" aria-label="Clients per page" data-clients-filter>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <a class="product-clear-filter" href="{{ route('clients.index') }}">Clear</a>
                </form>
            </div>

            <div class="table-wrap">
                <table class="admin-table clients-table">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Owner</th>
                            <th>Contact</th>
                            <th>Plan</th>
                            <th>Users</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr>
                                <td>
                                    <div class="users-person">
                                        <span>{{ strtoupper(substr($client->business_name, 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ $client->business_name }}</strong>
                                            <small>{{ $client->business_category_label }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $client->owner_name }}</td>
                                <td>
                                    <div class="clients-contact">
                                        <strong>{{ $client->email }}</strong>
                                        <small>{{ $client->mobile ?: '-' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="clients-plan-chip">{{ ucfirst($client->plan?->name ?? 'No plan') }}</span>
                                </td>
                                <td>{{ number_format($client->users_count) }}</td>
                                <td>{{ $client->created_at?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">{{ $hasActiveFilters ? 'No clients match the current filters.' : 'No client businesses yet.' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('products.partials.pagination', ['paginator' => $clients, 'itemLabel' => 'clients'])
        </article>
    </section>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-clients-filter-form]').forEach(function (form) {
                    function submitFilters() {
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                            return;
                        }

                        form.submit();
                    }

                    form.querySelectorAll('[data-clients-filter]').forEach(function (field) {
                        field.addEventListener('change', submitFilters);
                        field.addEventListener('search', submitFilters);
                    });

                    form.addEventListener('submit', function () {
                        form.querySelectorAll('input, select').forEach(function (field) {
                            if (field.value === '') {
                                field.disabled = true;
                            }
                        });
                    });
                });
            });
        </script>
    @endonce
@endsection
