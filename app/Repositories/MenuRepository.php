<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\RolUsuarioEnum;
use App\Models\MenuDia;
use App\Models\Producto;
use App\Models\Usuario;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class MenuRepository
{
    /**
     * @param  array{estado?: string, fecha_desde?: string, fecha_hasta?: string}  $filters
     * @return LengthAwarePaginator<int, MenuDia>
     */
    public function paginateFor(Usuario $usuario, array $filters): LengthAwarePaginator
    {
        return MenuDia::query()
            ->with('productos')
            ->when(
                $usuario->rol === RolUsuarioEnum::CLIENTE,
                fn (Builder $query): Builder => $query->where('estado', 'PUBLICADO'),
            )
            ->when(
                $usuario->rol !== RolUsuarioEnum::CLIENTE && ($filters['estado'] ?? null),
                fn (Builder $query, string $estado): Builder => $query->where('estado', $estado),
            )
            ->when(
                $filters['fecha_desde'] ?? null,
                fn (Builder $query, string $fecha): Builder => $query->whereDate(
                    'fecha',
                    '>=',
                    $fecha,
                ),
            )
            ->when(
                $filters['fecha_hasta'] ?? null,
                fn (Builder $query, string $fecha): Builder => $query->whereDate(
                    'fecha',
                    '<=',
                    $fecha,
                ),
            )
            ->orderByDesc('fecha')
            ->paginate(15)
            ->withQueryString();
    }

    public function findByDate(DateTimeInterface $fecha): ?MenuDia
    {
        return MenuDia::query()
            ->with('productos')
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->first();
    }

    public function findForUpdate(int $menuDiaId): ?MenuDia
    {
        return MenuDia::query()->lockForUpdate()->find($menuDiaId);
    }

    /**
     * @return Collection<int, Producto>
     */
    public function productsForSelection(?MenuDia $menuDia = null): Collection
    {
        return Producto::query()
            ->when(
                $menuDia instanceof MenuDia,
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query
                        ->where('disponible', true)
                        ->orWhereHas(
                            'menusDia',
                            fn (Builder $menuQuery): Builder => $menuQuery->whereKey(
                                $menuDia->getKey(),
                            ),
                        ),
                ),
                fn (Builder $query): Builder => $query->where('disponible', true),
            )
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @param  list<int>  $productIds
     * @return list<int>
     */
    public function lockEligibleProductIds(array $productIds, ?MenuDia $menuDia = null): array
    {
        if ($productIds === []) {
            return [];
        }

        return Producto::query()
            ->whereIn('id', $productIds)
            ->where(
                fn (Builder $query): Builder => $query
                    ->where('disponible', true)
                    ->when(
                        $menuDia instanceof MenuDia,
                        fn (Builder $query): Builder => $query->orWhereHas(
                            'menusDia',
                            fn (Builder $menuQuery): Builder => $menuQuery->whereKey(
                                $menuDia->getKey(),
                            ),
                        ),
                    ),
            )
            ->lockForUpdate()
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();
    }

    public function save(MenuDia $menuDia): MenuDia
    {
        if (! $menuDia->save()) {
            throw new RuntimeException('No fue posible guardar el menú del día.');
        }

        return $menuDia->refresh()->load('productos');
    }

    /**
     * @param  list<int>  $productIds
     */
    public function syncProducts(MenuDia $menuDia, array $productIds): void
    {
        $menuDia->productos()->sync($productIds);
    }
}
