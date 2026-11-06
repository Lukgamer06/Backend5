@extends('layouts.app')

@section('title', 'Actualizar incidencia | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Actualizar incidencia</h1>
        <p class="text-body-secondary mb-0">
            {{ $incident->titulo }} · reportada por {{ $incident->usuario->nombres }} {{ $incident->usuario->apellidos }}
        </p>
    </div>

    <form method="POST" action="{{ route('incidents.update', $incident) }}" class="card card-body">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label for="estado" class="form-label">Estado</label>
                <select
                    class="form-select @error('estado') is-invalid @enderror"
                    id="estado"
                    name="estado"
                    required
                    aria-describedby="estado-error"
                >
                    @foreach ($statuses as $status)
                        <option
                            value="{{ $status->value }}"
                            @selected(old('estado', $incident->estado->value) === $status->value)
                        >
                            {{ str_replace('_', ' ', $status->value) }}
                        </option>
                    @endforeach
                </select>
                @error('estado')
                    <div class="invalid-feedback" id="estado-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="prioridad" class="form-label">Prioridad</label>
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
                            @selected(old('prioridad', $incident->prioridad->value) === $priority->value)
                        >
                            {{ str_replace('_', ' ', $priority->value) }}
                        </option>
                    @endforeach
                </select>
                @error('prioridad')
                    <div class="invalid-feedback" id="prioridad-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                <button class="btn btn-primary" type="submit">Guardar actualización</button>
                <a class="btn btn-outline-secondary" href="{{ route('incidents.show', $incident) }}">Cancelar</a>
            </div>
        </div>
    </form>
@endsection
