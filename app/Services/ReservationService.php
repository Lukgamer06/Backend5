<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoReservaEnum;
use App\Enums\RolUsuarioEnum;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Repositories\ReservationRepository;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly AvailabilityService $availabilityService,
    ) {}

    /**
     * @param  array{
     *     search?: string,
     *     estado?: string,
     *     fecha_desde?: string,
     *     fecha_hasta?: string,
     *     per_page?: int
     * }  $filters
     * @return LengthAwarePaginator<int, Reserva>
     */
    public function paginate(Usuario $usuario, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->reservationRepository->paginateFor($usuario, $filters, $perPage);
    }

    public function findOrFail(int $reservaId): Reserva
    {
        return $this->reservationRepository->find($reservaId)
            ?? throw (new ModelNotFoundException)->setModel(Reserva::class, [$reservaId]);
    }

    /**
     * @return array{clientes: Collection<int, Cliente>, mesas: Collection<int, Mesa>}
     */
    public function formOptions(Usuario $usuario): array
    {
        return [
            'clientes' => $usuario->rol === RolUsuarioEnum::CLIENTE
                ? new Collection
                : $this->reservationRepository->customers(),
            'mesas' => $this->reservationRepository->tables(),
        ];
    }

    public function create(
        Usuario $usuario,
        ?int $clienteId,
        int $mesaId,
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
        ?string $observaciones,
    ): Reserva {
        return DB::transaction(function () use (
            $usuario,
            $clienteId,
            $mesaId,
            $fechaInicio,
            $fechaFin,
            $numeroPersonas,
            $observaciones,
        ): Reserva {
            $cliente = $this->resolveCustomer($usuario, $clienteId);
            $mesa = $this->resolveTableForUpdate($mesaId);

            $this->validateReservation($mesa, $fechaInicio, $fechaFin, $numeroPersonas);

            $reserva = new Reserva([
                'usuario_id' => $usuario->getKey(),
                'cliente_id' => $cliente->getKey(),
                'mesa_id' => $mesa->getKey(),
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'numero_personas' => $numeroPersonas,
                'estado' => EstadoReservaEnum::CONFIRMADA,
                'observaciones' => $observaciones,
            ]);

            return $this->reservationRepository->save($reserva);
        });
    }

    public function update(
        Reserva $reserva,
        Usuario $usuario,
        ?int $clienteId,
        int $mesaId,
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
        EstadoReservaEnum $estado,
        ?string $observaciones,
    ): Reserva {
        return DB::transaction(function () use (
            $reserva,
            $usuario,
            $clienteId,
            $mesaId,
            $fechaInicio,
            $fechaFin,
            $numeroPersonas,
            $estado,
            $observaciones,
        ): Reserva {
            $lockedReserva = $this->reservationRepository->findForUpdate($reserva->getKey());

            if (! $lockedReserva instanceof Reserva) {
                throw new ModelNotFoundException('No se encontró la reserva que se desea actualizar.');
            }

            $cliente = $this->resolveCustomer($usuario, $clienteId);
            $mesa = $this->resolveTableForUpdate($mesaId);

            $this->validateReservation(
                $mesa,
                $fechaInicio,
                $fechaFin,
                $numeroPersonas,
                $lockedReserva->getKey(),
            );

            $lockedReserva->fill([
                'cliente_id' => $cliente->getKey(),
                'mesa_id' => $mesa->getKey(),
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'numero_personas' => $numeroPersonas,
                'estado' => $estado,
                'observaciones' => $observaciones,
            ]);

            return $this->reservationRepository->save($lockedReserva);
        });
    }

    public function cancel(Reserva $reserva): Reserva
    {
        if ($reserva->estado === EstadoReservaEnum::FINALIZADA) {
            throw ValidationException::withMessages([
                'reserva' => 'No es posible cancelar una reserva finalizada.',
            ]);
        }

        if ($reserva->estado === EstadoReservaEnum::CANCELADA) {
            return $reserva;
        }

        $reserva->estado = EstadoReservaEnum::CANCELADA;

        return $this->reservationRepository->save($reserva);
    }

    public function delete(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva): void {
            $lockedReserva = $this->reservationRepository->findForUpdate($reserva->getKey());

            if (! $lockedReserva instanceof Reserva) {
                throw new ModelNotFoundException('No se encontró la reserva que se desea eliminar.');
            }

            if ($lockedReserva->pedidos()->exists()) {
                throw ValidationException::withMessages([
                    'reserva' => 'No se puede eliminar una reserva que tiene pedidos asociados.',
                ]);
            }

            $this->reservationRepository->delete($lockedReserva);
        });
    }

    private function resolveCustomer(Usuario $usuario, ?int $clienteId): Cliente
    {
        $cliente = $usuario->rol === RolUsuarioEnum::CLIENTE
            ? $usuario->cliente()->first()
            : ($clienteId === null ? null : $this->reservationRepository->findCustomer($clienteId));

        if (! $cliente instanceof Cliente) {
            throw new ModelNotFoundException('No se encontró el cliente asociado a la reserva.');
        }

        return $cliente;
    }

    private function resolveTableForUpdate(int $mesaId): Mesa
    {
        $mesa = $this->reservationRepository->findTableForUpdate($mesaId);

        if (! $mesa instanceof Mesa) {
            throw new ModelNotFoundException('No se encontró la mesa seleccionada.');
        }

        return $mesa;
    }

    private function validateReservation(
        Mesa $mesa,
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        int $numeroPersonas,
        ?int $exceptReservaId = null,
    ): void {
        if ($fechaFin <= $fechaInicio) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            ]);
        }

        $this->availabilityService->ensureTableIsAvailable(
            $mesa,
            $fechaInicio,
            $fechaFin,
            $numeroPersonas,
            $exceptReservaId,
        );
    }
}
