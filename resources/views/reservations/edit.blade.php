@extends('layouts.app')

@section('title', 'Editar reserva | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Editar reserva</h1>
        <p class="text-body-secondary mb-0">Actualiza los datos de la reserva.</p>
    </div>

    @include('reservations._form', [
        'action' => route('reservations.update', $reservation),
        'httpMethod' => 'PUT',
        'submitLabel' => 'Guardar cambios',
        'cancelUrl' => route('reservations.show', $reservation),
    ])
@endsection
