<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\RolUsuarioEnum;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class CatalogRepository
{
    /**
     * @param  array{search?: string, disponible?: string}  $filters
     * @return LengthAwarePaginator<int, Producto>
     */
    public function paginateFor(Usuario $usuario, array $filters): LengthAwarePaginator
    {
        return Producto::query()
            ->when(
                $usuario->rol !== RolUsuarioEnum::ADMINISTRADOR,
                fn (Builder $query): Builder => $query->where('disponible', true),
            )
            ->when(
                $usuario->rol === RolUsuarioEnum::ADMINISTRADOR
                    && isset($filters['disponible'])
                    && $filters['disponible'] !== '',
                fn (Builder $query): Builder => $query->where(
                    'disponible',
                    $filters['disponible'] === '1',
                ),
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search): Builder => $query->where(
                    fn (Builder $query): Builder => $query
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%"),
                ),
            )
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $productoId): ?Producto
    {
        return Producto::query()->find($productoId);
    }

    /**
     * @return Collection<int, Producto>
     */
    public function availableProducts(): Collection
    {
        return Producto::query()
            ->where('disponible', true)
            ->orderBy('nombre')
            ->get();
    }

    public function save(Producto $producto): Producto
    {
        if (! $producto->save()) {
            throw new RuntimeException('No fue posible guardar el producto.');
        }

        return $producto->refresh();
    }
}
