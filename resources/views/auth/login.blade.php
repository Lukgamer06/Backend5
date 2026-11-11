@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', auth()->check() ? 'Inicio | HostalConnect UNAB' : 'Iniciar sesión | HostalConnect UNAB')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <section class="card auth-card mx-auto">
            <div class="row g-0">
                <div class="col-12 col-md-5">
                    <div class="auth-brand-panel">
                        <div>
                            <div class="brand-mark d-inline-flex align-items-center justify-content-center fw-bold">
                                HC
                            </div>
                            <h2>Una estadía mejor, desde el primer contacto.</h2>
                            <p>
                                Reservas, pedidos y atención del hostal en un solo lugar.
                            </p>
                        </div>
                        <span class="auth-brand-note">HostalConnect · UNAB</span>
                    </div>
                </div>

                <div class="col-12 col-md-7">
                    <div class="auth-form-panel">
                        @auth
                            <header class="mb-4">
                                <p class="text-primary fw-semibold mb-2">HostalConnect UNAB</p>
                                <h1 class="h3 mb-2">Sesión iniciada</h1>
                                <p class="text-body-secondary mb-0">
                                    Bienvenido, {{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}.
                                </p>
                            </header>
                            <a class="btn btn-primary text-white w-100" href="{{ route('home') }}">
                                Ir al panel principal
                            </a>
                        @else
                            <header class="mb-4">
                                <p class="text-primary fw-semibold mb-2">Tu espacio de trabajo</p>
                                <h1 class="h3 mb-2">Iniciar sesión</h1>
                                <p class="text-body-secondary mb-0">
                                    Ingresa con tu correo institucional o registrado.
                                </p>
                            </header>

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

                                <button class="btn btn-primary text-white w-100" type="submit">
                                    Ingresar
                                </button>
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
