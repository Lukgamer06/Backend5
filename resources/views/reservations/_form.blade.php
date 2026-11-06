@php
    $editing = isset($reservation);
    $fechaInicio = old(
        'fecha_inicio',
        $editing ? $reservation->fecha_inicio->format('Y-m-d\TH:i') : '',
    );
    $fechaFin = old(
        'fecha_fin',
        $editing ? $reservation->fecha_fin->format('Y-m-d\TH:i') : '',
    );
@endphp

<form method="POST" action="{{ $action }}" class="card card-body">
    @csrf
    @if ($httpMethod !== 'POST')
        @method($httpMethod)
    @endif

    <div class="row g-3">
        @if (auth()->user()->rol !== \App\Enums\RolUsuarioEnum::CLIENTE)
            <div class="col-12 col-md-6">
                <label for="cliente_id" class="form-label">Cliente</label>
                <select
                    class="form-select @error('cliente_id') is-invalid @enderror"
                    id="cliente_id"
                    name="cliente_id"
                    required
                    aria-describedby="cliente_id-error"
                >
                    <option value="">Selecciona un cliente</option>
                    @foreach ($clientes as $cliente)
                        <option
                            value="{{ $cliente->id }}"
                            @selected((string) old('cliente_id', $reservation->cliente_id ?? '') === (string) $cliente->id)
                        >
                            {{ $cliente->nombres }} {{ $cliente->apellidos }}
                            @if ($cliente->correo)
                                — {{ $cliente->correo }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('cliente_id')
                    <div class="invalid-feedback" id="cliente_id-error">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <div class="col-12 col-md-6">
            <label for="mesa_id" class="form-label">Mesa</label>
            <select
                class="form-select @error('mesa_id') is-invalid @enderror"
                id="mesa_id"
                name="mesa_id"
                required
                aria-describedby="mesa_id-error"
            >
                <option value="">Selecciona una mesa</option>
                @foreach ($mesas as $mesa)
                    <option
                        value="{{ $mesa->id }}"
                        @selected((string) old('mesa_id', $reservation->mesa_id ?? '') === (string) $mesa->id)
                    >
                        Mesa {{ $mesa->numero }} · capacidad {{ $mesa->capacidad }}
                        · {{ str_replace('_', ' ', $mesa->estado_operativo->value) }}
                    </option>
                @endforeach
            </select>
            @error('mesa_id')
                <div class="invalid-feedback" id="mesa_id-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label for="fecha_inicio" class="form-label">Inicio</label>
            <input
                class="form-control @error('fecha_inicio') is-invalid @enderror"
                id="fecha_inicio"
                name="fecha_inicio"
                type="datetime-local"
                value="{{ $fechaInicio }}"
                required
                aria-describedby="fecha_inicio-error"
            >
            @error('fecha_inicio')
                <div class="invalid-feedback" id="fecha_inicio-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label for="fecha_fin" class="form-label">Fin</label>
            <input
                class="form-control @error('fecha_fin') is-invalid @enderror"
                id="fecha_fin"
                name="fecha_fin"
                type="datetime-local"
                value="{{ $fechaFin }}"
                required
                aria-describedby="fecha_fin-error"
            >
            @error('fecha_fin')
                <div class="invalid-feedback" id="fecha_fin-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label for="numero_personas" class="form-label">Número de personas</label>
            <input
                class="form-control @error('numero_personas') is-invalid @enderror"
                id="numero_personas"
                name="numero_personas"
                type="number"
                min="1"
                max="255"
                value="{{ old('numero_personas', $reservation->numero_personas ?? 1) }}"
                required
                aria-describedby="numero_personas-error"
            >
            @error('numero_personas')
                <div class="invalid-feedback" id="numero_personas-error">{{ $message }}</div>
            @enderror
        </div>

        @if ($editing)
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
                            @selected(old('estado', $reservation->estado->value) === $status->value)
                        >
                            {{ str_replace('_', ' ', $status->value) }}
                        </option>
                    @endforeach
                </select>
                @error('estado')
                    <div class="invalid-feedback" id="estado-error">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <div class="col-12">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea
                class="form-control @error('observaciones') is-invalid @enderror"
                id="observaciones"
                name="observaciones"
                rows="3"
                maxlength="2000"
                aria-describedby="observaciones-error"
            >{{ old('observaciones', $reservation->observaciones ?? '') }}</textarea>
            @error('observaciones')
                <div class="invalid-feedback" id="observaciones-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 d-flex flex-wrap gap-2 pt-2">
            <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
            <a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">Cancelar</a>
        </div>
    </div>
</form>
