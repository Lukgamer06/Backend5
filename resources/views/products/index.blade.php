@extends('layouts.app')

@section('title', 'Productos | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Productos</h1>
            <p class="text-body-secondary mb-0">Consulta el catálogo y administra su disponibilidad.</p>
        </div>
        @can('create', \App\Models\Producto::class)
            <a class="btn btn-primary" href="{{ route('products.create') }}">Nuevo producto</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-7">
                <label for="search" class="form-label">Buscar por nombre o descripción</label>
                <input
                    class="form-control"
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] ?? '' }}"
                    maxlength="100"
                >
            </div>
            @can('create', \App\Models\Producto::class)
                <div class="col-12 col-md-3">
                    <label for="disponible" class="form-label">Disponibilidad</label>
                    <select class="form-select" id="disponible" name="disponible">
                        <option value="">Todos</option>
                        <option value="1" @selected(($filters['disponible'] ?? '') === '1')>Activos</option>
                        <option value="0" @selected(($filters['disponible'] ?? '') === '0')>Inactivos</option>
                    </select>
                </div>
            @endcan
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Buscar</button>
                <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Precio</th>
                        <th scope="col">Disponibilidad</th>
                        @can('create', \App\Models\Producto::class)
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <th scope="row">{{ $product->nombre }}</th>
                            <td>{{ $product->descripcion ?: '—' }}</td>
                            <td>${{ number_format((float) $product->precio, 2, ',', '.') }}</td>
                            <td>
                                @if ($product->disponible)
                                    <span class="badge text-bg-success">Activo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            @can('create', \App\Models\Producto::class)
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('products.edit', $product) }}">
                                        Editar
                                    </a>
                                    @if ($product->disponible)
                                        <form
                                            class="d-inline"
                                            method="POST"
                                            action="{{ route('products.deactivate', $product) }}"
                                            data-confirm="¿Desactivar este producto? Los pedidos existentes conservarán su detalle."
                                            data-loading-text="Desactivando..."
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Desactivar</button>
                                        </form>
                                    @else
                                        <form class="d-inline" method="POST" action="{{ route('products.activate', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-success" type="submit">Activar</button>
                                        </form>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td
                                class="py-5 text-center text-body-secondary"
                                colspan="{{ auth()->user()->can('create', \App\Models\Producto::class) ? 5 : 4 }}"
                            >
                                <span class="d-block fw-semibold">No hay productos para mostrar</span>
                                No hay productos que coincidan con la búsqueda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="card-footer">{{ $products->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
