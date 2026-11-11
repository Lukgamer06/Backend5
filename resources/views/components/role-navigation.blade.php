@php
    $roleNavigation = config('role-navigation.'.auth()->user()->rol->value, []);
@endphp

<nav aria-label="Navegación principal">
    <ul class="nav nav-pills flex-column gap-2">
        <li class="nav-item">
            <a
                class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                href="{{ route('home') }}"
                @if (request()->routeIs('home')) aria-current="page" @endif
            >
                <span class="nav-link-mark" aria-hidden="true">01</span>
                Inicio
            </a>
        </li>
        @foreach ($roleNavigation as $label => $item)
            <li class="nav-item">
                <a
                    class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}"
                    href="{{ route($item['route']) }}"
                    @if (request()->routeIs($item['active'])) aria-current="page" @endif
                >
                    <span class="nav-link-mark" aria-hidden="true">{{ str_pad((string) ($loop->iteration + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
