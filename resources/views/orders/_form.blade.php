@php
    $editing = isset($order);
    $lines = old('lineas', $initialLines ?? [
        ['producto_id' => '', 'cantidad' => 1, 'observaciones' => ''],
    ]);
    $fechaProgramada = old(
        'fecha_programada',
        $editing && $order->fecha_programada
            ? $order->fecha_programada->format('Y-m-d\TH:i')
            : '',
    );
@endphp

<form method="POST" action="{{ $action }}" class="card card-body" id="order-form">
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
                            @selected((string) old('cliente_id', $order->cliente_id ?? '') === (string) $cliente->id)
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
            <label for="tipo_atencion" class="form-label">Tipo de atención</label>
            <select
                class="form-select @error('tipo_atencion') is-invalid @enderror"
                id="tipo_atencion"
                name="tipo_atencion"
                required
            >
                @foreach ($attentionTypes as $type)
                    <option
                        value="{{ $type->value }}"
                        @selected(old('tipo_atencion', $order->tipo_atencion->value ?? '') === $type->value)
                    >
                        {{ str_replace('_', ' ', $type->value) }}
                    </option>
                @endforeach
            </select>
            @error('tipo_atencion')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label for="modalidad_servicio" class="form-label">Modalidad de servicio</label>
            <select
                class="form-select @error('modalidad_servicio') is-invalid @enderror"
                id="modalidad_servicio"
                name="modalidad_servicio"
                required
            >
                @foreach ($modalities as $modality)
                    <option
                        value="{{ $modality->value }}"
                        @selected(old('modalidad_servicio', $order->modalidad_servicio->value ?? '') === $modality->value)
                    >
                        {{ str_replace('_', ' ', $modality->value) }}
                    </option>
                @endforeach
            </select>
            @error('modalidad_servicio')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6" data-order-field="fecha-programada">
            <label for="fecha_programada" class="form-label">Fecha programada</label>
            <input
                class="form-control @error('fecha_programada') is-invalid @enderror"
                id="fecha_programada"
                name="fecha_programada"
                type="datetime-local"
                value="{{ $fechaProgramada }}"
                aria-describedby="fecha_programada-error"
            >
            @error('fecha_programada')
                <div class="invalid-feedback" id="fecha_programada-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-6" data-order-field="mesa">
            <label for="mesa_id" class="form-label">Mesa</label>
            <select
                class="form-select @error('mesa_id') is-invalid @enderror"
                id="mesa_id"
                name="mesa_id"
                aria-describedby="mesa_id-error"
            >
                <option value="">Selecciona una mesa</option>
                @foreach ($mesas as $mesa)
                    <option
                        value="{{ $mesa->id }}"
                        @selected((string) old('mesa_id', $order->mesa_id ?? '') === (string) $mesa->id)
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

        <div class="col-12 col-md-6" data-order-field="reserva">
            <label for="reserva_id" class="form-label">Reserva relacionada (opcional)</label>
            <select
                class="form-select @error('reserva_id') is-invalid @enderror"
                id="reserva_id"
                name="reserva_id"
                aria-describedby="reserva_id-error"
            >
                <option value="">Sin reserva relacionada</option>
                @foreach ($reservas as $reservation)
                    <option
                        value="{{ $reservation->id }}"
                        data-client-id="{{ $reservation->cliente_id }}"
                        data-table-id="{{ $reservation->mesa_id }}"
                        @selected((string) old('reserva_id', $order->reserva_id ?? '') === (string) $reservation->id)
                    >
                        #{{ $reservation->id }} ·
                        {{ $reservation->cliente->nombres }} {{ $reservation->cliente->apellidos }} ·
                        {{ $reservation->fecha_inicio->format('d/m/Y H:i') }} ·
                        {{ $reservation->estado->value }}
                    </option>
                @endforeach
            </select>
            @error('reserva_id')
                <div class="invalid-feedback" id="reserva_id-error">{{ $message }}</div>
            @enderror
        </div>

        @if ($editing)
            <div class="col-12 col-md-6">
                <label for="estado" class="form-label">Siguiente estado</label>
                <select
                    class="form-select @error('estado') is-invalid @enderror"
                    id="estado"
                    name="estado"
                    required
                    aria-describedby="estado-error"
                >
                    <option value="{{ $order->estado->value }}" @selected(old('estado', $order->estado->value) === $order->estado->value)>
                        Mantener: {{ str_replace('_', ' ', $order->estado->value) }}
                    </option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('estado') === $status->value)>
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
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div>
                    <h2 class="h5 mb-0">Productos</h2>
                    <p class="small text-body-secondary mb-0">Los precios se calculan y guardan al enviar el pedido.</p>
                </div>
                <button class="btn btn-sm btn-outline-primary" type="button" id="add-order-line">
                    Agregar producto
                </button>
            </div>
            @error('lineas')
                <div class="alert alert-danger py-2">{{ $message }}</div>
            @enderror
            <div id="order-lines" class="d-grid gap-3">
                @foreach ($lines as $index => $line)
                    <div class="row g-2 align-items-end order-line border rounded p-2 mx-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="linea-{{ $index }}-producto">Producto</label>
                            <select
                                class="form-select @error("lineas.$index.producto_id") is-invalid @enderror"
                                id="linea-{{ $index }}-producto"
                                name="lineas[{{ $index }}][producto_id]"
                                required
                            >
                                <option value="">Selecciona un producto</option>
                                @foreach ($productos as $producto)
                                    <option
                                        value="{{ $producto->id }}"
                                        @selected((string) ($line['producto_id'] ?? '') === (string) $producto->id)
                                    >
                                        {{ $producto->nombre }} · ${{ number_format((float) $producto->precio, 2, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            @error("lineas.$index.producto_id")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label" for="linea-{{ $index }}-cantidad">Cantidad</label>
                            <input
                                class="form-control @error("lineas.$index.cantidad") is-invalid @enderror"
                                id="linea-{{ $index }}-cantidad"
                                name="lineas[{{ $index }}][cantidad]"
                                type="number"
                                min="1"
                                max="65535"
                                value="{{ $line['cantidad'] ?? 1 }}"
                                required
                            >
                            @error("lineas.$index.cantidad")
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="linea-{{ $index }}-observaciones">Nota del producto</label>
                            <div class="input-group">
                                <input
                                    class="form-control"
                                    id="linea-{{ $index }}-observaciones"
                                    name="lineas[{{ $index }}][observaciones]"
                                    type="text"
                                    maxlength="1000"
                                    value="{{ $line['observaciones'] ?? '' }}"
                                >
                                <button
                                    class="btn btn-outline-danger remove-order-line"
                                    type="button"
                                    aria-label="Quitar producto"
                                    @disabled(count($lines) === 1)
                                >Quitar</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2 pt-2">
            <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
            <a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">Cancelar</a>
        </div>
    </div>
</form>

<template id="order-line-template">
    <div class="row g-2 align-items-end order-line border rounded p-2 mx-0">
        <div class="col-12 col-md-6">
            <label class="form-label" for="linea-__INDEX__-producto">Producto</label>
            <select class="form-select" id="linea-__INDEX__-producto" name="lineas[__INDEX__][producto_id]" required>
                <option value="">Selecciona un producto</option>
                @foreach ($productos as $producto)
                    <option value="{{ $producto->id }}">
                        {{ $producto->nombre }} · ${{ number_format((float) $producto->precio, 2, ',', '.') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="linea-__INDEX__-cantidad">Cantidad</label>
            <input
                class="form-control"
                id="linea-__INDEX__-cantidad"
                name="lineas[__INDEX__][cantidad]"
                type="number"
                min="1"
                max="65535"
                value="1"
                required
            >
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label" for="linea-__INDEX__-observaciones">Nota del producto</label>
            <div class="input-group">
                <input
                    class="form-control"
                    id="linea-__INDEX__-observaciones"
                    name="lineas[__INDEX__][observaciones]"
                    type="text"
                    maxlength="1000"
                >
                <button class="btn btn-outline-danger remove-order-line" type="button" aria-label="Quitar producto">
                    Quitar
                </button>
            </div>
        </div>
    </div>
</template>

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('order-form');
            const lines = document.getElementById('order-lines');
            const template = document.getElementById('order-line-template');
            const addButton = document.getElementById('add-order-line');
            const attention = document.getElementById('tipo_atencion');
            const modality = document.getElementById('modalidad_servicio');
            const client = document.getElementById('cliente_id');
            const reservations = document.getElementById('reserva_id');
            let nextIndex = lines.querySelectorAll('.order-line').length;

            const updateFields = () => {
                const scheduled = attention.value === 'PROGRAMADO';
                const tableService = modality.value === 'EN_MESA';
                const dateField = form.querySelector('[data-order-field="fecha-programada"]');
                const tableField = form.querySelector('[data-order-field="mesa"]');
                const reservationField = form.querySelector('[data-order-field="reserva"]');
                const date = document.getElementById('fecha_programada');
                const table = document.getElementById('mesa_id');

                dateField.hidden = !scheduled;
                date.disabled = !scheduled;
                date.required = scheduled;
                if (!scheduled) date.value = '';

                tableField.hidden = !tableService;
                table.disabled = !tableService;
                table.required = tableService;
                if (!tableService) table.value = '';

                reservationField.hidden = !tableService;
                reservations.disabled = !tableService;
                if (!tableService) reservations.value = '';
            };

            const updateReservationOptions = () => {
                if (!client || !reservations) return;
                for (const option of reservations.options) {
                    if (!option.value) continue;
                    option.hidden = client.value !== '' && option.dataset.clientId !== client.value;
                    if (option.selected && option.hidden) reservations.value = '';
                }
            };

            addButton.addEventListener('click', () => {
                if (lines.querySelectorAll('.order-line').length >= 30) return;
                const fragment = template.content.cloneNode(true);
                for (const element of fragment.querySelectorAll('*')) {
                    if (element.id) element.id = element.id.replaceAll('__INDEX__', String(nextIndex));
                    if (element.htmlFor) element.htmlFor = element.htmlFor.replaceAll('__INDEX__', String(nextIndex));
                    if (element.name) element.name = element.name.replaceAll('__INDEX__', String(nextIndex));
                }
                lines.append(fragment);
                nextIndex += 1;
            });

            lines.addEventListener('click', (event) => {
                if (!event.target.closest('.remove-order-line')) return;
                if (lines.querySelectorAll('.order-line').length <= 1) return;
                event.target.closest('.order-line').remove();
            });

            attention.addEventListener('change', updateFields);
            modality.addEventListener('change', updateFields);
            client?.addEventListener('change', updateReservationOptions);
            updateFields();
            updateReservationOptions();
        })();
    </script>
@endpush
