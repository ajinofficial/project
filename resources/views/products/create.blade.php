@extends('layouts.admin', [
    'title' => __('products.new_product_4ac296'),
    'eyebrow' => __('products.stock_setup_a1c474'),
    'heading' => __('products.new_product_1bc965'),
])

@section('content')
    <section class="admin-section">
        <div class="section-title">
            <div>
                <p class="eyebrow">{{ __('common.item_details_12e853') }}</p>
                <h2>{{ __('products.add_stock_item_f8c591') }}</h2>
            </div>
            <a href="{{ route('products.index') }}">{{ __('products.back_to_products_becfcf') }}</a>
        </div>

        <form class="product-form" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" data-product-save-form>
            @csrf
            @include('products.partials.form')
            <div class="form-actions">
                <a class="ghost-button" href="{{ route('products.index') }}">{{ __('common.cancel_77dfd2') }}</a>
                <button class="product-save-button" type="submit" data-product-save-button>
                    <span class="product-save-button__idle">{{ __('common.save_item_37480e') }}</span>
                    <span class="product-save-button__loading" aria-hidden="true">
                        <i></i>
                        {{ __('common.saving_369c53') }}
                    </span>
                </button>
            </div>
        </form>
    </section>

    @include('products.partials.save-loader-script')
@endsection
