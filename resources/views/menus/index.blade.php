@extends('layouts.app')

@section('title', 'Menú del día | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Menú del día</h1>
            <p class="text-body-secondary mb-0">Consulta los menús y administra sus borradores.</p>
        </div>
        @can('create', \App\Models\MenuDia::class)
            <a class="btn btn-primary" href="{{ route('menus.create') }}">Crear borrador</a>
        @endcan
    </div>

    @error('productos')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    <form method="GET" action="{{ route('menus.index') }}" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            @can('create', \App\Models\MenuDia::class)
                <div class="col-12 col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="BORRADOR" @selected(($filters['estado'] ?? '') === 'BORRADOR')>
                            Borrador
                        </option>
                        <option value="PUBLICADO" @selected(($filters['estado'] ?? '') === 'PUBLICADO')>
                            Publicado
                        </option>
                    </select>
                </div>
            @endcan
            <div class="col-12 col-md-4">
                <label for="fecha_desde" class="form-label">Desde</label>
                <input
                    class="form-control"
                    id="fecha_desde"
                    name="fecha_desde"
                    type="date"
                    value="{{ $filters['fecha_desde'] ?? '' }}"
                >
            </div>
            <div class="col-12 col-md-4">
                <label for="fecha_hasta" class="form-label">Hasta</label>
                <input
                    class="form-control"
                    id="fecha_hasta"
                    name="fecha_hasta"
                    type="date"
                    value="{{ $filters['fecha_hasta'] ?? '' }}"
                >
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="{{ route('menus.index') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="row g-3">
        @forelse ($menus as $menu)
            <div class="col-12">
                <article class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h2 class="h5 mb-0">
                                        Menú del {{ $menu->fecha->format('d/m/Y') }}
                                    </h2>
                                    @if ($menu->estado === \App\Enums\EstadoMenuDiaEnum::PUBLICADO)
                                        <span class="badge text-bg-success">Publicado</span>
                                    @else
                                        <span class="badge text-bg-secondary">Borrador</span>
                                    @endif
                                </div>
                                @if ($menu->productos->isNotEmpty())
                                    <ul class="mb-0 ps-3">
                                        @foreach ($menu->productos as $product)
                                            <li>{{ $product->nombre }} — ${{ number_format((float) $product->precio, 2, ',', '.') }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-body-secondary mb-0">Aún no se han agregado productos.</p>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('menus.show', $menu) }}">
                                    Ver menú
                                </a>
                                @can('update', $menu)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('menus.edit', $menu) }}">
                                        Editar borrador
                                    </a>
                                @endcan
                                @can('publish', $menu)
                                    <form
                                        method="POST"
                                        action="{{ route('menus.publish', $menu) }}"
                                        data-confirm="¿Publicar el menú de esta fecha? Después no podrá editarse."
                                        data-loading-text="Publicando..."
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-success" type="submit">Publicar</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="card card-body py-5 text-center text-body-secondary">
                    <span class="d-block fw-semibold">Aún no hay menús</span>
                    No hay menús para los filtros seleccionados.
                </div>
            </div>
        @endforelse
    </div>

    @if ($menus->hasPages())
        <div class="mt-4">{{ $menus->links('pagination::bootstrap-5') }}</div>
    @endif
@endsection
