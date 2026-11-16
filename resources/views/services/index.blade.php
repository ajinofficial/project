@extends('layouts.admin', ['title' => 'Services'])
@section('content')
@include('services.style')
<header class="service-header"><div><p class="eyebrow">Service management</p><h1>Services</h1><p>Track every request, part, expense and payment in one place.</p></div><a class="primary-link" href="#new-service">Create service order</a></header>
<section class="service-stats">
    <article>Open orders<strong>{{ $open }}</strong></article>
    <article>Completed revenue<strong>&#8377;{{ number_format($revenue, 2) }}</strong></article>
    <article>Completed profit<strong>&#8377;{{ number_format($revenue - $cost, 2) }}</strong></article>
    <article>Outstanding<strong>&#8377;{{ number_format($outstanding, 2) }}</strong></article>
</section>
<section class="service-layout">
<article class="admin-section" id="new-service"><div class="section-title"><h2>New service order</h2></div>
<form method="POST" action="{{ route('services.store') }}" class="service-form" novalidate>@csrf
<div class="service-customer-field"><label>Customer *<select name="customer_id" required><option value="">Select customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} — {{ $customer->mobile }}</option>@endforeach</select>@error('customer_id')<small role="alert">{{ $message }}</small>@enderror</label>
<a class="service-add-customer" href="{{ route('customers.index') }}"><span aria-hidden="true">+</span> Add customer</a></div>
@php
    $serviceCategories = [
        'Mobile' => ['Display replacement', 'Battery replacement', 'Charging port repair', 'Software installation'],
        'Computer' => ['Laptop repair', 'Display replacement', 'Software installation', 'OS installation'],
        'Electronics & appliances' => ['TV repair', 'Appliance servicing', 'Installation'],
        'Automobile' => ['Bike servicing', 'Oil replacement', 'Maintenance'],
        'Electrical' => ['Wiring', 'Electrical installation', 'Maintenance'],
        'Furniture' => ['Furniture assembly', 'Furniture repair'],
        'Clothing' => ['Alteration', 'Stitching'],
        'General' => ['Delivery', 'Installation', 'Maintenance'],
    ];
    $selectedCategory = old('service_category', '');
    if (!$selectedCategory && old('service_type')) {
        foreach ($serviceCategories as $category => $types) {
            if (in_array(old('service_type'), $types, true)) { $selectedCategory = $category; break; }
        }
        $selectedCategory = $selectedCategory ?: 'General';
    }
@endphp
<label>Service category *<select name="service_category" required><option value="">Select category</option>@foreach($serviceCategories as $category => $types)<option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>@endforeach</select></label>
<label>Service type *<select name="service_type" required data-service-types data-selected="{{ old('service_type') }}"><option value="">Select service</option></select>@error('service_type')<small role="alert">{{ $message }}</small>@enderror</label>
<label data-custom-service hidden>Custom service *<input name="custom_service_type" maxlength="255" value="{{ old('custom_service_type') }}" disabled></label>
<label>Request details<textarea name="description" rows="3" maxlength="5000">{{ old('description') }}</textarea>@error('description')<small role="alert">{{ $message }}</small>@enderror</label>
<label>Total customer charge (&#8377;) *<input type="text" name="charge" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" maxlength="11" value="{{ old('charge') }}" required>@error('charge')<small role="alert">{{ $message }}</small>@enderror</label>
<p class="service-help">Include any parts you charge to the customer. Parts and expenses recorded later are your costs.</p>
<label>Due date<input type="date" name="due_date" value="{{ old('due_date') }}">@error('due_date')<small role="alert">{{ $message }}</small>@enderror</label>
<p data-service-create-error role="alert" hidden></p>
<button class="product-save-button" type="submit"><span class="product-save-button__idle">Create service order</span><span class="product-save-button__loading" aria-hidden="true"><i></i>Creating...</span></button>
</form></article>
<article class="admin-section"><div class="section-title"><h2>Service orders</h2></div>
<form class="service-filter" method="GET" action="{{ route('services.index') }}" data-service-filters><input name="search" type="search" placeholder="Search customer or service" value="{{ request('search') }}" aria-label="Search services"><select name="status" aria-label="Status"><option value="">All statuses</option>@foreach(\App\Models\ServiceOrder::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select><button class="primary-link" type="submit">Filter</button><a class="primary-link" href="{{ route('services.index') }}" data-service-clear>Clear</a></form>
<p data-service-error role="alert" hidden>Unable to load services. Please try again.</p>
<div class="service-listing-results" data-service-listing>
<div class="product-listing-loader" data-service-loading aria-live="polite" aria-hidden="true"><span aria-hidden="true"></span><strong>Loading services</strong></div>
<div data-service-results aria-live="polite">
<div class="table-wrap"><table class="admin-table service-orders-table"><thead><tr><th>Order</th><th>Customer</th><th>Mobile</th><th>Service</th><th>Request details</th><th>Status</th><th>Due</th><th>Charge</th><th>Paid</th><th>Balance due</th><th>Costs</th><th>Profit</th></tr></thead><tbody>
@forelse($orders as $order)
<tr>
    <td><a href="{{ route('services.show',$order) }}"><strong>#SV-{{ $order->id }}</strong></a></td>
    <td>{{ $order->customer->name }}</td>
    <td>{{ $order->customer->mobile ?: 'Not set' }}</td>
    <td>{{ $order->service_type }}</td>
    <td>{{ $order->description ?: 'No additional details' }}</td>

    <td><span class="service-status">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span></td>
    <td>{{ $order->due_date?->format('d M Y') ?? 'Not set' }}</td>
    <td>&#8377;{{ number_format($order->charge,2) }}</td>
    <td>&#8377;{{ number_format($order->paid_amount,2) }}</td>
    <td>&#8377;{{ number_format($order->charge - $order->paid_amount,2) }}</td>
    <td>&#8377;{{ number_format($order->total_cost,2) }}</td>
    <td>&#8377;{{ number_format($order->profit,2) }}</td>
</tr>
@empty<tr><td colspan="12">No service orders found. Create an order to get started.</td></tr>@endforelse
</tbody></table></div>
@include('products.partials.pagination', ['paginator' => $orders, 'itemLabel' => 'services'])
</div>
</div>
<p class="service-help">For mobile, computer, electronics, automobile, electrical, furniture, clothing and general retail businesses.</p>
</article></section>
@include('services.script')
@endsection
