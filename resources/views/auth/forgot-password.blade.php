<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password - Investrivo</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="auth-ui password-page">
    <main class="password-shell">
        <a class="password-brand" href="{{ route('login') }}"><span class="brand-logo"></span><strong>Investrivo</strong></a>
        <section class="password-card" aria-labelledby="password-title">
            <p class="eyebrow">Account recovery</p>
            <h1 id="password-title">Forgot your password?</h1>
            <p>Enter the email address linked to your account. We’ll send a six-digit verification code.</p>
            @if (session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
            <form class="register-form" method="POST" action="{{ route('password.email') }}">
                @csrf
                <label><span>Email address</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="staff@shop.com" required autofocus>@error('email')<small>{{ $message }}</small>@enderror</label>
                <button type="submit">Send verification code</button>
            </form>
            <p class="auth-switch"><a href="{{ route('login') }}">Back to login</a></p>
        </section>
    </main>
</body>
</html>
