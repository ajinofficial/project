@extends('layouts.admin', [
    'title' => 'Edit Inventory Item',
    'eyebrow' => 'Stock update',
    'heading' => 'Edit inventory item',
])

@section('content')
    <section class="admin-section">
        <div class="section-title">
            <div>
                <p class="eyebrow">Item details</p>
                <h2>{{ $product->name }}</h2>
            </div>
            <a href="{{ route('products.index') }}">Back to inventory</a>
        </div>

        <form class="product-form" method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('products.partials.form')
            <div class="form-actions">
                <a class="ghost-button" href="{{ route('products.index') }}">Cancel</a>
                <button type="submit">Update item</button>
            </div>
        </form>
    </section>
@endsection
