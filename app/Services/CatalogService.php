<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Producto;
use App\Models\Usuario;
use App\Repositories\CatalogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    public function __construct(
        private readonly CatalogRepository $catalogRepository,
    ) {}

    /**
     * @param  array{search?: string, disponible?: string}  $filters
     * @return LengthAwarePaginator<int, Producto>
     */
    public function paginate(Usuario $usuario, array $filters): LengthAwarePaginator
    {
        return $this->catalogRepository->paginateFor($usuario, $filters);
    }

    /**
     * @return Collection<int, Producto>
     */
    public function availableProducts(): Collection
    {
        return $this->catalogRepository->availableProducts();
    }

    /**
     * @param  array{nombre: string, descripcion?: string|null, precio: string}  $data
     */
    public function create(array $data): Producto
    {
        $producto = new Producto([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'precio' => $data['precio'],
            'disponible' => true,
        ]);

        return $this->catalogRepository->save($producto);
    }

    public function findProduct(int $productoId): ?Producto
    {
        return $this->catalogRepository->find($productoId);
    }

    /**
     * @param  array{nombre: string, descripcion?: string|null, precio: string}  $data
     */
    public function update(Producto $producto, array $data): Producto
    {
        $producto->fill([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'precio' => $data['precio'],
        ]);

        return $this->catalogRepository->save($producto);
    }

    public function activate(Producto $producto): Producto
    {
        if (! $producto->disponible) {
            $producto->disponible = true;
        }

        return $this->catalogRepository->save($producto);
    }

    public function deactivate(Producto $producto): Producto
    {
        if ($producto->disponible) {
            $producto->disponible = false;
        }

        return $this->catalogRepository->save($producto);
    }
}
