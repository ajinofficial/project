@extends('layouts.admin', ['title' => __('orders.services_5cbd58')])
@section('content')
@include('services.style')
<header class="service-header"><div><p class="eyebrow">{{ __('orders.service_management_1f131d') }}</p><h1>{{ __('orders.services_5cbd58') }}</h1><p>{{ __('orders.track_every_request_part_expense_and_payment_in_one_place_8aa07e') }}</p></div><a class="primary-link" href="#new-service">{{ __('orders.create_service_order_a2d5c7') }}</a></header>
<section class="service-stats">
    <article>{{ __('orders.open_orders_29706c') }}<strong>{{ $open }}</strong></article>
    <article>{{ __('dashboard.completed_revenue_a01c33') }}<strong>&#8377;{{ number_format($revenue, 2) }}</strong></article>
    <article>{{ __('products.completed_profit_26bdf7') }}<strong>&#8377;{{ number_format($revenue - $cost, 2) }}</strong></article>
    <article>{{ __('common.outstanding_f8ee57') }}<strong>&#8377;{{ number_format($outstanding, 2) }}</strong></article>
</section>
<section class="service-layout">
<article class="admin-section" id="new-service"><div class="section-title"><h2>{{ __('orders.new_service_order_445d96') }}</h2></div>
<form method="POST" action="{{ route('services.store') }}" class="service-form" novalidate>@csrf
<div class="service-customer-field"><label>{{ __('orders.customer_6e2808') }}<select name="customer_id" required><option value="">{{ __('orders.select_customer_681717') }}</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} — {{ $customer->mobile }}</option>@endforeach</select>@error('customer_id')<small role="alert">{{ $message }}</small>@enderror</label>
<a class="service-add-customer" href="{{ route('customers.index') }}"><span aria-hidden="true">+</span> {{ __('orders.add_customer_2e23cb') }}</a></div>
@php
    $serviceCategories = [
        'Mobile' => ["Display replacement", "Battery replacement", "Charging port repair", "Software installation"],
        'Computer' => ["Laptop repair", "Display replacement", "Software installation", "OS installation"],
        'Electronics & appliances' => ["TV repair", "Appliance servicing", "Installation"],
        'Automobile' => ["Bike servicing", "Oil replacement", "Maintenance"],
        'Electrical' => ["Wiring", "Electrical installation", "Maintenance"],
        'Furniture' => ["Furniture assembly", "Furniture repair"],
        'Clothing' => ["Alteration", "Stitching"],
        'General' => ["Delivery", "Installation", "Maintenance"],
    ];
    $selectedCategory = old('service_category', '');
    if (!$selectedCategory && old('service_type')) {
        foreach ($serviceCategories as $category => $types) {
            if (in_array(old('service_type'), $types, true)) { $selectedCategory = $category; break; }
        }
        $selectedCategory = $selectedCategory ?: 'General';
    }
@endphp
<label>{{ __('products.service_category_7abc93') }}<select name="service_category" required><option value="">{{ __('products.select_category_a2a5ca') }}</option>@foreach($serviceCategories as $category => $types)<option value="{{ $category }}" @selected($selectedCategory === $category)>{{ __($category) }}</option>@endforeach</select></label>
<label>{{ __('orders.service_type_4acfd2') }}<select name="service_type" required data-service-types data-selected="{{ old('service_type') }}"><option value="">{{ __('orders.select_service_f216b2') }}</option></select>@error('service_type')<small role="alert">{{ $message }}</small>@enderror</label>
<label data-custom-service hidden>{{ __('orders.custom_service_77ed02') }}<input name="custom_service_type" maxlength="255" value="{{ old('custom_service_type') }}" disabled></label>
<label>{{ __('common.request_details_bf5023') }}<textarea name="description" rows="3" maxlength="5000">{{ old('description') }}</textarea>@error('description')<small role="alert">{{ $message }}</small>@enderror</label>
<label>Total customer charge (&#8377;) *<input type="text" name="charge" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" maxlength="11" value="{{ old('charge') }}" required>@error('charge')<small role="alert">{{ $message }}</small>@enderror</label>
<p class="service-help">{{ __('orders.include_any_parts_you_charge_to_the_customer_parts_and_expenses_record_bdd6f5') }}</p>
<label>{{ __('common.due_date_4c1aee') }}<input type="date" name="due_date" value="{{ old('due_date') }}">@error('due_date')<small role="alert">{{ $message }}</small>@enderror</label>
<p data-service-create-error role="alert" hidden></p>
<button class="product-save-button" type="submit"><span class="product-save-button__idle">{{ __('orders.create_service_order_a2d5c7') }}</span><span class="product-save-button__loading" aria-hidden="true"><i></i>{{ __('common.creating_28ea76') }}</span></button>
</form></article>
<article class="admin-section"><div class="section-title"><h2>{{ __('orders.service_orders_b616e6') }}</h2></div>
<form class="service-filter" method="GET" action="{{ route('services.index') }}" data-service-filters><input name="search" type="search" placeholder="{{ __('orders.search_customer_or_service_3c8763') }}" value="{{ request('search') }}" aria-label="{{ __('orders.search_services_d1e0a4') }}"><select name="status" aria-label="{{ __('common.status_bae7d5') }}"><option value="">{{ __('common.all_statuses_640517') }}</option>@foreach(\App\Models\ServiceOrder::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst(str_replace('_',' ',$status))) }}</option>@endforeach</select><button class="primary-link" type="submit">{{ __('common.filter_d7decf') }}</button><a class="primary-link" href="{{ route('services.index') }}" data-service-clear>{{ __('common.clear_719ea3') }}</a></form>
<p data-service-error role="alert" hidden>{{ __('validation.unable_to_load_services_please_try_again_bc8b6e') }}</p>
<div class="service-listing-results" data-service-listing>
<div class="product-listing-loader" data-service-loading aria-live="polite" aria-hidden="true"><span aria-hidden="true"></span><strong>{{ __('orders.loading_services_362ae8') }}</strong></div>
<div data-service-results aria-live="polite">
<div class="table-wrap"><table class="admin-table service-orders-table"><thead><tr><th>{{ __('orders.order_1d7577') }}</th><th>{{ __('orders.customer_0e8574') }}</th><th>{{ __('common.mobile_b1d702') }}</th><th>{{ __('orders.service_329cb8') }}</th><th>{{ __('common.request_details_bf5023') }}</th><th>{{ __('common.status_bae7d5') }}</th><th>{{ __('common.due_145caf') }}</th><th>{{ __('common.charge_d4e1ae') }}</th><th>{{ __('common.paid_dc9d45') }}</th><th>{{ __('common.balance_due_bebeab') }}</th><th>{{ __('common.costs_799b3e') }}</th><th>{{ __('products.profit_8544a4') }}</th></tr></thead><tbody>
@forelse($orders as $order)
<tr>
    <td><a href="{{ route('services.show',$order) }}"><strong>#SV-{{ $order->id }}</strong></a></td>
    <td>{{ $order->customer->name }}</td>
    <td>{{ $order->customer->mobile ?: __('common.not_set_93039e') }}</td>
    <td>{{ $order->service_type }}</td>
    <td>{{ $order->description ?: __('common.no_additional_details_72c3cf') }}</td>

    <td><span class="service-status">{{ __(ucfirst(str_replace('_',' ',$order->status))) }}</span></td>
    <td>{{ $order->due_date?->translatedFormat('d M Y') ?? __('common.not_set_93039e') }}</td>
    <td>&#8377;{{ number_format($order->charge,2) }}</td>
    <td>&#8377;{{ number_format($order->paid_amount,2) }}</td>
    <td>&#8377;{{ number_format($order->charge - $order->paid_amount,2) }}</td>
    <td>&#8377;{{ number_format($order->total_cost,2) }}</td>
    <td>&#8377;{{ number_format($order->profit,2) }}</td>
</tr>
@empty<tr><td colspan="12">{{ __('orders.no_service_orders_found_create_an_order_to_get_started_53d49d') }}</td></tr>@endforelse
</tbody></table></div>
@include('products.partials.pagination', ['paginator' => $orders, 'itemLabel' => 'services'])
</div>
</div>
<p class="service-help">{{ __('common.for_mobile_computer_electronics_automobile_electrical_furniture_clothi_0a3baf') }}</p>
</article></section>
@include('services.script')
@endsection
