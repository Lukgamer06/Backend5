@extends('layouts.app')

@section('title', 'Detalle de pedido | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Pedido #{{ $order->id }}</h1>
            <p class="text-body-secondary mb-0">Registrado {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('orders.index') }}">Volver</a>
            @can('update', $order)
                <a class="btn btn-primary" href="{{ route('orders.edit', $order) }}">Editar pedido</a>
            @endcan
            @can('cancel', $order)
                @if ($order->estado === \App\Enums\EstadoPedidoEnum::REGISTRADO)
                    <form
                        method="POST"
                        action="{{ route('orders.cancel', $order) }}"
                        data-confirm="¿Deseas cancelar este pedido?"
                        data-loading-text="Cancelando..."
                    >
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-outline-danger" type="submit">Cancelar pedido</button>
                    </form>
                @endif
            @endcan
            @can('delete', $order)
                @if ($order->pagos->isEmpty())
                    <form
                        method="POST"
                        action="{{ route('orders.destroy', $order) }}"
                        data-confirm="¿Deseas eliminar este pedido y sus detalles?"
                        data-loading-text="Eliminando..."
                    >
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Eliminar pedido</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header bg-body fw-semibold">Datos del pedido</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Cliente</dt>
                        <dd class="col-7">
                            {{ $order->cliente->nombres }} {{ $order->cliente->apellidos }}
                        </dd>
                        <dt class="col-5">Estado</dt>
                        <dd class="col-7">
                            <span class="badge text-bg-secondary">{{ str_replace('_', ' ', $order->estado->value) }}</span>
                        </dd>
                        <dt class="col-5">Atención</dt>
                        <dd class="col-7">{{ str_replace('_', ' ', $order->tipo_atencion->value) }}</dd>
                        <dt class="col-5">Servicio</dt>
                        <dd class="col-7">{{ str_replace('_', ' ', $order->modalidad_servicio->value) }}</dd>
                        @if ($order->fecha_programada)
                            <dt class="col-5">Programado</dt>
                            <dd class="col-7">{{ $order->fecha_programada->format('d/m/Y H:i') }}</dd>
                        @endif
                        @if ($order->mesa)
                            <dt class="col-5">Mesa</dt>
                            <dd class="col-7">{{ $order->mesa->numero }}</dd>
                        @endif
                        @if ($order->reserva)
                            <dt class="col-5">Reserva</dt>
                            <dd class="col-7">
                                <a href="{{ route('reservations.show', $order->reserva) }}">
                                    #{{ $order->reserva->id }}
                                </a>
                            </dd>
                        @endif
                        <dt class="col-5">Registrado por</dt>
                        <dd class="col-7">{{ $order->usuario->nombres }} {{ $order->usuario->apellidos }}</dd>
                    </dl>
                </div>
                @can('changeStatus', $order)
                    @if ($availableTransitions !== [])
                        <div class="card-footer">
                            <form method="POST" action="{{ route('orders.status', $order) }}" class="d-flex gap-2">
                                @csrf
                                @method('PATCH')
                                <label class="visually-hidden" for="estado">Cambiar estado</label>
                                <select class="form-select" id="estado" name="estado" required>
                                    @foreach ($availableTransitions as $status)
                                        <option value="{{ $status->value }}">
                                            {{ str_replace('_', ' ', $status->value) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-primary text-nowrap" type="submit">Actualizar</button>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header bg-body fw-semibold">Detalle de productos</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Cantidad</th>
                                <th scope="col">Precio unitario</th>
                                <th scope="col">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->detalles as $detail)
                                <tr>
                                    <td>
                                        <span class="d-block">{{ $detail->producto->nombre }}</span>
                                        @if ($detail->observaciones)
                                            <span class="small text-body-secondary">{{ $detail->observaciones }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->cantidad }}</td>
                                    <td>${{ number_format((float) $detail->precio_unitario, 2, ',', '.') }}</td>
                                    <td>${{ number_format((float) $detail->subtotal, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-end" colspan="3">Total</th>
                                <th>${{ number_format((float) $order->total, 2, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
