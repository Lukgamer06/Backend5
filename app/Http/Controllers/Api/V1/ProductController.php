<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Products\IndexProductApiRequest;
use App\Http\Resources\Api\V1\ProductCollection;
use App\Http\Resources\Api\V1\ProductoResource;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\CatalogService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController
{
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    public function index(IndexProductApiRequest $request): ProductCollection
    {
        $usuario = $this->authenticatedUser($request);
        Gate::authorize('viewAny', Producto::class);

        return ProductCollection::make(
            $this->catalogService->paginate($usuario, $request->validated()),
        );
    }

    public function show(Request $request, int $producto): ProductoResource
    {
        $usuario = $this->authenticatedUser($request);
        $productoEncontrado = $this->catalogService->findProductFor($usuario, $producto);

        if (! $productoEncontrado instanceof Producto) {
            throw (new ModelNotFoundException)->setModel(Producto::class, [$producto]);
        }

        Gate::authorize('view', $productoEncontrado);

        return ProductoResource::make($productoEncontrado);
    }

    private function authenticatedUser(Request $request): Usuario
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new AuthenticationException;
        }

        return $usuario;
    }
}
