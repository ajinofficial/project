<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - StockPilot Inventory</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
</head>
<body class="login-page auth-ui">
    <main class="inventory-login-shell">
        <section class="login-visual-panel" aria-label="Inventory workspace summary">
            <nav class="login-brand">
                <span class="brand-icon">SP</span>
                <strong>StockPilot</strong>
            </nav>

            <div class="login-hero-copy">
                <p class="eyebrow">Shop inventory SaaS</p>
                <h1>Control stock, catalog status, and reorder work from one desk.</h1>
                <p>Built for store teams that need fast SKU lookup, low-stock visibility, and reliable daily inventory operations.</p>
            </div>

            <div class="login-stat-grid">
                <div>
                    <strong>Live</strong>
                    <span>Stock movement</span>
                </div>
                <div>
                    <strong>Low</strong>
                    <span>Reorder alerts</span>
                </div>
                <div>
                    <strong>SKU</strong>
                    <span>Catalog control</span>
                </div>
            </div>
        </section>

        <section class="login-form-panel" aria-labelledby="login-title">
            <div class="login-card">
                <span class="brand-icon">SP</span>
                <p class="eyebrow">Secure access</p>
                <h1 id="login-title">Log in to inventory</h1>
                <p class="login-intro">Enter your staff account details to open the shop operations dashboard.</p>
                <p class="auth-switch">New business? <a href="{{ route('register') }}">Create an account</a></p>

                @if ($errors->any())
                    <div class="error-summary" role="alert">
                        <strong>Check your login</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form class="register-form" method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <label>
                        <span>Email address</span>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="staff@shop.com" required>
                    </label>

                    <label>
                        <span>Password</span>
                        <input type="password" name="password" autocomplete="current-password" placeholder="Enter password" required>
                    </label>

                    <label class="check-row">
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep this device signed in</span>
                    </label>

                    <button type="submit">Open dashboard</button>
                </form>
                <div class="auth-footnote">
                    <span>Tenant isolated</span>
                    <span>Role based access</span>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
