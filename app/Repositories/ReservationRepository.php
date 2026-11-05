<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\EstadoReservaEnum;
use App\Enums\RolUsuarioEnum;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Usuario;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class ReservationRepository
{
    /**
     * @param  array{
     *     search?: string,
     *     estado?: string,
     *     fecha_desde?: string,
     *     fecha_hasta?: string
     * }  $filters
     * @return LengthAwarePaginator<int, Reserva>
     */
    public function paginateFor(Usuario $usuario, array $filters): LengthAwarePaginator
    {
        $query = Reserva::query()
            ->with(['cliente', 'mesa', 'usuario'])
            ->when(
                $usuario->rol === RolUsuarioEnum::CLIENTE,
                fn (Builder $query): Builder => $query->whereHas(
                    'cliente',
                    fn (Builder $clienteQuery): Builder => $clienteQuery
                        ->where('usuario_id', $usuario->getKey()),
                ),
            )
            ->when(
                $filters['search'] ?? null,
                function (Builder $query, string $search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->whereHas(
                                'cliente',
                                fn (Builder $clienteQuery): Builder => $clienteQuery
                                    ->where('nombres', 'like', "%{$search}%")
                                    ->orWhere('apellidos', 'like', "%{$search}%")
                                    ->orWhere('correo', 'like', "%{$search}%"),
                            )
                            ->orWhereHas(
                                'mesa',
                                fn (Builder $mesaQuery): Builder => $mesaQuery
                                    ->where('numero', 'like', "%{$search}%"),
                            );
                    });
                },
            )
            ->when(
                $filters['estado'] ?? null,
                fn (Builder $query, string $estado): Builder => $query->where('estado', $estado),
            )
            ->when(
                $filters['fecha_desde'] ?? null,
                fn (Builder $query, string $fecha): Builder => $query->whereDate(
                    'fecha_inicio',
                    '>=',
                    $fecha,
                ),
            )
            ->when(
                $filters['fecha_hasta'] ?? null,
                fn (Builder $query, string $fecha): Builder => $query->whereDate(
                    'fecha_inicio',
                    '<=',
                    $fecha,
                ),
            )
            ->orderByDesc('fecha_inicio');

        return $query->paginate(15)->withQueryString();
    }

    public function find(int $reservaId): ?Reserva
    {
        return Reserva::query()
            ->with(['cliente', 'mesa', 'usuario'])
            ->find($reservaId);
    }

    public function findForUpdate(int $reservaId): ?Reserva
    {
        return Reserva::query()->lockForUpdate()->find($reservaId);
    }

    public function findCustomer(int $clienteId): ?Cliente
    {
        return Cliente::query()->find($clienteId);
    }

    public function findTableForUpdate(int $mesaId): ?Mesa
    {
        return Mesa::query()->lockForUpdate()->find($mesaId);
    }

    /**
     * @return Collection<int, Cliente>
     */
    public function customers(): Collection
    {
        return Cliente::query()
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();
    }

    /**
     * @return Collection<int, Mesa>
     */
    public function tables(): Collection
    {
        return Mesa::query()->orderBy('numero')->get();
    }

    public function hasConflict(
        Mesa $mesa,
        DateTimeInterface $fechaInicio,
        DateTimeInterface $fechaFin,
        ?int $exceptReservaId = null,
    ): bool {
        return Reserva::query()
            ->where('mesa_id', $mesa->getKey())
            ->where('estado', '!=', EstadoReservaEnum::CANCELADA->value)
            ->where('fecha_inicio', '<', $fechaFin)
            ->where('fecha_fin', '>', $fechaInicio)
            ->when(
                $exceptReservaId !== null,
                fn (Builder $query): Builder => $query->where('id', '!=', $exceptReservaId),
            )
            ->exists();
    }

    public function save(Reserva $reserva): Reserva
    {
        if (! $reserva->save()) {
            throw new RuntimeException('No fue posible guardar la reserva.');
        }

        return $reserva->refresh()->load(['cliente', 'mesa', 'usuario']);
    }

    public function delete(Reserva $reserva): void
    {
        if (! $reserva->delete()) {
            throw new RuntimeException('No fue posible eliminar la reserva.');
        }
    }
}
