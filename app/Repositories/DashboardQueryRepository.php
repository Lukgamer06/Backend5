<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\RolUsuarioEnum;
use App\Models\Incidencia;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardQueryRepository
{
    /**
     * @return array{
     *     reservationCount: int,
     *     orderCount: int,
     *     paymentCount: int|null,
     *     incidentCount: int|null,
     *     reservationActivity: array<string, int>,
     *     orderActivity: array<string, int>,
     *     recentReservations: Collection<int, Reserva>,
     *     recentOrders: Collection<int, Pedido>,
     *     recentPayments: Collection<int, Pago>,
     *     recentIncidents: Collection<int, Incidencia>
     * }
     */
    public function forUser(Usuario $usuario, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $esCliente = $usuario->rol === RolUsuarioEnum::CLIENTE;
        $esAdministrador = $usuario->rol === RolUsuarioEnum::ADMINISTRADOR;
        $clienteId = $esCliente ? $usuario->cliente()->value('id') : null;
        $clienteId = $clienteId === null ? null : (int) $clienteId;
        $reservationQuery = $this->scopeToCustomer(Reserva::query(), $esCliente, $clienteId);
        $orderQuery = $this->scopeToCustomer(Pedido::query(), $esCliente, $clienteId);
        $incidentCount = $esCliente
            ? null
            : Incidencia::query()
                ->where('estado', EstadoIncidenciaEnum::ABIERTA->value)
                ->count();

        return [
            'reservationCount' => (int) (clone $reservationQuery)->count(),
            'orderCount' => (int) (clone $orderQuery)->count(),
            'paymentCount' => $esAdministrador ? (int) Pago::query()->count() : null,
            'incidentCount' => $incidentCount === null ? null : (int) $incidentCount,
            'reservationActivity' => $this->dailyActivity(
                (clone $reservationQuery)->whereBetween('created_at', [$fechaInicio, $fechaFin]),
            ),
            'orderActivity' => $this->dailyActivity(
                (clone $orderQuery)->whereBetween('created_at', [$fechaInicio, $fechaFin]),
            ),
            'recentReservations' => $esCliente && $clienteId === null
                ? new Collection
                : $this->scopeToCustomer(Reserva::query(), $esCliente, $clienteId)
                    ->with(['cliente', 'mesa'])
                    ->latest('created_at')
                    ->limit(5)
                    ->get(),
            'recentOrders' => $esCliente && $clienteId === null
                ? new Collection
                : $this->scopeToCustomer(Pedido::query(), $esCliente, $clienteId)
                    ->with('cliente')
                    ->latest('created_at')
                    ->limit(5)
                    ->get(),
            'recentPayments' => $esAdministrador
                ? Pago::query()->with('pedido.cliente')->latest('created_at')->limit(5)->get()
                : new Collection,
            'recentIncidents' => $esCliente
                ? new Collection
                : Incidencia::query()->latest('created_at')->limit(5)->get(),
        ];
    }

    /**
     * @param  Builder<Reserva>|Builder<Pedido>  $query
     * @return Builder<Reserva>|Builder<Pedido>
     */
    private function scopeToCustomer(Builder $query, bool $esCliente, ?int $clienteId): Builder
    {
        if (! $esCliente) {
            return $query;
        }

        return $query->where('cliente_id', $clienteId ?? 0);
    }

    /**
     * @param  Builder<Reserva>|Builder<Pedido>  $query
     * @return array<string, int>
     */
    private function dailyActivity(Builder $query): array
    {
        return $query
            ->selectRaw('DATE(created_at) as activity_date, COUNT(*) as activity_total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('activity_date')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [
                (string) $row->activity_date => (int) $row->activity_total,
            ])
            ->all();
    }
}
