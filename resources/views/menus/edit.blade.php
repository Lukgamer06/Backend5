@extends('layouts.app')

@section('title', 'Editar borrador | HostalConnect UNAB')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Editar borrador</h1>
        <p class="text-body-secondary mb-0">
            Ajusta la fecha y los productos antes de publicar el menú.
        </p>
    </div>

    @include('menus._form', [
        'menu' => $menu,
        'action' => route('menus.update', $menu),
        'httpMethod' => 'PUT',
        'submitLabel' => 'Guardar cambios',
    ])

    <form
        class="mt-3"
        method="POST"
        action="{{ route('menus.publish', $menu) }}"
        onsubmit="return confirm('¿Publicar el menú? Los cambios quedarán bloqueados.')"
    >
        @csrf
        @method('PATCH')
        <button class="btn btn-success" type="submit">Publicar menú</button>
    </form>
@endsection
