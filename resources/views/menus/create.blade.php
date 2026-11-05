@extends('layouts.app')

@section('title', 'Crear borrador | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Crear borrador de menú</h1>
        <p class="text-body-secondary mb-0">Define la fecha y selecciona los productos que ofrecerás.</p>
    </div>

    @include('menus._form', [
        'action' => route('menus.store'),
        'httpMethod' => 'POST',
        'submitLabel' => 'Guardar borrador',
    ])
@endsection
