@extends('layouts.app')

@section('title', 'Reportar incidencia | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Reportar incidencia</h1>
        <p class="text-body-secondary mb-0">Describe la situación para que el equipo pueda atenderla.</p>
    </div>

    <form method="POST" action="{{ route('incidents.store') }}" class="card card-body">
        @csrf
        <div class="row g-3">
            <div class="col-12 col-md-8">
                <label for="titulo" class="form-label">Título</label>
                <input
                    class="form-control @error('titulo') is-invalid @enderror"
                    id="titulo"
                    name="titulo"
                    type="text"
                    maxlength="150"
                    value="{{ old('titulo') }}"
                    required
                    aria-describedby="titulo-error"
                >
                @error('titulo')
                    <div class="invalid-feedback" id="titulo-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-4">
                <label for="prioridad" class="form-label">Prioridad inicial</label>
                <select
                    class="form-select @error('prioridad') is-invalid @enderror"
                    id="prioridad"
                    name="prioridad"
                    required
                    aria-describedby="prioridad-error"
                >
                    @foreach ($priorities as $priority)
                        <option
                            value="{{ $priority->value }}"
                            @selected(old('prioridad', \App\Enums\PrioridadIncidenciaEnum::MEDIA->value) === $priority->value)
                        >
                            {{ str_replace('_', ' ', $priority->value) }}
                        </option>
                    @endforeach
                </select>
                @error('prioridad')
                    <div class="invalid-feedback" id="prioridad-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea
                    class="form-control @error('descripcion') is-invalid @enderror"
                    id="descripcion"
                    name="descripcion"
                    rows="5"
                    maxlength="65535"
                    required
                    aria-describedby="descripcion-error"
                >{{ old('descripcion') }}</textarea>
                @error('descripcion')
                    <div class="invalid-feedback" id="descripcion-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="mesa_id" class="form-label">Mesa relacionada <span class="text-body-secondary">(opcional)</span></label>
                <select
                    class="form-select @error('mesa_id') is-invalid @enderror"
                    id="mesa_id"
                    name="mesa_id"
                    aria-describedby="mesa_id-error"
                >
                    <option value="">Sin mesa relacionada</option>
                    @foreach ($mesas as $mesa)
                        <option value="{{ $mesa->id }}" @selected((string) old('mesa_id') === (string) $mesa->id)>
                            Mesa {{ $mesa->numero }}
                        </option>
                    @endforeach
                </select>
                @error('mesa_id')
                    <div class="invalid-feedback" id="mesa_id-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="pedido_id" class="form-label">Pedido relacionado <span class="text-body-secondary">(opcional)</span></label>
                <select
                    class="form-select @error('pedido_id') is-invalid @enderror"
                    id="pedido_id"
                    name="pedido_id"
                    aria-describedby="pedido_id-error"
                >
                    <option value="">Sin pedido relacionado</option>
                    @foreach ($pedidos as $pedido)
                        <option value="{{ $pedido->id }}" @selected((string) old('pedido_id') === (string) $pedido->id)>
                            Pedido #{{ $pedido->id }} · {{ $pedido->cliente->nombres }} {{ $pedido->cliente->apellidos }}
                        </option>
                    @endforeach
                </select>
                @error('pedido_id')
                    <div class="invalid-feedback" id="pedido_id-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button class="btn btn-primary" type="submit">Registrar incidencia</button>
                <a class="btn btn-outline-secondary" href="{{ route('incidents.index') }}">Cancelar</a>
            </div>
        </div>
    </form>
@endsection
