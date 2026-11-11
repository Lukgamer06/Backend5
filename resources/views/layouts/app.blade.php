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
            --hc-surface: #f4f7f8;
            --hc-border: #e2e9ec;
            --hc-muted: #60727e;
        }

        body {
            min-height: 100vh;
            color: var(--hc-ink);
            background: var(--hc-surface) !important;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .app-sidebar {
            min-height: calc(100vh - 4.5rem);
            background: #fff;
            box-shadow: 0.75rem 0 2rem rgb(20 50 74 / 3%);
        }

        .app-content {
            min-height: calc(100vh - 8rem);
        }

        .app-navbar {
            min-height: 4.5rem;
            background: linear-gradient(110deg, #12334c, #1d5870);
            box-shadow: 0 0.5rem 1.5rem rgb(20 50 74 / 12%);
        }

        .brand-mark {
            display: inline-grid;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border: 1px solid rgb(255 255 255 / 28%);
            border-radius: 0.7rem;
            color: #fff;
            background: rgb(255 255 255 / 12%);
            font-size: 0.72rem;
            letter-spacing: 0.04em;
        }

        .brand-unab {
            color: #f0c39b;
        }

        .app-sidebar .nav {
            padding: 1.25rem 0.8rem;
        }

        .app-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            min-height: 2.85rem;
            border-radius: 0.8rem;
            color: #526977;
            font-size: 0.94rem;
            font-weight: 550;
            transition: color 160ms ease-out, background-color 160ms ease-out, transform 160ms ease-out;
        }

        .app-sidebar .nav-link:hover {
            color: var(--hc-primary);
            background: #f0f5f6;
        }

        .app-sidebar .nav-link:active {
            transform: scale(0.98);
        }

        .app-sidebar .nav-link.active {
            color: #fff;
            background: var(--hc-primary);
            box-shadow: 0 0.45rem 1rem rgb(22 75 105 / 18%);
        }

        .nav-link-mark {
            display: inline-grid;
            width: 1.7rem;
            height: 1.7rem;
            place-items: center;
            border-radius: 0.55rem;
            color: #748995;
            background: #eef3f4;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .nav-link.active .nav-link-mark {
            color: #fff;
            background: rgb(255 255 255 / 18%);
        }

        main > .breadcrumb {
            margin-bottom: 1.25rem;
        }

        .card {
            border: 1px solid var(--hc-border) !important;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.5rem rgb(20 50 74 / 4%) !important;
        }

        .card-header {
            border-color: var(--hc-border);
            border-radius: 1rem 1rem 0 0 !important;
        }

        .form-control,
        .form-select {
            min-height: 2.8rem;
            border-color: #d7e1e5;
            border-radius: 0.7rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #6b9aaa;
            box-shadow: 0 0 0 0.22rem rgb(22 75 105 / 12%);
        }

        .btn {
            border-radius: 0.7rem;
            font-weight: 600;
            transition: transform 140ms ease-out, box-shadow 140ms ease-out, background-color 140ms ease-out;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            border-color: var(--hc-primary);
            background-color: var(--hc-primary);
        }

        .btn-primary:hover {
            border-color: #103c56;
            background-color: #103c56;
            box-shadow: 0 0.4rem 1rem rgb(22 75 105 / 18%);
        }

        .table {
            --bs-table-hover-bg: #f5f8f9;
        }

        .table thead th {
            color: #617681;
            background: #f7f9fa;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .app-content h1,
        .app-content h2,
        .app-content h3 {
            color: var(--hc-ink);
            letter-spacing: -0.025em;
        }

        @media (max-width: 991.98px) {
            .app-sidebar {
                min-height: auto;
                border-right: 0 !important;
            }

            .app-sidebar .nav {
                padding: 0.75rem 0.4rem 1rem;
            }

            .app-content {
                min-height: auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-body-tertiary">
<x-navbar />

<div class="container-fluid">
    <div class="row">
        <x-sidebar />

        <main class="col-12 col-lg-10 px-3 px-lg-4 py-4">
            <x-breadcrumb :items="$breadcrumbs ?? []" />
            <x-flash-message />

            <div class="app-content">
                @yield('content')
            </div>

            <x-footer />
        </main>
    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
@stack('scripts')
<x-form-ux />
</body>
</html>
