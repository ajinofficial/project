@extends('layouts.admin', [
    'title' => 'New Inventory Item',
    'eyebrow' => 'Stock setup',
    'heading' => 'New inventory item',
])

@section('content')
    <section class="admin-section">
        <div class="section-title">
            <div>
                <p class="eyebrow">Item details</p>
                <h2>Add stock item</h2>
            </div>
            <a href="{{ route('products.index') }}">Back to inventory</a>
        </div>

        <form class="product-form" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf
            @include('products.partials.form')
            <div class="form-actions">
                <a class="ghost-button" href="{{ route('products.index') }}">Cancel</a>
                <button type="submit">Save item</button>
            </div>
        </form>
    </section>
@endsection
