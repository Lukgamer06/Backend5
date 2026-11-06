<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Mesa;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

abstract class TableRepository
{
    abstract public function find(int $mesaId): ?Mesa;

    /**
     * @return Collection<int, Mesa>
     */
    abstract public function all(): Collection;

    /**
     * @return Collection<int, Mesa>
     */
    abstract public function availableFor(
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
    ): Collection;

    abstract public function save(Mesa $mesa): Mesa;
}
