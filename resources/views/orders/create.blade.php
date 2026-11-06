@extends('layouts.app')

@section('title', 'Nuevo pedido | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Nuevo pedido</h1>
        <p class="text-body-secondary mb-0">Registra la atención y agrega los productos solicitados.</p>
    </div>

    @include('orders._form', [
        'action' => route('orders.store'),
        'httpMethod' => 'POST',
        'submitLabel' => 'Registrar pedido',
        'cancelUrl' => route('orders.index'),
    ])
@endsection
