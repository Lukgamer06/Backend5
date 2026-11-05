@extends('layouts.app')

@section('title', 'Pedidos | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Pedidos</h1>
            <p class="text-body-secondary mb-0">Consulta y administra los pedidos.</p>
        </div>
        @can('create', \App\Models\Pedido::class)
            <a class="btn btn-primary" href="{{ route('orders.create') }}">Nuevo pedido</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('orders.index') }}" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label">Buscar por número o cliente</label>
                <input
                    class="form-control"
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] ?? '' }}"
                    maxlength="100"
                >
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\EstadoPedidoEnum::cases() as $estado)
                        <option value="{{ $estado->value }}" @selected(($filters['estado'] ?? '') === $estado->value)>
                            {{ str_replace('_', ' ', $estado->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label for="tipo_atencion" class="form-label">Atención</label>
                <select class="form-select" id="tipo_atencion" name="tipo_atencion">
                    <option value="">Todas</option>
                    @foreach (\App\Enums\TipoAtencionEnum::cases() as $tipo)
                        <option value="{{ $tipo->value }}" @selected(($filters['tipo_atencion'] ?? '') === $tipo->value)>
                            {{ str_replace('_', ' ', $tipo->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label for="fecha_desde" class="form-label">Desde</label>
                <input class="form-control" id="fecha_desde" name="fecha_desde" type="date" value="{{ $filters['fecha_desde'] ?? '' }}">
            </div>
            <div class="col-6 col-md-1">
                <label for="fecha_hasta" class="form-label">Hasta</label>
                <input class="form-control" id="fecha_hasta" name="fecha_hasta" type="date" value="{{ $filters['fecha_hasta'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="{{ route('orders.index') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Atención / servicio</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Total</th>
                        <th scope="col">Fecha</th>
                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->cliente->nombres }} {{ $order->cliente->apellidos }}</td>
                            <td>
                                <span class="d-block">{{ str_replace('_', ' ', $order->tipo_atencion->value) }}</span>
                                <span class="small text-body-secondary">
                                    {{ str_replace('_', ' ', $order->modalidad_servicio->value) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ str_replace('_', ' ', $order->estado->value) }}
                                </span>
                            </td>
                            <td>${{ number_format((float) $order->total, 2, ',', '.') }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('orders.show', $order) }}">
                                    Ver
                                </a>
                                @can('update', $order)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('orders.edit', $order) }}">
                                        Editar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-5 text-center text-body-secondary" colspan="7">
                                <span class="d-block fw-semibold">No hay pedidos registrados</span>
                                No hay pedidos que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="card-footer">{{ $orders->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
