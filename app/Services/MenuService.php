<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MenuDia;
use App\Repositories\MenuRepository;
use DateTimeInterface;

abstract class MenuService
{
    public function __construct(
        protected readonly MenuRepository $menuRepository,
    ) {}

    abstract public function findByDate(DateTimeInterface $fecha): ?MenuDia;

    abstract public function publish(MenuDia $menuDia): MenuDia;
}
