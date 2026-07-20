<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify code - Investrivo</title>
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="auth-ui password-page">
    <main class="password-shell">
        <a class="password-brand" href="{{ route('login') }}"><span class="brand-logo"></span><strong>Investrivo</strong></a>
        <section class="password-card" aria-labelledby="password-title">
            <p class="eyebrow">Account recovery</p>
            <h1 id="password-title">Check your email</h1>
            <p>Enter the six-digit code sent to <strong>{{ $email }}</strong>. It expires in 10 minutes.</p>
            @if (session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
            <form class="register-form" method="POST" action="{{ route('password.otp.verify') }}">
                @csrf
                <label><span>Verification code</span><input class="otp-input" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus>@error('otp')<small>{{ $message }}</small>@enderror</label>
                <button type="submit">Verify code</button>
            </form>
            <p class="auth-switch">Didn’t get a code? <a href="{{ route('password.request') }}">Send another</a></p>
        </section>
    </main>
</body>
</html>
