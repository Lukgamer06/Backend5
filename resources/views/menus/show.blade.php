@extends('layouts.app')

@section('title', 'Detalle del menú | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="h2 mb-0">
                    Menú del {{ $menu->fecha->format('d/m/Y') }}
                </h1>
                @if ($menu->estado === \App\Enums\EstadoMenuDiaEnum::PUBLICADO)
                    <span class="badge text-bg-success">Publicado</span>
                @else
                    <span class="badge text-bg-secondary">Borrador</span>
                @endif
            </div>
            <p class="text-body-secondary mb-0">Productos incluidos en este menú.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('menus.index') }}">Volver</a>
            @can('update', $menu)
                <a class="btn btn-outline-primary" href="{{ route('menus.edit', $menu) }}">Editar borrador</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($menu->productos as $product)
                <div class="list-group-item d-flex justify-content-between align-items-start gap-3 py-3">
                    <div>
                        <h2 class="h6 mb-1">{{ $product->nombre }}</h2>
                        @if ($product->descripcion)
                            <p class="text-body-secondary mb-0">{{ $product->descripcion }}</p>
                        @endif
                    </div>
                    <span class="fw-semibold text-nowrap">
                        ${{ number_format((float) $product->precio, 2, ',', '.') }}
                    </span>
                </div>
            @empty
                <div class="list-group-item py-5 text-center text-body-secondary">
                    Este borrador todavía no tiene productos.
                </div>
            @endforelse
        </div>
    </div>

    @can('publish', $menu)
        <form
            class="mt-3"
            method="POST"
            action="{{ route('menus.publish', $menu) }}"
            onsubmit="return confirm('¿Publicar el menú? Los cambios quedarán bloqueados.')"
        >
            @csrf
            @method('PATCH')
            <button class="btn btn-success" type="submit">Publicar menú</button>
        </form>
    @endcan
@endsection
