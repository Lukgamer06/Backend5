@extends('layouts.app')

@section('title', 'Panel principal | HostalConnect UNAB')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Panel principal</h1>
        <p class="text-body-secondary mb-0">
            Resumen operativo para {{ now()->translatedFormat('l, d \d\e F') }}.
        </p>
    </div>
</div>

<section aria-label="Indicadores principales" class="mb-4">
    <div class="row g-3">
        @foreach ($metrics as $metric)
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card h-100 border-0 shadow-sm border-start border-4 border-{{ $metric['color'] }}">
                    <div class="card-body">
                        <p class="text-body-secondary small mb-2">{{ $metric['label'] }}</p>
                        <p class="display-6 fw-semibold mb-0">{{ number_format($metric['value']) }}</p>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</section>

<section class="row g-3 mb-4" aria-label="Actividad de los últimos siete días">
    <div class="col-12 col-xl-6">
        <article class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Reservas recientes</h2>
                <p class="text-body-secondary small">Registros creados durante los últimos siete días.</p>
                <div class="d-flex align-items-end gap-2" style="height: 10rem">
                    @foreach ($reservationActivity as $day)
                        <div class="d-flex h-100 flex-fill flex-column align-items-center justify-content-end gap-2">
                            <span class="small text-body-secondary">{{ $day['count'] }}</span>
                            <div
                                class="w-100 rounded-top bg-primary"
                                style="height: {{ $day['height'] }}%; min-height: {{ $day['count'] > 0 ? '0.5rem' : '0' }}"
                                role="img"
                                aria-label="{{ $day['label'] }}: {{ $day['count'] }} reservas"
                            ></div>
                            <span class="small text-body-secondary">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    </div>

    <div class="col-12 col-xl-6">
        <article class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Pedidos recientes</h2>
                <p class="text-body-secondary small">Registros creados durante los últimos siete días.</p>
                <div class="d-flex align-items-end gap-2" style="height: 10rem">
                    @foreach ($orderActivity as $day)
                        <div class="d-flex h-100 flex-fill flex-column align-items-center justify-content-end gap-2">
                            <span class="small text-body-secondary">{{ $day['count'] }}</span>
                            <div
                                class="w-100 rounded-top bg-success"
                                style="height: {{ $day['height'] }}%; min-height: {{ $day['count'] > 0 ? '0.5rem' : '0' }}"
                                role="img"
                                aria-label="{{ $day['label'] }}: {{ $day['count'] }} pedidos"
                            ></div>
                            <span class="small text-body-secondary">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    </div>
</section>

<section class="row g-3" aria-label="Registros recientes">
    <div class="col-12 col-xl-6">
        <article class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0">{{ auth()->user()->rol->value === 'CLIENTE' ? 'Mis últimas reservas' : 'Últimas reservas' }}</h2>
            </div>
            @if ($recentReservations->isEmpty())
                <div class="card-body">
                    <p class="text-body-secondary mb-0">Todavía no hay reservas para mostrar.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            @unless (auth()->user()->rol->value === 'CLIENTE')
                                <th scope="col">Cliente</th>
                            @endunless
                            <th scope="col">Mesa</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Estado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($recentReservations as $reserva)
                            <tr>
                                @unless (auth()->user()->rol->value === 'CLIENTE')
                                    <td>{{ $reserva->cliente->nombres }} {{ $reserva->cliente->apellidos }}</td>
                                @endunless
                                <td>{{ $reserva->mesa->numero }}</td>
                                <td>{{ $reserva->fecha_inicio->format('d/m/Y H:i') }}</td>
                                <td><span class="badge text-bg-light">{{ $reserva->estado->value }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </div>

    <div class="col-12 col-xl-6">
        <article class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0">{{ auth()->user()->rol->value === 'CLIENTE' ? 'Mis últimos pedidos' : 'Últimos pedidos' }}</h2>
            </div>
            @if ($recentOrders->isEmpty())
                <div class="card-body">
                    <p class="text-body-secondary mb-0">Todavía no hay pedidos para mostrar.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            @unless (auth()->user()->rol->value === 'CLIENTE')
                                <th scope="col">Cliente</th>
                            @endunless
                            <th scope="col">Modalidad</th>
                            <th scope="col">Total</th>
                            <th scope="col">Estado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($recentOrders as $pedido)
                            <tr>
                                @unless (auth()->user()->rol->value === 'CLIENTE')
                                    <td>{{ $pedido->cliente->nombres }} {{ $pedido->cliente->apellidos }}</td>
                                @endunless
                                <td>{{ str_replace('_', ' ', $pedido->modalidad_servicio->value) }}</td>
                                <td>${{ number_format((float) $pedido->total, 2, ',', '.') }}</td>
                                <td><span class="badge text-bg-light">{{ str_replace('_', ' ', $pedido->estado->value) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </div>

    @if ($recentPayments->isNotEmpty())
        <div class="col-12 col-xl-6">
            <article class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h2 class="h5 mb-0">Últimos pagos</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th scope="col">Referencia</th>
                            <th scope="col">Monto</th>
                            <th scope="col">Estado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($recentPayments as $pago)
                            <tr>
                                <td>{{ $pago->referencia_operacion }}</td>
                                <td>${{ number_format((float) $pago->monto, 2, ',', '.') }}</td>
                                <td><span class="badge text-bg-light">{{ $pago->estado->value }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    @endif

    @if ($recentIncidents->isNotEmpty())
        <div class="col-12 col-xl-6">
            <article class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h2 class="h5 mb-0">Últimas incidencias</h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th scope="col">Incidencia</th>
                            <th scope="col">Prioridad</th>
                            <th scope="col">Estado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($recentIncidents as $incidencia)
                            <tr>
                                <td>{{ $incidencia->titulo }}</td>
                                <td><span class="badge text-bg-light">{{ $incidencia->prioridad->value }}</span></td>
                                <td><span class="badge text-bg-light">{{ str_replace('_', ' ', $incidencia->estado->value) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    @endif
</section>
@endsection
