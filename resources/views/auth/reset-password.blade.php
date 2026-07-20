<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a new password - Investrivo</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="auth-ui password-page">
    <main class="password-shell">
        <a class="password-brand" href="{{ route('login') }}"><span class="brand-logo"></span><strong>Investrivo</strong></a>
        <section class="password-card" aria-labelledby="password-title">
            <p class="eyebrow">Verified</p>
            <h1 id="password-title">Choose a new password</h1>
            <p>Use at least eight characters and keep it private.</p>
            <form class="register-form" method="POST" action="{{ route('password.update') }}">
                @csrf
                <label><span>New password</span><input type="password" name="password" autocomplete="new-password" required autofocus>@error('password')<small>{{ $message }}</small>@enderror</label>
                <label><span>Confirm new password</span><input type="password" name="password_confirmation" autocomplete="new-password" required></label>
                <button type="submit">Reset password</button>
            </form>
        </section>
    </main>
</body>
</html>
