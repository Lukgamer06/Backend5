<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Products\IndexProductRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ProductController
{
    public function index(
        IndexProductRequest $request,
        CatalogService $catalogService,
    ): View {
        return view('products.index', [
            'products' => $catalogService->paginate(
                $this->authenticatedUser($request),
                $request->validated(),
            ),
            'filters' => $request->validated(),
            'breadcrumbs' => [
                ['label' => 'Productos'],
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Producto::class);

        return view('products.create', [
            'breadcrumbs' => [
                ['label' => 'Productos', 'url' => route('products.index')],
                ['label' => 'Nuevo producto'],
            ],
        ]);
    }

    public function store(StoreProductRequest $request, CatalogService $catalogService): RedirectResponse
    {
        $producto = $catalogService->create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', "El producto «{$producto->nombre}» fue creado correctamente.");
    }

    public function edit(Producto $producto): View
    {
        Gate::authorize('update', $producto);

        return view('products.edit', [
            'product' => $producto,
            'breadcrumbs' => [
                ['label' => 'Productos', 'url' => route('products.index')],
                ['label' => 'Editar producto'],
            ],
        ]);
    }

    public function update(
        UpdateProductRequest $request,
        Producto $producto,
        CatalogService $catalogService,
    ): RedirectResponse {
        $producto = $catalogService->update($producto, $request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', "El producto «{$producto->nombre}» fue actualizado correctamente.");
    }

    public function activate(Producto $producto, CatalogService $catalogService): RedirectResponse
    {
        Gate::authorize('activate', $producto);
        $catalogService->activate($producto);

        return redirect()
            ->route('products.index')
            ->with('status', "El producto «{$producto->nombre}» está activo.");
    }

    public function deactivate(Producto $producto, CatalogService $catalogService): RedirectResponse
    {
        Gate::authorize('deactivate', $producto);
        $catalogService->deactivate($producto);

        return redirect()
            ->route('products.index')
            ->with('status', "El producto «{$producto->nombre}» fue desactivado.");
    }

    private function authenticatedUser(Request $request): Usuario
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new UnauthorizedHttpException('web');
        }

        return $usuario;
    }
}
