<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\RolUsuarioEnum;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class OrderRepository
{
    /**
     * @param  array{
     *     search?: string,
     *     estado?: string,
     *     tipo_atencion?: string,
     *     fecha_desde?: string,
     *     fecha_hasta?: string,
     *     per_page?: int
     * }  $filters
     * @return LengthAwarePaginator<int, Pedido>
     */
    public function paginateFor(
        Usuario $usuario,
        array $filters,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return Pedido::query()
            ->with(['cliente', 'mesa', 'detalles.producto'])
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
                        if (ctype_digit($search)) {
                            $query->whereKey((int) $search);
                        }

                        $query->orWhereHas(
                            'cliente',
                            fn (Builder $clienteQuery): Builder => $clienteQuery
                                ->where('nombres', 'like', "%{$search}%")
                                ->orWhere('apellidos', 'like', "%{$search}%")
                                ->orWhere('correo', 'like', "%{$search}%"),
                        );
                    });
                },
            )
            ->when(
                $filters['estado'] ?? null,
                fn (Builder $query, string $estado): Builder => $query->where('estado', $estado),
            )
            ->when(
                $filters['tipo_atencion'] ?? null,
                fn (Builder $query, string $tipo): Builder => $query->where(
                    'tipo_atencion',
                    $tipo,
                ),
            )
            ->when(
                $filters['fecha_desde'] ?? null,
                fn (Builder $query, string $fecha): Builder => $query->whereDate(
                    $query->getModel()->qualifyColumn('created_at'),
                    '>=',
                    $fecha,
                ),
            )
            ->when(
                $filters['fecha_hasta'] ?? null,
                fn (Builder $query, string $fecha): Builder => $query->whereDate(
                    $query->getModel()->qualifyColumn('created_at'),
                    '<=',
                    $fecha,
                ),
            )
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $pedidoId): ?Pedido
    {
        return Pedido::query()
            ->with(['cliente', 'mesa', 'reserva', 'usuario', 'detalles.producto', 'pagos'])
            ->find($pedidoId);
    }

    public function findForUpdate(int $pedidoId): ?Pedido
    {
        return Pedido::query()->lockForUpdate()->find($pedidoId);
    }

    public function findCustomer(int $clienteId): ?Cliente
    {
        return Cliente::query()->find($clienteId);
    }

    public function findTable(int $mesaId): ?Mesa
    {
        return Mesa::query()->find($mesaId);
    }

    public function findReservation(int $reservaId): ?Reserva
    {
        return Reserva::query()->find($reservaId);
    }

    /**
     * @param  list<int>  $productIds
     * @return Collection<int, Producto>
     */
    public function findAvailableProducts(array $productIds): Collection
    {
        return Producto::query()
            ->whereIn('id', $productIds)
            ->where('disponible', true)
            ->orderBy('nombre')
            ->lockForUpdate()
            ->get();
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
        return Mesa::query()
            ->orderBy('numero')
            ->get();
    }

    /**
     * @return Collection<int, Reserva>
     */
    public function reservationsForCustomer(int $clienteId): Collection
    {
        return Reserva::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    /**
     * @return Collection<int, Reserva>
     */
    public function reservations(): Collection
    {
        return Reserva::query()
            ->with('cliente')
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    /**
     * @return Collection<int, Producto>
     */
    public function availableProducts(): Collection
    {
        return Producto::query()
            ->where('disponible', true)
            ->orderBy('nombre')
            ->get();
    }

    public function save(Pedido $pedido): Pedido
    {
        if (! $pedido->save()) {
            throw new RuntimeException('No fue posible guardar el pedido.');
        }

        return $pedido->refresh()->load([
            'cliente',
            'mesa',
            'reserva',
            'usuario',
            'detalles.producto',
            'pagos',
        ]);
    }

    public function deleteDetails(Pedido $pedido): void
    {
        $pedido->detalles()->delete();
    }

    public function delete(Pedido $pedido): void
    {
        if (! $pedido->delete()) {
            throw new RuntimeException('No fue posible eliminar el pedido.');
        }
    }
}
