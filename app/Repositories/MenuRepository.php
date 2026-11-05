<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MenuDia;
use DateTimeInterface;

abstract class MenuRepository
{
    public function __construct() {}

    abstract public function findByDate(DateTimeInterface $fecha): ?MenuDia;

    abstract public function save(MenuDia $menuDia): MenuDia;
}
