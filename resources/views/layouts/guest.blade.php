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
        body {
            min-height: 100vh;
            background: linear-gradient(145deg, #f3f6fb 0%, #e8eef8 100%);
        }

        .auth-card {
            max-width: 440px;
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgb(24 43 77 / 10%);
        }

        .brand-mark {
            width: 3rem;
            height: 3rem;
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
