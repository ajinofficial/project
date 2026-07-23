<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Access denied — Investrivo</title>
    @include('partials.app-base-url')
    <style>
        :root {
            color-scheme: light;
            --ink: #17201a;
            --muted: #687268;
            --line: #dfe6db;
            --surface: #f5f7f0;
            --primary: #146c43;
            --primary-dark: #0f5132;
            --accent: #d94f30;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 15%, rgba(20, 108, 67, .09), transparent 28rem),
                radial-gradient(circle at 88% 88%, rgba(217, 79, 48, .09), transparent 25rem),
                var(--surface);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .page {
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
            padding: 28px clamp(20px, 5vw, 72px);
        }

        .brand {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: 11px;
            color: var(--ink);
            font-size: 18px;
            font-weight: 800;
            text-decoration: none;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #12376d url('{{ asset('images/investrivo-logo.png') }}') center 48% / 160% no-repeat;
            box-shadow: 0 8px 18px rgba(8, 72, 156, .22);
        }

        .error-layout {
            width: min(100%, 1080px);
            margin: auto;
            display: grid;
            grid-template-columns: minmax(280px, .8fr) minmax(360px, 1.2fr);
            align-items: center;
            gap: clamp(48px, 8vw, 112px);
            padding: 52px 0;
        }

        .visual {
            position: relative;
            min-height: 390px;
            display: grid;
            place-items: center;
        }

        .orbit {
            position: absolute;
            width: min(100%, 390px);
            aspect-ratio: 1;
            border: 1px dashed rgba(20, 108, 67, .22);
            border-radius: 50%;
        }

        .orbit::before,
        .orbit::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: var(--accent);
        }

        .orbit::before {
            width: 15px;
            height: 15px;
            top: 15%;
            right: 12%;
            box-shadow: 0 0 0 8px rgba(217, 79, 48, .1);
        }

        .orbit::after {
            width: 10px;
            height: 10px;
            bottom: 8%;
            left: 24%;
            background: var(--primary);
            box-shadow: 0 0 0 7px rgba(20, 108, 67, .1);
        }

        .shield {
            position: relative;
            z-index: 1;
            width: 190px;
            height: 218px;
            display: grid;
            place-items: center;
            color: white;
            border-radius: 54% 54% 60% 60% / 24% 24% 76% 76%;
            background: linear-gradient(145deg, #18794e, var(--primary-dark));
            box-shadow: 0 30px 60px rgba(15, 81, 50, .22);
            clip-path: polygon(50% 0, 94% 18%, 88% 69%, 72% 88%, 50% 100%, 28% 88%, 12% 69%, 6% 18%);
        }

        .lock {
            width: 76px;
            height: 64px;
            position: relative;
            margin-top: 34px;
            border-radius: 14px;
            background: white;
        }

        .lock::before {
            content: "";
            position: absolute;
            width: 43px;
            height: 42px;
            left: 50%;
            top: -34px;
            translate: -50% 0;
            border: 10px solid white;
            border-bottom: 0;
            border-radius: 28px 28px 0 0;
        }

        .lock::after {
            content: "";
            position: absolute;
            width: 9px;
            height: 20px;
            left: 50%;
            top: 21px;
            translate: -50% 0;
            border-radius: 9px;
            background: var(--primary-dark);
        }

        .code-badge {
            position: absolute;
            z-index: 2;
            right: 5%;
            bottom: 18%;
            padding: 10px 16px;
            border: 1px solid rgba(217, 79, 48, .16);
            border-radius: 999px;
            color: var(--accent);
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 12px 30px rgba(23, 32, 26, .1);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .eyebrow {
            margin: 0 0 15px;
            color: var(--accent);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        h1 {
            max-width: 620px;
            margin: 0 0 18px;
            font-size: clamp(40px, 5vw, 68px);
            line-height: 1.03;
            letter-spacing: -.045em;
        }

        .message {
            max-width: 570px;
            margin: 0 0 32px;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .button {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 20px;
            border: 1px solid var(--line);
            border-radius: 9px;
            color: var(--ink);
            background: white;
            font-size: 14px;
            font-weight: 750;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .button-primary {
            border-color: var(--primary);
            color: white;
            background: var(--primary);
            box-shadow: 0 10px 22px rgba(20, 108, 67, .18);
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(23, 32, 26, .12);
        }

        .button-primary:hover {
            background: var(--primary-dark);
        }

        .button:focus-visible {
            outline: 3px solid rgba(47, 128, 237, .3);
            outline-offset: 3px;
        }

        .button svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        footer {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 760px) {
            .page {
                padding: 22px 20px;
            }

            .error-layout {
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 38px 0 48px;
            }

            .visual {
                min-height: 260px;
            }

            .orbit {
                width: 260px;
            }

            .shield {
                width: 130px;
                height: 150px;
            }

            .lock {
                scale: .72;
                margin-top: 24px;
            }

            .code-badge {
                right: calc(50% - 145px);
                bottom: 10%;
            }

            h1 {
                font-size: clamp(38px, 12vw, 54px);
            }

            footer {
                flex-direction: column;
                gap: 6px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .button {
                transition: none;
            }
        }
    </style>
</head>
<body>
    @php
        $homeUrl = auth()->check()
            ? route(\App\Support\RolePermission::firstAccessibleRoute(auth()->user()))
            : route('login');
    @endphp
    <div class="page">
        <header>
            <a class="brand" href="{{ $homeUrl }}" aria-label="Investrivo home">
                <span class="brand-logo" aria-hidden="true"></span>
                <span>Investrivo</span>
            </a>
        </header>

        <main class="error-layout">
            <div class="visual" aria-hidden="true">
                <div class="orbit"></div>
                <div class="shield">
                    <span class="lock"></span>
                </div>
                <span class="code-badge">403</span>
            </div>

            <section aria-labelledby="error-title">
                <p class="eyebrow">Restricted area</p>
                <h1 id="error-title">You don’t have access to this page.</h1>
                <p class="message">
                    {{ $exception->getMessage() ?: 'Your account does not have permission to view this area. If you think this is a mistake, contact your administrator.' }}
                </p>
            </section>
        </main>

        <footer>
            <span>Investrivo inventory platform</span>
            <span>Error code: 403 · Access forbidden</span>
        </footer>
    </div>
</body>
</html>
