@extends('layouts.app')

@section('title', 'Nuevo producto | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Nuevo producto</h1>
        <p class="text-body-secondary mb-0">Agrega un producto al catálogo. Quedará activo al crearse.</p>
    </div>

    @include('products._form', [
        'action' => route('products.store'),
        'httpMethod' => 'POST',
        'submitLabel' => 'Crear producto',
    ])
@endsection
