<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mesa;
use App\Repositories\TableRepository;
use Illuminate\Database\Eloquent\Collection;

abstract class TableService
{
    public function __construct(
        protected readonly TableRepository $tableRepository,
    ) {}

    abstract public function find(int $mesaId): ?Mesa;

    /**
     * @return Collection<int, Mesa>
     */
    abstract public function all(): Collection;

    abstract public function save(Mesa $mesa): Mesa;
}
