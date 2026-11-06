@extends('layouts.app')

@section('title', 'Nueva reserva | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Nueva reserva</h1>
        <p class="text-body-secondary mb-0">Completa los datos para registrar una reserva.</p>
    </div>

    @include('reservations._form', [
        'action' => route('reservations.store'),
        'httpMethod' => 'POST',
        'submitLabel' => 'Crear reserva',
        'cancelUrl' => route('reservations.index'),
    ])
@endsection
