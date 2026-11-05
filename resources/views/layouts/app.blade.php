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
        }

        .app-sidebar {
            min-height: calc(100vh - 4.5rem);
            background: #f8f9fa;
        }

        .app-content {
            min-height: calc(100vh - 8rem);
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
