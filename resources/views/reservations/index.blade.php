@extends('layouts.app')

@section('title', 'Reservas | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Reservas</h1>
            <p class="text-body-secondary mb-0">Consulta y administra las reservas.</p>
        </div>
        @can('create', \App\Models\Reserva::class)
            <a class="btn btn-primary" href="{{ route('reservations.create') }}">Nueva reserva</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('reservations.index') }}" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label">Buscar cliente o mesa</label>
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
                    @foreach (\App\Enums\EstadoReservaEnum::cases() as $estado)
                        <option value="{{ $estado->value }}" @selected(($filters['estado'] ?? '') === $estado->value)>
                            {{ str_replace('_', ' ', $estado->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="fecha_desde" class="form-label">Desde</label>
                <input
                    class="form-control"
                    id="fecha_desde"
                    name="fecha_desde"
                    type="date"
                    value="{{ $filters['fecha_desde'] ?? '' }}"
                >
            </div>
            <div class="col-6 col-md-2">
                <label for="fecha_hasta" class="form-label">Hasta</label>
                <input
                    class="form-control"
                    id="fecha_hasta"
                    name="fecha_hasta"
                    type="date"
                    value="{{ $filters['fecha_hasta'] ?? '' }}"
                >
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="{{ route('reservations.index') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Fecha y hora</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Mesa</th>
                        <th scope="col">Personas</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservations as $reservation)
                        <tr>
                            <td>
                                <span class="d-block">{{ $reservation->fecha_inicio->format('d/m/Y H:i') }}</span>
                                <span class="small text-body-secondary">hasta {{ $reservation->fecha_fin->format('H:i') }}</span>
                            </td>
                            <td>{{ $reservation->cliente->nombres }} {{ $reservation->cliente->apellidos }}</td>
                            <td>{{ $reservation->mesa->numero }}</td>
                            <td>{{ $reservation->numero_personas }}</td>
                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ str_replace('_', ' ', $reservation->estado->value) }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('reservations.show', $reservation) }}">
                                    Ver
                                </a>
                                @can('update', $reservation)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('reservations.edit', $reservation) }}">
                                        Editar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-5 text-center text-body-secondary" colspan="6">
                                <span class="d-block fw-semibold">No hay reservas registradas</span>
                                No hay reservas que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reservations->hasPages())
            <div class="card-footer">{{ $reservations->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
