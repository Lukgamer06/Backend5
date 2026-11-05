@extends('layouts.app')

@section('title', 'Pagos | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Pagos</h1>
        <p class="text-body-secondary mb-0">Consulta el historial de pagos registrado en el sistema.</p>
    </div>

    <form method="GET" action="{{ route('payments.index') }}" class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label">Referencia, pedido o cliente</label>
                <input
                    class="form-control"
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $filters['search'] ?? '' }}"
                    maxlength="100"
                >
            </div>
            <div class="col-6 col-md-2">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\EstadoPagoEnum::cases() as $estado)
                        <option value="{{ $estado->value }}" @selected(($filters['estado'] ?? '') === $estado->value)>
                            {{ str_replace('_', ' ', $estado->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="canal" class="form-label">Canal</label>
                <select class="form-select" id="canal" name="canal">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\CanalPagoEnum::cases() as $canal)
                        <option value="{{ $canal->value }}" @selected(($filters['canal'] ?? '') === $canal->value)>
                            {{ str_replace('_', ' ', $canal->value) }}
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
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="{{ route('payments.index') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Referencia</th>
                        <th scope="col">Pedido / cliente</th>
                        <th scope="col">Canal</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Monto</th>
                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="text-nowrap">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                            <th scope="row">{{ $payment->referencia_operacion }}</th>
                            <td>
                                <span class="d-block">Pedido #{{ $payment->pedido->id }}</span>
                                <span class="small text-body-secondary">
                                    {{ $payment->pedido->cliente->nombres }} {{ $payment->pedido->cliente->apellidos }}
                                </span>
                            </td>
                            <td>{{ str_replace('_', ' ', $payment->canal->value) }}</td>
                            <td>
                                <span class="badge {{ $payment->estado === \App\Enums\EstadoPagoEnum::APROBADO ? 'text-bg-success' : ($payment->estado === \App\Enums\EstadoPagoEnum::RECHAZADO ? 'text-bg-danger' : 'text-bg-secondary') }}">
                                    {{ str_replace('_', ' ', $payment->estado->value) }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">${{ number_format((float) $payment->monto, 2, ',', '.') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('payments.show', $payment) }}">
                                    Historial
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-5 text-center text-body-secondary" colspan="7">
                                <span class="d-block fw-semibold">No hay pagos registrados</span>
                                No hay pagos que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="card-footer">{{ $payments->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
