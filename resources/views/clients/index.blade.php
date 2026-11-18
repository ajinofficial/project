@extends('layouts.admin', ['title' => __('common.clients_28e22f')])

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
                <p class="eyebrow">{{ __('common.vendor_console_e4cec4') }}</p>
                <h1>{{ __('common.clients_28e22f') }}</h1>
                <p>{{ __('auth.manage_registered_client_businesses_from_one_place_use_filters_to_find_2cfa78') }}</p>
            </div>
            <div class="users-plan-badge">
                <span>{{ __('settings.client_tenants_73cd85') }}</span>
                <strong>{{ number_format($stats['clients']) }}</strong>
            </div>
        </header>

        <section class="users-stat-grid clients-stat-grid">
            <article>
                <span>{{ __('common.total_clients_9c969e') }}</span>
                <strong>{{ number_format($stats['clients']) }}</strong>
            </article>
            <article>
                <span>{{ __('common.client_users_1db029') }}</span>
                <strong>{{ number_format($stats['users']) }}</strong>
            </article>
            <article>
                <span>{{ __('common.free_trials_57fee4') }}</span>
                <strong>{{ number_format($stats['free_trial']) }}</strong>
            </article>
            <article>
                <span>{{ __('settings.starter_plan_49e34c') }}</span>
                <strong>{{ number_format($stats['starter']) }}</strong>
            </article>
            <article>
                <span>{{ __('settings.premium_plan_834bae') }}</span>
                <strong>{{ number_format($stats['premium']) }}</strong>
            </article>
        </section>

        <article class="users-card clients-card">
            <div class="section-title">
                <div>
                    <p class="eyebrow">{{ __('common.client_listing_aa6404') }}</p>
                    <h2>{{ __('common.businesses_f6e931') }}</h2>
                </div>
            </div>

            <div class="product-toolbar">
                <form class="product-filter-form clients-filter-form" method="GET" action="{{ route('clients.index') }}" data-clients-filter-form>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('common.search_business_owner_email_mobile_626a56') }}" data-clients-filter>
                    <select name="plan_id" data-clients-filter>
                        <option value="">{{ __('settings.all_plans_34a5ad') }}</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) request('plan_id') === (string) $plan->id)>{{ __(ucfirst($plan->name)) }}</option>
                        @endforeach
                    </select>
                    <select name="category" data-clients-filter>
                        <option value="">{{ __('common.all_categories_060be0') }}</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected((string) request('category') === (string) $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    <select name="per_page" aria-label="{{ __('common.clients_per_page_9668ef') }}" data-clients-filter>
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ __('common.per_page', ['count' => $option]) }}</option>
                        @endforeach
                    </select>
                    <button type="submit">{{ __('common.apply_cfea41') }}</button>
                    <a class="product-clear-filter" href="{{ route('clients.index') }}">{{ __('common.clear_719ea3') }}</a>
                </form>
            </div>

            @if ($hasActiveFilters)
                <div class="clients-filter-chips" aria-label="{{ __('common.active_client_filters_032538') }}">
                    @if (request('search'))
                        <span>{{ __('common.search_filter', ['value' => request('search')]) }}</span>
                    @endif
                    @if ($selectedPlan)
                        <span>{{ __('common.plan_filter', ['value' => __(ucfirst($selectedPlan->name))]) }}</span>
                    @endif
                    @if ($selectedCategory)
                        <span>{{ __('common.category_filter', ['value' => __($selectedCategory)]) }}</span>
                    @endif
                </div>
            @endif

            <div class="table-wrap">
                <table class="admin-table clients-table">
                    <thead>
                        <tr>
                            <th>{{ __('common.business_d6663d') }}</th>
                            <th>{{ __('common.owner_89ff31') }}</th>
                            <th>{{ __('common.contact_b37456') }}</th>
                            <th>{{ __('settings.plan_ae2f98') }}</th>
                            <th>{{ __('common.users_57f2b1') }}</th>
                            <th>{{ __('common.created_accf40') }}</th>
                            <th>{{ __('common.action_97c89a') }}</th>
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
                                            <small>{{ __($client->business_category_label) }}</small>
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
                                    <span class="clients-plan-chip">{{ ucfirst($client->plan?->name ?? __('settings.no_plan_e71f35')) }}</span>
                                </td>
                                <td>{{ number_format($client->users_count) }}</td>
                                <td>{{ $client->created_at?->translatedFormat('d M Y') }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="clients-view-button"
                                        data-client-view
                                        data-business="{{ $client->business_name }}"
                                        data-owner="{{ $client->owner_name }}"
                                        data-email="{{ $client->email }}"
                                        data-mobile="{{ $client->mobile ?: '-' }}"
                                        data-plan="{{ ucfirst($client->plan?->name ?? __('settings.no_plan_e71f35')) }}"
                                        data-category="{{ __($client->business_category_label) }}"
                                        data-users="{{ number_format($client->users_count) }}"
                                        data-address="{{ $client->store_address ?: '-' }}"
                                        data-created="{{ $client->created_at?->translatedFormat('d M Y') }}"
                                    >{{ __('common.view_69bd4e') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="clients-empty">
                                        <strong>{{ $hasActiveFilters ? __('common.no_clients_match_the_current_filters_4e0e5b') : __('common.no_client_businesses_yet_cda195') }}</strong>
                                        @if ($hasActiveFilters)
                                            <a href="{{ route('clients.index') }}">{{ __('common.clear_filters_412226') }}</a>
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
        <aside class="clients-drawer" data-client-drawer aria-hidden="true" aria-label="{{ __('common.client_details_06f85f') }}">
            <div class="clients-drawer-head">
                <div>
                    <span data-drawer-category>{{ __('common.client_1bdd79') }}</span>
                    <h2 data-drawer-business>{{ __('common.client_details_06f85f') }}</h2>
                </div>
                <button type="button" data-client-drawer-close aria-label="{{ __('common.close_client_details_babea6') }}">x</button>
            </div>
            <dl class="clients-drawer-list">
                <div><dt>{{ __('common.owner_89ff31') }}</dt><dd data-drawer-owner></dd></div>
                <div><dt>{{ __('common.email_84add5') }}</dt><dd data-drawer-email></dd></div>
                <div><dt>{{ __('common.mobile_b1d702') }}</dt><dd data-drawer-mobile></dd></div>
                <div><dt>{{ __('settings.plan_ae2f98') }}</dt><dd data-drawer-plan></dd></div>
                <div><dt>{{ __('common.users_57f2b1') }}</dt><dd data-drawer-users></dd></div>
                <div><dt>{{ __('common.created_accf40') }}</dt><dd data-drawer-created></dd></div>
                <div class="clients-drawer-wide"><dt>{{ __('common.store_address_934589') }}</dt><dd data-drawer-address></dd></div>
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
