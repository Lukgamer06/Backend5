<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mesa;
use App\Repositories\ReservationRepository;
use App\Repositories\TableRepository;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

abstract class AvailabilityService
{
    public function __construct(
        protected readonly ReservationRepository $reservationRepository,
        protected readonly TableRepository $tableRepository,
    ) {}

    /**
     * @return Collection<int, Mesa>
     */
    abstract public function availableTables(
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
    ): Collection;
}
