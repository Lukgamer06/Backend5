<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Incidencia;
use App\Models\Mesa;
use App\Models\Pedido;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class IncidentRepository
{
    /**
     * @param  array{search?: string, prioridad?: string, estado?: string}  $filters
     * @return LengthAwarePaginator<int, Incidencia>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Incidencia::query()
            ->with(['usuario', 'mesa', 'pedido'])
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search): Builder => $query->where(
                    fn (Builder $query): Builder => $query
                        ->where('titulo', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%"),
                ),
            )
            ->when(
                $filters['prioridad'] ?? null,
                fn (Builder $query, string $prioridad): Builder => $query->where(
                    'prioridad',
                    $prioridad,
                ),
            )
            ->when(
                $filters['estado'] ?? null,
                fn (Builder $query, string $estado): Builder => $query->where('estado', $estado),
            )
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $incidenciaId): ?Incidencia
    {
        return Incidencia::query()
            ->with(['usuario', 'mesa', 'pedido'])
            ->find($incidenciaId);
    }

    /**
     * @return Collection<int, Mesa>
     */
    public function mesas(): Collection
    {
        return Mesa::query()->orderBy('numero')->get();
    }

    /**
     * @return Collection<int, Pedido>
     */
    public function pedidos(): Collection
    {
        return Pedido::query()
            ->with('cliente')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function save(Incidencia $incidencia): Incidencia
    {
        if (! $incidencia->save()) {
            throw new RuntimeException('No fue posible guardar la incidencia.');
        }

        return $incidencia->refresh()->load(['usuario', 'mesa', 'pedido']);
    }
}
