<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · {{ __('common.errors.'.$code.'.title') }} - Zyloah</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ rawurlencode(config('app.asset_version')) }}">
    @include('partials.localization')
</head>
<body class="login-page auth-ui">
    <header class="auth-language-header">@include('partials.language-selector')</header>
    <main class="login-card" style="max-width:600px;margin:60px auto;padding:32px;">
        <p class="eyebrow">{{ $code }}</p>
        <h1>{{ __('common.errors.'.$code.'.title') }}</h1>
        <p>{{ __('common.errors.'.$code.'.description') }}</p>
        <a class="primary-link" href="{{ url('/') }}">{{ __('common.home') }}</a>
    </main>
</body>
</html>
