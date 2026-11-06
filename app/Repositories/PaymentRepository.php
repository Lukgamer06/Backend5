<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pago;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class PaymentRepository
{
    /**
     * @param  array{
     *     search?: string,
     *     estado?: string,
     *     canal?: string,
     *     fecha_desde?: string,
     *     fecha_hasta?: string
     * }  $filters
     * @return LengthAwarePaginator<int, Pago>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Pago::query()
            ->with(['pedido.cliente'])
            ->when(
                $filters['search'] ?? null,
                function (Builder $query, string $search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('referencia_operacion', 'like', "%{$search}%")
                            ->orWhereHas(
                                'pedido',
                                function (Builder $pedidoQuery) use ($search): void {
                                    if (ctype_digit($search)) {
                                        $pedidoQuery->whereKey((int) $search);
                                    }

                                    $pedidoQuery->orWhereHas(
                                        'cliente',
                                        fn (Builder $clienteQuery): Builder => $clienteQuery
                                            ->where('nombres', 'like', "%{$search}%")
                                            ->orWhere('apellidos', 'like', "%{$search}%")
                                            ->orWhere('correo', 'like', "%{$search}%"),
                                    );
                                },
                            );
                    });
                },
            )
            ->when(
                $filters['estado'] ?? null,
                fn (Builder $query, string $estado): Builder => $query->where('estado', $estado),
            )
            ->when(
                $filters['canal'] ?? null,
                fn (Builder $query, string $canal): Builder => $query->where('canal', $canal),
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
            ->paginate(15)
            ->withQueryString();
    }

    public function findByIdempotencyKey(string $claveIdempotencia): ?Pago
    {
        return Pago::query()
            ->with(['pedido.cliente'])
            ->where('clave_idempotencia', $claveIdempotencia)
            ->first();
    }

    public function find(int $pagoId): ?Pago
    {
        return Pago::query()
            ->with(['pedido.cliente'])
            ->find($pagoId);
    }

    public function save(Pago $pago): Pago
    {
        if (! $pago->save()) {
            throw new RuntimeException('No fue posible guardar el pago.');
        }

        return $pago->refresh()->load(['pedido.cliente']);
    }
}
