@extends('layouts.app')

@section('title', 'Detalle de reserva | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Detalle de reserva</h1>
            <p class="text-body-secondary mb-0">Reserva #{{ $reservation->id }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('reservations.index') }}">Volver</a>
            @can('update', $reservation)
                <a class="btn btn-primary" href="{{ route('reservations.edit', $reservation) }}">Editar</a>
            @endcan
            @can('cancel', $reservation)
                @if (! in_array($reservation->estado, [
                    \App\Enums\EstadoReservaEnum::CANCELADA,
                    \App\Enums\EstadoReservaEnum::FINALIZADA,
                ], true))
                    <form
                        method="POST"
                        action="{{ route('reservations.cancel', $reservation) }}"
                        onsubmit="return confirm('¿Deseas cancelar esta reserva?')"
                    >
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-outline-danger" type="submit">Cancelar reserva</button>
                    </form>
                @endif
            @endcan
            @can('delete', $reservation)
                <form
                    method="POST"
                    action="{{ route('reservations.destroy', $reservation) }}"
                    onsubmit="return confirm('¿Deseas eliminar esta reserva?')"
                >
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Eliminar</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4 col-lg-3">Cliente</dt>
                <dd class="col-sm-8 col-lg-9">
                    {{ $reservation->cliente->nombres }} {{ $reservation->cliente->apellidos }}
                    @if ($reservation->cliente->correo)
                        <span class="text-body-secondary">({{ $reservation->cliente->correo }})</span>
                    @endif
                </dd>

                <dt class="col-sm-4 col-lg-3">Mesa</dt>
                <dd class="col-sm-8 col-lg-9">{{ $reservation->mesa->numero }}</dd>

                <dt class="col-sm-4 col-lg-3">Fecha de inicio</dt>
                <dd class="col-sm-8 col-lg-9">{{ $reservation->fecha_inicio->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-4 col-lg-3">Fecha de fin</dt>
                <dd class="col-sm-8 col-lg-9">{{ $reservation->fecha_fin->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-4 col-lg-3">Personas</dt>
                <dd class="col-sm-8 col-lg-9">{{ $reservation->numero_personas }}</dd>

                <dt class="col-sm-4 col-lg-3">Estado</dt>
                <dd class="col-sm-8 col-lg-9">
                    <span class="badge text-bg-secondary">
                        {{ str_replace('_', ' ', $reservation->estado->value) }}
                    </span>
                </dd>

                <dt class="col-sm-4 col-lg-3">Registrada por</dt>
                <dd class="col-sm-8 col-lg-9">
                    {{ $reservation->usuario->nombres }} {{ $reservation->usuario->apellidos }}
                </dd>

                <dt class="col-sm-4 col-lg-3">Observaciones</dt>
                <dd class="col-sm-8 col-lg-9">{{ $reservation->observaciones ?: 'Sin observaciones.' }}</dd>
            </dl>
        </div>
    </div>
@endsection
