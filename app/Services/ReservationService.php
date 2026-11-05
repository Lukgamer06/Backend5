<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Repositories\ReservationRepository;
use DateTimeInterface;

abstract class ReservationService
{
    public function __construct(
        protected readonly ReservationRepository $reservationRepository,
    ) {}

    abstract public function create(
        Usuario $usuario,
        Cliente $cliente,
        Mesa $mesa,
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
    ): Reserva;

    abstract public function cancel(Reserva $reserva): Reserva;
}
