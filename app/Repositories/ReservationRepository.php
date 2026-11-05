<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Mesa;
use App\Models\Reserva;
use DateTimeInterface;

abstract class ReservationRepository
{
    public function __construct() {}

    abstract public function find(int $reservaId): ?Reserva;

    abstract public function hasConflict(
        Mesa $mesa,
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
    ): bool;

    abstract public function save(Reserva $reserva): Reserva;
}
