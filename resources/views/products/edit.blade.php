@extends('layouts.admin', [
    'title' => __('products.edit_product_f8f4e0'),
    'eyebrow' => __('products.stock_update_4fbea1'),
    'heading' => __('products.edit_product_fbeabd'),
])

@section('content')
    <section class="admin-section">
        <div class="section-title">
            <div>
                <p class="eyebrow">{{ __('common.item_details_12e853') }}</p>
                <h2>{{ $product->localized('name') }}</h2>
            </div>
            <a href="{{ route('products.index') }}">{{ __('products.back_to_products_becfcf') }}</a>
        </div>

        <form class="product-form" method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" data-product-save-form>
            @csrf
            @method('PUT')
            @include('products.partials.form')
            <div class="form-actions">
                <a class="ghost-button" href="{{ route('products.index') }}">{{ __('common.cancel_77dfd2') }}</a>
                <button class="product-save-button" type="submit" data-product-save-button>
                    <span class="product-save-button__idle">{{ __('common.update_item_dcfbc4') }}</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        {{ __('common.updating_ec6952') }}
                    </span>
                </button>
            </div>
        </form>
    </section>

    @include('products.partials.save-loader-script')
@endsection
