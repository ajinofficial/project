@extends('layouts.admin', ['title' => 'Clients'])

@section('content')
    @php
        $selectedPlan = $plans->firstWhere('id', (int) request('plan_id'));
        $selectedCategory = request('category') !== null && request('category') !== ''
            ? ($categories[(int) request('category')] ?? null)
            : null;
    @endphp

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
                <span>Free trials</span>
                <strong>{{ number_format($stats['free_trial']) }}</strong>
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
                    <select name="category" data-clients-filter>
                        <option value="">All categories</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected((string) request('category') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="per_page" aria-label="Clients per page" data-clients-filter>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                    <button type="submit">Apply</button>
                    <a class="product-clear-filter" href="{{ route('clients.index') }}">Clear</a>
                </form>
            </div>

            @if ($hasActiveFilters)
                <div class="clients-filter-chips" aria-label="Active client filters">
                    @if (request('search'))
                        <span>Search: {{ request('search') }}</span>
                    @endif
                    @if ($selectedPlan)
                        <span>Plan: {{ ucfirst($selectedPlan->name) }}</span>
                    @endif
                    @if ($selectedCategory)
                        <span>Category: {{ $selectedCategory }}</span>
                    @endif
                </div>
            @endif

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
                            <th>Action</th>
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
                                <td>
                                    <button
                                        type="button"
                                        class="clients-view-button"
                                        data-client-view
                                        data-business="{{ $client->business_name }}"
                                        data-owner="{{ $client->owner_name }}"
                                        data-email="{{ $client->email }}"
                                        data-mobile="{{ $client->mobile ?: '-' }}"
                                        data-plan="{{ ucfirst($client->plan?->name ?? 'No plan') }}"
                                        data-category="{{ $client->business_category_label }}"
                                        data-users="{{ number_format($client->users_count) }}"
                                        data-address="{{ $client->store_address ?: '-' }}"
                                        data-created="{{ $client->created_at?->format('d M Y') }}"
                                    >View</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="clients-empty">
                                        <strong>{{ $hasActiveFilters ? 'No clients match the current filters.' : 'No client businesses yet.' }}</strong>
                                        @if ($hasActiveFilters)
                                            <a href="{{ route('clients.index') }}">Clear filters</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('products.partials.pagination', ['paginator' => $clients, 'itemLabel' => 'clients'])
        </article>

        <div class="clients-drawer-backdrop" data-client-drawer-close hidden></div>
        <aside class="clients-drawer" data-client-drawer aria-hidden="true" aria-label="Client details">
            <div class="clients-drawer-head">
                <div>
                    <span data-drawer-category>Client</span>
                    <h2 data-drawer-business>Client details</h2>
                </div>
                <button type="button" data-client-drawer-close aria-label="Close client details">x</button>
            </div>
            <dl class="clients-drawer-list">
                <div><dt>Owner</dt><dd data-drawer-owner></dd></div>
                <div><dt>Email</dt><dd data-drawer-email></dd></div>
                <div><dt>Mobile</dt><dd data-drawer-mobile></dd></div>
                <div><dt>Plan</dt><dd data-drawer-plan></dd></div>
                <div><dt>Users</dt><dd data-drawer-users></dd></div>
                <div><dt>Created</dt><dd data-drawer-created></dd></div>
                <div class="clients-drawer-wide"><dt>Store address</dt><dd data-drawer-address></dd></div>
            </dl>
        </aside>
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

                const drawer = document.querySelector('[data-client-drawer]');
                const backdrop = document.querySelector('.clients-drawer-backdrop');

                function setDrawerValue(name, value) {
                    const target = drawer?.querySelector(`[data-drawer-${name}]`);

                    if (target) {
                        target.textContent = value || '-';
                    }
                }

                function closeDrawer() {
                    drawer?.classList.remove('is-open');
                    drawer?.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('clients-drawer-open');

                    if (backdrop) {
                        backdrop.hidden = true;
                    }
                }

                document.querySelectorAll('[data-client-view]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        if (!drawer || !backdrop) {
                            return;
                        }

                        ['business', 'owner', 'email', 'mobile', 'plan', 'category', 'users', 'address', 'created'].forEach(function (name) {
                            setDrawerValue(name, button.dataset[name]);
                        });

                        backdrop.hidden = false;
                        drawer.classList.add('is-open');
                        drawer.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('clients-drawer-open');
                    });
                });

                document.querySelectorAll('[data-client-drawer-close]').forEach(function (button) {
                    button.addEventListener('click', closeDrawer);
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeDrawer();
                    }
                });
            });
        </script>
    @endonce
@endsection
