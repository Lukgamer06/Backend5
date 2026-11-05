@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', auth()->check() ? 'Inicio | HostalConnect UNAB' : 'Iniciar sesión | HostalConnect UNAB')

@section('content')
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

                @auth
                    <p class="text-body-secondary text-center mb-4">
                        Usa el menú lateral para navegar las secciones disponibles para tu rol.
                    </p>
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
        </section>
    </div>
</div>
@endsection
