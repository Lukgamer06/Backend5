@extends('layouts.app')

@section('title', 'Editar producto | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Editar producto</h1>
        <p class="text-body-secondary mb-0">
            Actualiza los datos de {{ $product->nombre }}. La disponibilidad se administra desde el catálogo.
        </p>
    </div>

    @include('products._form', [
        'product' => $product,
        'action' => route('products.update', $product),
        'httpMethod' => 'PUT',
        'submitLabel' => 'Guardar cambios',
    ])
@endsection
