@extends('layouts.app')

@section('title', 'Incidencias | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Incidencias</h1>
            <p class="text-body-secondary mb-0">Reporta y da seguimiento a incidentes operativos.</p>
        </div>
        @can('create', \App\Models\Incidencia::class)
            <a class="btn btn-primary" href="{{ route('incidents.create') }}">Reportar incidencia</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('incidents.index') }}" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label for="search" class="form-label">Buscar por título o descripción</label>
                <input
                    class="form-control"
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] ?? '' }}"
                    maxlength="100"
                >
            </div>
            <div class="col-6 col-md-3">
                <label for="prioridad" class="form-label">Prioridad</label>
                <select class="form-select" id="prioridad" name="prioridad">
                    <option value="">Todas</option>
                    @foreach (\App\Enums\PrioridadIncidenciaEnum::cases() as $prioridad)
                        <option
                            value="{{ $prioridad->value }}"
                            @selected(($filters['prioridad'] ?? '') === $prioridad->value)
                        >
                            {{ str_replace('_', ' ', $prioridad->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\EstadoIncidenciaEnum::cases() as $estado)
                        <option
                            value="{{ $estado->value }}"
                            @selected(($filters['estado'] ?? '') === $estado->value)
                        >
                            {{ str_replace('_', ' ', $estado->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="{{ route('incidents.index') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Incidencia</th>
                        <th scope="col">Reportada por</th>
                        <th scope="col">Referencia</th>
                        <th scope="col">Prioridad</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Fecha</th>
                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incidents as $incident)
                        <tr>
                            <th scope="row">
                                <span class="d-block">{{ $incident->titulo }}</span>
                                <span class="small text-body-secondary">
                                    {{ \Illuminate\Support\Str::limit($incident->descripcion, 70) }}
                                </span>
                            </th>
                            <td>{{ $incident->usuario->nombres }} {{ $incident->usuario->apellidos }}</td>
                            <td>
                                @if ($incident->mesa)
                                    Mesa {{ $incident->mesa->numero }}
                                @endif
                                @if ($incident->pedido)
                                    <span class="d-block">Pedido #{{ $incident->pedido->id }}</span>
                                @endif
                                @if (! $incident->mesa && ! $incident->pedido)
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $incident->prioridad === \App\Enums\PrioridadIncidenciaEnum::CRITICA ? 'text-bg-danger' : ($incident->prioridad === \App\Enums\PrioridadIncidenciaEnum::ALTA ? 'text-bg-warning' : 'text-bg-secondary') }}">
                                    {{ str_replace('_', ' ', $incident->prioridad->value) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $incident->estado === \App\Enums\EstadoIncidenciaEnum::RESUELTA ? 'text-bg-success' : ($incident->estado === \App\Enums\EstadoIncidenciaEnum::EN_ATENCION ? 'text-bg-primary' : 'text-bg-secondary') }}">
                                    {{ str_replace('_', ' ', $incident->estado->value) }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ $incident->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('incidents.show', $incident) }}">
                                    Ver
                                </a>
                                @can('update', $incident)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('incidents.edit', $incident) }}">
                                        Actualizar
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-5 text-center text-body-secondary" colspan="7">
                                <span class="d-block fw-semibold">Sin incidencias registradas</span>
                                No hay incidencias que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($incidents->hasPages())
            <div class="card-footer">{{ $incidents->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
