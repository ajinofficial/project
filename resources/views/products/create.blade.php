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

        <form class="product-form" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" data-product-save-form>
            @csrf
            @include('products.partials.form')
            <div class="form-actions">
                <a class="ghost-button" href="{{ route('products.index') }}">Cancel</a>
                <button class="product-save-button" type="submit" data-product-save-button>
                    <span class="product-save-button__idle">Save item</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        Saving
                    </span>
                </button>
            </div>
        </form>
    </section>

    @include('products.partials.save-loader-script')
@endsection
