<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="{{ route('home') }}">
            <span class="brand-mark" aria-hidden="true">HC</span>
            <span>HostalConnect <span class="brand-unab">UNAB</span></span>
        </a>

        <button
            class="navbar-toggler d-lg-none"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#appSidebar"
            aria-controls="appSidebar"
            aria-expanded="false"
            aria-label="Mostrar navegación"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="d-none d-sm-block text-end text-white">
                <span class="d-block small fw-semibold">
                    {{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}
                </span>
                <span class="d-block small opacity-75">{{ auth()->user()->rol->value }}</span>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light btn-sm" type="submit">Cerrar sesión</button>
            </form>
        </div>
    </div>
</nav>
