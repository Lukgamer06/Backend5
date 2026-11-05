@extends('layouts.app')

@section('title', 'Historial del pago | HostalConnect UNAB')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Historial del pago</h1>
            <p class="text-body-secondary mb-0">{{ $payment->referencia_operacion }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('payments.index') }}">Volver al historial</a>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">Pago #{{ $payment->id }}</span>
            <span class="badge {{ $payment->estado === \App\Enums\EstadoPagoEnum::APROBADO ? 'text-bg-success' : ($payment->estado === \App\Enums\EstadoPagoEnum::RECHAZADO ? 'text-bg-danger' : 'text-bg-secondary') }}">
                {{ str_replace('_', ' ', $payment->estado->value) }}
            </span>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Monto</dt>
                <dd class="col-sm-8">${{ number_format((float) $payment->monto, 2, ',', '.') }}</dd>

                <dt class="col-sm-4">Canal</dt>
                <dd class="col-sm-8">{{ str_replace('_', ' ', $payment->canal->value) }}</dd>

                <dt class="col-sm-4">Referencia de operación</dt>
                <dd class="col-sm-8 text-break">{{ $payment->referencia_operacion }}</dd>

                <dt class="col-sm-4">Pedido</dt>
                <dd class="col-sm-8">
                    <a href="{{ route('orders.show', $payment->pedido) }}">Pedido #{{ $payment->pedido->id }}</a>
                </dd>

                <dt class="col-sm-4">Cliente</dt>
                <dd class="col-sm-8">
                    {{ $payment->pedido->cliente->nombres }} {{ $payment->pedido->cliente->apellidos }}
                </dd>

                <dt class="col-sm-4">Registrado</dt>
                <dd class="col-sm-8">{{ $payment->created_at->format('d/m/Y H:i:s') }}</dd>

                @if ($payment->proveedor)
                    <dt class="col-sm-4">Proveedor</dt>
                    <dd class="col-sm-8">{{ $payment->proveedor }}</dd>
                @endif

                @if ($payment->transaccion_externa)
                    <dt class="col-sm-4">Transacción externa</dt>
                    <dd class="col-sm-8 text-break">{{ $payment->transaccion_externa }}</dd>
                @endif
            </dl>
        </div>
    </div>

    <p class="small text-body-secondary mt-3 mb-0">
        Esta pantalla es de consulta. No se realizan operaciones de cobro ni cambios en el estado del pago.
    </p>
@endsection
