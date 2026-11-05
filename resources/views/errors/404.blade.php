<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Página no encontrada | HostalConnect UNAB</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
</head>
<body class="bg-body-tertiary">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <section class="card border-0 shadow-sm text-center w-100" style="max-width: 34rem" aria-labelledby="error-title">
            <div class="card-body p-4 p-md-5">
                <p class="display-1 fw-bold text-primary mb-0">404</p>
                <h1 class="h3 mt-2" id="error-title">No encontramos esa página</h1>
                <p class="text-body-secondary">
                    Es posible que la dirección haya cambiado o que el contenido ya no esté disponible.
                </p>
                <a class="btn btn-primary" href="{{ auth()->check() ? route('home') : route('login') }}">
                    {{ auth()->check() ? 'Volver al inicio' : 'Ir a iniciar sesión' }}
                </a>
            </div>
        </section>
    </main>
</body>
</html>
