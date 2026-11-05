<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Iniciar sesión | HostalConnect UNAB</title>
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
</head>
<body class="d-flex align-items-center py-5">
<main class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <section class="card auth-card mx-auto">
                <div class="card-body p-4 p-sm-5">
                    <header class="mb-4 text-center">
                        <div class="brand-mark d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white fw-bold mb-3">
                            HC
                        </div>
                        <p class="text-primary fw-semibold mb-1">HostalConnect UNAB</p>
                        @auth
                            <h1 class="h3 mb-2">Sesión iniciada</h1>
                            <p class="text-body-secondary mb-0">
                                Bienvenido, {{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}.
                            </p>
                        @else
                            <h1 class="h3 mb-2">Iniciar sesión</h1>
                            <p class="text-body-secondary mb-0">Ingresa con tu correo institucional o registrado.</p>
                        @endauth
                    </header>

                    @if (session('status'))
                        <div class="alert alert-success" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert" aria-live="polite">
                            Revisa los datos ingresados e inténtalo nuevamente.
                        </div>
                    @endif

                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-primary w-100" type="submit">
                                Cerrar sesión
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('login.store') }}" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo electrónico</label>
                                <input
                                    id="correo"
                                    name="correo"
                                    type="email"
                                    value="{{ old('correo') }}"
                                    class="form-control @error('correo') is-invalid @enderror"
                                    autocomplete="username"
                                    inputmode="email"
                                    maxlength="255"
                                    required
                                    autofocus
                                    aria-describedby="correo-error"
                                >
                                @error('correo')
                                    <div id="correo-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    autocomplete="current-password"
                                    required
                                    aria-describedby="password-error"
                                >
                                @error('password')
                                    <div id="password-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-check mb-4">
                                <input
                                    id="remember"
                                    name="remember"
                                    value="1"
                                    type="checkbox"
                                    class="form-check-input"
                                    @checked(old('remember'))
                                >
                                <label class="form-check-label" for="remember">Recordarme</label>
                            </div>

                            <button class="btn btn-primary btn-lg w-100" type="submit">
                                Ingresar
                            </button>
                        </form>
                    @endauth
                </div>
                <footer class="card-footer bg-transparent border-0 text-center px-4 pb-4">
                    <small class="text-body-secondary">HostalConnect UNAB</small>
                </footer>
            </section>
        </div>
    </div>
</main>
</body>
</html>
