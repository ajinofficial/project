<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>InvestRivo</title>
    @include('partials.app-base-url')
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="auth-ui">
    <main class="register-shell">
        <section class="brand-panel">
            <nav class="topbar">
                <a class="brand-mark" href="{{ route('login') }}"><span class="brand-logo" role="img" aria-label="InvestRivo logo"></span> InvestRivo</a>
            </nav>
            <div class="brand-copy">
                <p class="eyebrow">Inventory workspace</p>
                <h1>Manage products, billing, purchases, and clients.</h1>
            </div>
        </section>
        <section class="form-panel">
            <div class="form-card">
                <h2>Open InvestRivo</h2>
                <p class="auth-switch">Use your business account to continue.</p>
                <div class="form-actions">
                    <a class="submit-button" href="{{ route('login') }}">Log in</a>
                    <a class="ghost-button" href="{{ route('register') }}">Register</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
