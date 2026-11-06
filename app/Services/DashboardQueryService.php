<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RolUsuarioEnum;
use App\Models\Incidencia;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Repositories\DashboardQueryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class DashboardQueryService
{
    public function __construct(
        private readonly DashboardQueryRepository $dashboardQueryRepository,
    ) {}

    /**
     * @return array{
     *     metrics: list<array{label: string, value: int, color: string}>,
     *     reservationActivity: list<array{label: string, count: int, height: int}>,
     *     orderActivity: list<array{label: string, count: int, height: int}>,
     *     recentReservations: Collection<int, Reserva>,
     *     recentOrders: Collection<int, Pedido>,
     *     recentPayments: Collection<int, Pago>,
     *     recentIncidents: Collection<int, Incidencia>
     * }
     */
    public function overview(Usuario $usuario): array
    {
        $fechaFin = Carbon::now()->endOfDay();
        $fechaInicio = $fechaFin->copy()->subDays(6)->startOfDay();
        $data = $this->dashboardQueryRepository->forUser($usuario, $fechaInicio, $fechaFin);
        $metrics = [
            [
                'label' => $usuario->rol === RolUsuarioEnum::CLIENTE
                    ? 'Mis reservas'
                    : 'Total de reservas',
                'value' => $data['reservationCount'],
                'color' => 'primary',
            ],
            [
                'label' => $usuario->rol === RolUsuarioEnum::CLIENTE
                    ? 'Mis pedidos'
                    : 'Total de pedidos',
                'value' => $data['orderCount'],
                'color' => 'success',
            ],
        ];

        if ($data['paymentCount'] !== null) {
            $metrics[] = [
                'label' => 'Total de pagos',
                'value' => $data['paymentCount'],
                'color' => 'info',
            ];
        }

        if ($data['incidentCount'] !== null) {
            $metrics[] = [
                'label' => 'Total de incidencias',
                'value' => $data['incidentCount'],
                'color' => 'warning',
            ];
        }

        return [
            'metrics' => $metrics,
            'reservationActivity' => $this->formatActivity($data['reservationActivity'], $fechaFin),
            'orderActivity' => $this->formatActivity($data['orderActivity'], $fechaFin),
            'recentReservations' => $data['recentReservations'],
            'recentOrders' => $data['recentOrders'],
            'recentPayments' => $data['recentPayments'],
            'recentIncidents' => $data['recentIncidents'],
        ];
    }

    /**
     * @param  array<string, int>  $activity
     * @return list<array{label: string, count: int, height: int}>
     */
    private function formatActivity(array $activity, Carbon $fechaFin): array
    {
        $days = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $date = $fechaFin->copy()->startOfDay()->subDays($offset);
            $days[] = [
                'label' => $date->locale('es')->translatedFormat('D'),
                'count' => $activity[$date->toDateString()] ?? 0,
            ];
        }

        $maxCount = max(array_column($days, 'count'));

        return array_map(
            static fn (array $day): array => [
                ...$day,
                'height' => $maxCount === 0
                    ? 0
                    : max(8, (int) round($day['count'] / $maxCount * 100)),
            ],
            $days,
        );
    }
}
