<form method="POST" action="{{ $action }}" class="card card-body">
    @csrf
    @if ($httpMethod !== 'POST')
        @method($httpMethod)
    @endif

    <div class="row g-3">
        <div class="col-12">
            <label for="nombre" class="form-label">Nombre</label>
            <input
                class="form-control @error('nombre') is-invalid @enderror"
                id="nombre"
                name="nombre"
                type="text"
                maxlength="150"
                value="{{ old('nombre', $product->nombre ?? '') }}"
                required
                aria-describedby="nombre-error"
            >
            @error('nombre')
                <div class="invalid-feedback" id="nombre-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea
                class="form-control @error('descripcion') is-invalid @enderror"
                id="descripcion"
                name="descripcion"
                rows="4"
                maxlength="5000"
                aria-describedby="descripcion-error"
            >{{ old('descripcion', $product->descripcion ?? '') }}</textarea>
            @error('descripcion')
                <div class="invalid-feedback" id="descripcion-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 col-md-5">
            <label for="precio" class="form-label">Precio</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input
                    class="form-control @error('precio') is-invalid @enderror"
                    id="precio"
                    name="precio"
                    type="number"
                    min="0.01"
                    max="9999999999.99"
                    step="0.01"
                    value="{{ old('precio', $product->precio ?? '') }}"
                    required
                    aria-describedby="precio-error"
                >
                @error('precio')
                    <div class="invalid-feedback" id="precio-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-text">Usa un valor positivo con máximo dos decimales.</div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2 pt-2">
            <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Cancelar</a>
        </div>
    </div>
</form>
