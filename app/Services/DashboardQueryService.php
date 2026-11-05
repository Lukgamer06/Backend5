<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;

abstract class DashboardQueryService
{
    public function __construct() {}

    /**
     * @return array<string, int|float|string|null>
     */
    abstract public function summary(
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
    ): array;
}
