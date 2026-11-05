@php
    $roleNavigation = config('role-navigation.'.auth()->user()->rol->value, []);
@endphp

<nav aria-label="Secciones asignadas a tu rol">
    <p class="small text-body-secondary mb-2">Secciones asignadas a tu rol</p>
    <ul class="nav nav-pills flex-column gap-1">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('home') }}">Inicio</a>
        </li>
        @foreach ($roleNavigation as $module)
            <li class="nav-item">
                @if (in_array($module, ['Reservas', 'Mis reservas'], true))
                    <a class="nav-link" href="{{ route('reservations.index') }}">{{ $module }}</a>
                @elseif (in_array($module, ['Pedidos', 'Mis pedidos'], true))
                    <a class="nav-link" href="{{ route('orders.index') }}">{{ $module }}</a>
                @elseif ($module === 'Productos')
                    <a class="nav-link" href="{{ route('products.index') }}">{{ $module }}</a>
                @elseif ($module === 'Menú del día')
                    <a class="nav-link" href="{{ route('menus.index') }}">{{ $module }}</a>
                @elseif ($module === 'Incidencias')
                    <a class="nav-link" href="{{ route('incidents.index') }}">{{ $module }}</a>
                @else
                    <span class="nav-link text-body-secondary" aria-disabled="true">{{ $module }}</span>
                @endif
            </li>
        @endforeach
    </ul>
    <p class="small text-body-secondary mt-2 mb-0">
        Las demás secciones se habilitarán a medida que avance su implementación.
    </p>
</nav>
