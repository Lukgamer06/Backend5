@php
    $selectedProductIds = old(
        'productos',
        isset($menu) ? $menu->productos->modelKeys() : [],
    );
    $selectedProductIds = array_map('strval', $selectedProductIds);
@endphp

<form method="POST" action="{{ $action }}" class="card card-body">
    @csrf
    @if ($httpMethod !== 'POST')
        @method($httpMethod)
    @endif

    <div class="row g-3">
        <div class="col-12 col-md-5">
            <label for="fecha" class="form-label">Fecha del menú</label>
            <input
                class="form-control @error('fecha') is-invalid @enderror"
                id="fecha"
                name="fecha"
                type="date"
                value="{{ old('fecha', isset($menu) ? $menu->fecha->format('Y-m-d') : '') }}"
                required
                aria-describedby="fecha-error"
            >
            @error('fecha')
                <div class="invalid-feedback" id="fecha-error">{{ $message }}</div>
            @enderror
        </div>

        <fieldset class="col-12">
            <legend class="h6">Productos disponibles</legend>
            <p class="small text-body-secondary">
                Puedes guardar el borrador sin productos; se requiere al menos uno para publicarlo.
            </p>
            @error('productos')
                <div class="alert alert-danger py-2" role="alert">{{ $message }}</div>
            @enderror

            @if ($products->isEmpty())
                <div class="alert alert-warning mb-0" role="status">
                    No hay productos disponibles para agregar al menú.
                </div>
            @else
                <div class="row g-2">
                    @foreach ($products as $product)
                        <div class="col-12 col-md-6 col-xl-4">
                            <label class="form-check border rounded p-3 h-100" for="producto-{{ $product->id }}">
                                <input
                                    class="form-check-input @error('productos.*') is-invalid @enderror"
                                    id="producto-{{ $product->id }}"
                                    name="productos[]"
                                    type="checkbox"
                                    value="{{ $product->id }}"
                                    @checked(in_array((string) $product->id, $selectedProductIds, true))
                                >
                                <span class="form-check-label ms-1">
                                    <span class="d-block fw-semibold">{{ $product->nombre }}</span>
                                    <span class="d-block small text-body-secondary">
                                        ${{ number_format((float) $product->precio, 2, ',', '.') }}
                                        @if (! $product->disponible)
                                            · Ya no disponible
                                        @endif
                                    </span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif
            @error('productos.*')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
        </fieldset>

        <div class="col-12 d-flex flex-wrap gap-2 pt-2">
            <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('menus.index') }}">Cancelar</a>
        </div>
    </div>
</form>
