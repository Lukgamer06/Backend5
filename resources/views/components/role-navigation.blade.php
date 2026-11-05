@php
    $roleNavigation = config('role-navigation.'.auth()->user()->rol->value, []);
@endphp

<nav class="mb-4" aria-label="Módulos disponibles para tu perfil">
    <p class="small text-body-secondary mb-2">Secciones asignadas a tu rol</p>
    <ul class="nav nav-pills justify-content-center gap-2">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('home') }}">Inicio</a>
        </li>
        @foreach ($roleNavigation as $module)
            <li class="nav-item">
                <span class="nav-link text-body-secondary border" aria-disabled="true">{{ $module }}</span>
            </li>
        @endforeach
    </ul>
    <p class="small text-body-secondary mt-2 mb-0">
        Las secciones se habilitarán a medida que avance su implementación.
    </p>
</nav>
