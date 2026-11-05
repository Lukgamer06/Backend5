<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Collection;

abstract class CatalogService
{
    public function __construct() {}

    /**
     * @return Collection<int, Producto>
     */
    abstract public function availableProducts(): Collection;

    abstract public function findProduct(int $productoId): ?Producto;
}
