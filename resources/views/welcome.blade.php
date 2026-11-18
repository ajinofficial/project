<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zyloah</title>
    <link rel="icon" type="image/png" href="{{ asset('images/Zyloah-logo.png') }}">
    @include('partials.app-base-url')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
    @include('partials.localization')
</head>
<body class="auth-ui">
    <header class="auth-language-header">@include('partials.language-selector')</header>
    <main class="register-shell">
        <section class="brand-panel">
            <nav class="topbar">
                <a class="brand-mark" href="{{ route('login') }}"><span class="brand-logo" role="img" aria-label="{{ __('common.zyloah_logo_216929') }}"></span> Zyloah</a>
            </nav>
            <div class="brand-copy">
                <p class="eyebrow">{{ __('products.inventory_workspace_84bc6c') }}</p>
                <h1>{{ __('products.manage_products_billing_purchases_and_clients_634373') }}</h1>
            </div>
        </section>
        <section class="form-panel">
            <div class="form-card">
                <h2>{{ __('common.open_zyloah_3d8920') }}</h2>
                <p class="auth-switch">{{ __('common.use_your_business_account_to_continue_915626') }}</p>
                <div class="form-actions">
                    <a class="submit-button" href="{{ route('login') }}">{{ __('auth.log_in_f7c400') }}</a>
                    <a class="ghost-button" href="{{ route('register') }}">{{ __('auth.register_d67299') }}</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
