<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoMenuDiaEnum;
use App\Models\MenuDia;
use App\Models\Producto;
use App\Models\Usuario;
use App\Repositories\MenuRepository;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuService
{
    public function __construct(
        private readonly MenuRepository $menuRepository,
    ) {}

    /**
     * @param  array{estado?: string, fecha_desde?: string, fecha_hasta?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, MenuDia>
     */
    public function paginate(Usuario $usuario, array $filters): LengthAwarePaginator
    {
        return $this->menuRepository->paginateFor($usuario, $filters);
    }

    public function findByDate(DateTimeInterface $fecha): ?MenuDia
    {
        return $this->menuRepository->findByDate($fecha);
    }

    public function findPublishedByDate(DateTimeInterface $fecha): ?MenuDia
    {
        return $this->menuRepository->findPublishedByDate($fecha);
    }

    /**
     * @return Collection<int, Producto>
     */
    public function productsForSelection(?MenuDia $menuDia = null): Collection
    {
        return $this->menuRepository->productsForSelection($menuDia);
    }

    /**
     * @param  list<int>  $productIds
     */
    public function create(DateTimeInterface $fecha, array $productIds): MenuDia
    {
        return DB::transaction(function () use ($fecha, $productIds): MenuDia {
            $this->validateProductSelection($productIds);

            $menuDia = new MenuDia([
                'fecha' => $fecha,
                'estado' => EstadoMenuDiaEnum::BORRADOR,
            ]);

            $menuDia = $this->menuRepository->save($menuDia);
            $this->menuRepository->syncProducts($menuDia, $productIds);

            return $menuDia->refresh()->load('productos');
        });
    }

    /**
     * @param  list<int>  $productIds
     */
    public function update(
        MenuDia $menuDia,
        DateTimeInterface $fecha,
        array $productIds,
    ): MenuDia {
        return DB::transaction(function () use ($menuDia, $fecha, $productIds): MenuDia {
            $lockedMenu = $this->menuRepository->findForUpdate($menuDia->getKey());

            if (! $lockedMenu instanceof MenuDia) {
                throw new ModelNotFoundException('No se encontró el menú que se desea actualizar.');
            }

            $this->ensureDraft($lockedMenu);
            $this->validateProductSelection($productIds, $lockedMenu);

            $lockedMenu->fecha = $fecha;
            $lockedMenu = $this->menuRepository->save($lockedMenu);
            $this->menuRepository->syncProducts($lockedMenu, $productIds);

            return $lockedMenu->refresh()->load('productos');
        });
    }

    public function publish(MenuDia $menuDia): MenuDia
    {
        return DB::transaction(function () use ($menuDia): MenuDia {
            $lockedMenu = $this->menuRepository->findForUpdate($menuDia->getKey());

            if (! $lockedMenu instanceof MenuDia) {
                throw new ModelNotFoundException('No se encontró el menú que se desea publicar.');
            }

            $this->ensureDraft($lockedMenu);

            if (! $lockedMenu->productos()->exists()) {
                throw ValidationException::withMessages([
                    'productos' => 'Agrega al menos un producto antes de publicar el menú.',
                ]);
            }

            $lockedMenu->estado = EstadoMenuDiaEnum::PUBLICADO;

            return $this->menuRepository->save($lockedMenu);
        });
    }

    private function ensureDraft(MenuDia $menuDia): void
    {
        if ($menuDia->estado !== EstadoMenuDiaEnum::BORRADOR) {
            throw ValidationException::withMessages([
                'menuDia' => 'Solo es posible modificar o publicar menús en estado borrador.',
            ]);
        }
    }

    /**
     * @param  list<int>  $productIds
     */
    private function validateProductSelection(
        array $productIds,
        ?MenuDia $menuDia = null,
    ): void {
        if (count($productIds) !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'productos' => 'No puedes agregar el mismo producto más de una vez.',
            ]);
        }

        $availableIds = $this->menuRepository->lockEligibleProductIds($productIds, $menuDia);

        if (count($availableIds) !== count($productIds)) {
            throw ValidationException::withMessages([
                'productos' => 'Selecciona productos disponibles o que ya pertenezcan a este menú.',
            ]);
        }
    }
}
