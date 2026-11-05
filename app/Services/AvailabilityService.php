<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoOperativoMesaEnum;
use App\Models\Mesa;
use App\Repositories\ReservationRepository;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    public function __construct(
        protected readonly ReservationRepository $reservationRepository,
    ) {}

    /**
     * @return Collection<int, Mesa>
     */
    public function availableTables(
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
    ): Collection {
        return $this->reservationRepository->tables()
            ->filter(fn (Mesa $mesa): bool => $this->isAvailable(
                $mesa,
                $fechaInicio,
                $fechaFin,
                $numeroPersonas,
            ))
            ->values();
    }

    public function ensureTableIsAvailable(
        Mesa $mesa,
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
        ?int $exceptReservaId = null,
    ): void {
        if ($mesa->estado_operativo !== EstadoOperativoMesaEnum::HABILITADA) {
            throw ValidationException::withMessages([
                'mesa_id' => 'La mesa seleccionada no está habilitada para reservas.',
            ]);
        }

        if ($numeroPersonas > $mesa->capacidad) {
            throw ValidationException::withMessages([
                'numero_personas' => 'El número de personas supera la capacidad de la mesa.',
            ]);
        }

        if ($this->reservationRepository->hasConflict(
            $mesa,
            $fechaInicio,
            $fechaFin,
            $exceptReservaId,
        )) {
            throw new ReservationConflictException;
        }
    }

    private function isAvailable(
        Mesa $mesa,
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
    ): bool {
        return $mesa->estado_operativo === EstadoOperativoMesaEnum::HABILITADA
            && $numeroPersonas <= $mesa->capacidad
            && ! $this->reservationRepository->hasConflict(
                $mesa,
                $fechaInicio,
                $fechaFin,
            );
    }
}
