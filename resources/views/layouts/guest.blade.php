<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'HostalConnect UNAB')</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <style>
        :root {
            --hc-ink: #14324a;
            --hc-primary: #164b69;
            --hc-accent: #bb7048;
            --hc-border: #e2e9ec;
        }

        body {
            min-height: 100vh;
            color: var(--hc-ink);
            background:
                radial-gradient(circle at 8% 10%, rgb(187 112 72 / 9%), transparent 28rem),
                radial-gradient(circle at 92% 84%, rgb(22 75 105 / 9%), transparent 30rem),
                #f4f7f8;
        }

        .auth-card {
            max-width: 920px;
            overflow: hidden;
            border: 1px solid var(--hc-border) !important;
            border-radius: 1.4rem;
            box-shadow: 0 1.5rem 4rem rgb(20 50 74 / 12%) !important;
        }

        .brand-mark {
            width: 3.2rem;
            height: 3.2rem;
            border: 1px solid rgb(255 255 255 / 28%);
            border-radius: 1rem;
            color: #fff;
            background: rgb(255 255 255 / 13%);
            font-size: 0.9rem;
            letter-spacing: 0.04em;
        }

        .auth-brand-panel {
            display: flex;
            min-height: 100%;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(2rem, 5vw, 3.5rem);
            color: #fff;
            background:
                radial-gradient(circle at 90% 15%, rgb(255 255 255 / 10%), transparent 14rem),
                linear-gradient(145deg, #12334c, #1d5870);
        }

        .auth-brand-panel h2 {
            max-width: 18rem;
            margin-top: 3.5rem;
            font-size: clamp(1.8rem, 3vw, 2.45rem);
            font-weight: 700;
            letter-spacing: -0.035em;
            line-height: 1.15;
        }

        .auth-brand-panel p {
            max-width: 21rem;
            color: rgb(255 255 255 / 76%);
            line-height: 1.7;
        }

        .auth-brand-note {
            color: #f0c39b;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .auth-form-panel {
            padding: clamp(2rem, 5vw, 4rem);
        }

        .auth-form-panel .form-control {
            min-height: 3rem;
            border-color: #d7e1e5;
            border-radius: 0.75rem;
        }

        .auth-form-panel .form-control:focus {
            border-color: #6b9aaa;
            box-shadow: 0 0 0 0.22rem rgb(22 75 105 / 12%);
        }

        .auth-form-panel .btn {
            min-height: 3rem;
            border-radius: 0.75rem;
            background: var(--hc-primary);
            font-weight: 650;
            transition: transform 140ms ease-out, box-shadow 140ms ease-out;
        }

        .auth-form-panel .btn:active {
            transform: scale(0.98);
        }

        @media (max-width: 575.98px) {
            .auth-brand-panel {
                min-height: auto;
                padding: 1.6rem;
            }

            .auth-brand-panel h2 {
                margin-top: 2rem;
                font-size: 1.7rem;
            }

            .auth-brand-panel p {
                margin-bottom: 0;
            }

            .auth-form-panel {
                padding: 1.6rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition-duration: 0.01ms !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<main class="container d-flex min-vh-100 flex-column justify-content-center py-5">
    <x-flash-message />
    @yield('content')
    <footer class="text-center mt-4">
        <small class="text-body-secondary">&copy; {{ now()->year }} HostalConnect UNAB</small>
    </footer>
</main>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
@stack('scripts')
<x-form-ux />
</body>
</html>
