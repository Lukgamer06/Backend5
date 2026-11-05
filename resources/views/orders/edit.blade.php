@extends('layouts.app')

@section('title', 'Editar pedido | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Editar pedido #{{ $order->id }}</h1>
        <p class="text-body-secondary mb-0">Actualiza sus productos, datos de atención o avanza su estado.</p>
    </div>

    @include('orders._form', [
        'action' => route('orders.update', $order),
        'httpMethod' => 'PUT',
        'submitLabel' => 'Guardar cambios',
        'cancelUrl' => route('orders.show', $order),
    ])
@endsection
