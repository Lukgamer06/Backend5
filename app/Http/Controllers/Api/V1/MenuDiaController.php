<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Menus\IndexMenuDiaApiRequest;
use App\Http\Requests\Api\V1\Menus\StoreMenuDiaApiRequest;
use App\Http\Requests\Api\V1\Menus\UpdateMenuDiaApiRequest;
use App\Http\Resources\Api\V1\MenuDiaCollection;
use App\Http\Resources\Api\V1\MenuDiaResource;
use App\Models\MenuDia;
use App\Models\Usuario;
use App\Services\MenuService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class MenuDiaController
{
    public function __construct(
        private readonly MenuService $menuService,
    ) {}

    public function index(IndexMenuDiaApiRequest $request): MenuDiaCollection
    {
        Gate::authorize('viewAny', MenuDia::class);
        $usuario = $request->user();
        if (! $usuario instanceof Usuario) {
            throw new AuthorizationException('Se requiere una sesión autenticada.');
        }

        return MenuDiaCollection::make(
            $this->menuService->paginate($usuario, $request->validated()),
        );
    }

    public function actual(): MenuDiaResource
    {
        $menuDia = $this->menuService->findPublishedByDate(Carbon::today());

        if (! $menuDia instanceof MenuDia) {
            throw (new ModelNotFoundException)->setModel(MenuDia::class);
        }

        Gate::authorize('view', $menuDia);

        return MenuDiaResource::make($menuDia)
            ->withMessage('Menú del día consultado correctamente.');
    }

    public function store(StoreMenuDiaApiRequest $request): JsonResponse
    {
        Gate::authorize('create', MenuDia::class);
        $data = $request->validated();
        $menuDia = $this->menuService->create(
            Carbon::createFromFormat('Y-m-d', $data['fecha']),
            array_map('intval', $data['productos'] ?? []),
        );

        return MenuDiaResource::make($menuDia)
            ->withMessage('Menú guardado como borrador.')
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateMenuDiaApiRequest $request,
        MenuDia $menuDia,
    ): MenuDiaResource {
        Gate::authorize('update', $menuDia);
        $data = $request->validated();
        $menuDia->loadMissing('productos');
        $fecha = Carbon::createFromFormat(
            'Y-m-d',
            $data['fecha'] ?? $menuDia->fecha->format('Y-m-d'),
        );
        $productos = array_key_exists('productos', $data)
            ? array_map('intval', $data['productos'] ?? [])
            : $menuDia->productos->modelKeys();

        $menuActualizado = $this->menuService->update($menuDia, $fecha, $productos);

        return MenuDiaResource::make($menuActualizado)
            ->withMessage('Borrador del menú actualizado correctamente.');
    }

    public function publish(Request $request, MenuDia $menuDia): MenuDiaResource
    {
        Gate::authorize('publish', $menuDia);

        return MenuDiaResource::make($this->menuService->publish($menuDia))
            ->withMessage('Menú publicado correctamente.');
    }
}
