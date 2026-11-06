@extends('layouts.app')

@section('title', 'Detalle de incidencia | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="h2 mb-0">{{ $incident->titulo }}</h1>
                <span class="badge {{ $incident->estado === \App\Enums\EstadoIncidenciaEnum::RESUELTA ? 'text-bg-success' : ($incident->estado === \App\Enums\EstadoIncidenciaEnum::EN_ATENCION ? 'text-bg-primary' : 'text-bg-secondary') }}">
                    {{ str_replace('_', ' ', $incident->estado->value) }}
                </span>
            </div>
            <p class="text-body-secondary mb-0">
                Reportada el {{ $incident->created_at->format('d/m/Y H:i') }}
                por {{ $incident->usuario->nombres }} {{ $incident->usuario->apellidos }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('incidents.index') }}">Volver</a>
            @can('update', $incident)
                <a class="btn btn-primary" href="{{ route('incidents.edit', $incident) }}">Actualizar</a>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-4">
                <h2 class="h6">Descripción</h2>
                <p class="mb-0 text-break">{{ $incident->descripcion }}</p>
            </div>
            <dl class="row mb-0">
                <dt class="col-sm-3">Prioridad</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $incident->prioridad === \App\Enums\PrioridadIncidenciaEnum::CRITICA ? 'text-bg-danger' : ($incident->prioridad === \App\Enums\PrioridadIncidenciaEnum::ALTA ? 'text-bg-warning' : 'text-bg-secondary') }}">
                        {{ str_replace('_', ' ', $incident->prioridad->value) }}
                    </span>
                </dd>
                <dt class="col-sm-3">Mesa</dt>
                <dd class="col-sm-9">{{ $incident->mesa ? 'Mesa '.$incident->mesa->numero : 'Sin mesa relacionada' }}</dd>
                <dt class="col-sm-3">Pedido</dt>
                <dd class="col-sm-9">
                    {{ $incident->pedido ? 'Pedido #'.$incident->pedido->id : 'Sin pedido relacionado' }}
                </dd>
            </dl>
        </div>
    </div>
@endsection
