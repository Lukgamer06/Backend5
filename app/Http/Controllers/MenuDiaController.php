<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Menus\IndexMenuDiaRequest;
use App\Http\Requests\Menus\StoreMenuDiaRequest;
use App\Http\Requests\Menus\UpdateMenuDiaRequest;
use App\Models\MenuDia;
use App\Models\Usuario;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MenuDiaController
{
    public function index(
        IndexMenuDiaRequest $request,
        MenuService $menuService,
    ): View {
        return view('menus.index', [
            'menus' => $menuService->paginate(
                $this->authenticatedUser($request),
                $request->validated(),
            ),
            'filters' => $request->validated(),
            'breadcrumbs' => [
                ['label' => 'Menú del día'],
            ],
        ]);
    }

    public function create(MenuService $menuService): View
    {
        Gate::authorize('create', MenuDia::class);

        return view('menus.create', [
            'products' => $menuService->productsForSelection(),
            'breadcrumbs' => [
                ['label' => 'Menú del día', 'url' => route('menus.index')],
                ['label' => 'Nuevo menú'],
            ],
        ]);
    }

    public function store(
        StoreMenuDiaRequest $request,
        MenuService $menuService,
    ): RedirectResponse {
        $data = $request->validated();
        $menuDia = $menuService->create(
            Carbon::parse($data['fecha']),
            array_map('intval', $data['productos'] ?? []),
        );

        return redirect()
            ->route('menus.edit', $menuDia)
            ->with('status', 'El menú quedó guardado como borrador.');
    }

    public function show(MenuDia $menuDia): View
    {
        Gate::authorize('view', $menuDia);

        return view('menus.show', [
            'menu' => $menuDia->loadMissing('productos'),
            'breadcrumbs' => [
                ['label' => 'Menú del día', 'url' => route('menus.index')],
                ['label' => 'Detalle del menú'],
            ],
        ]);
    }

    public function edit(MenuDia $menuDia, MenuService $menuService): View
    {
        Gate::authorize('update', $menuDia);

        return view('menus.edit', [
            'menu' => $menuDia->loadMissing('productos'),
            'products' => $menuService->productsForSelection($menuDia),
            'breadcrumbs' => [
                ['label' => 'Menú del día', 'url' => route('menus.index')],
                ['label' => 'Editar borrador'],
            ],
        ]);
    }

    public function update(
        UpdateMenuDiaRequest $request,
        MenuDia $menuDia,
        MenuService $menuService,
    ): RedirectResponse {
        $data = $request->validated();
        $menuDia = $menuService->update(
            $menuDia,
            Carbon::parse($data['fecha']),
            array_map('intval', $data['productos'] ?? []),
        );

        return redirect()
            ->route('menus.edit', $menuDia)
            ->with('status', 'El borrador del menú fue actualizado.');
    }

    public function publish(MenuDia $menuDia, MenuService $menuService): RedirectResponse
    {
        Gate::authorize('publish', $menuDia);
        $menuService->publish($menuDia);

        return redirect()
            ->route('menus.show', $menuDia)
            ->with('status', 'El menú fue publicado correctamente.');
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
