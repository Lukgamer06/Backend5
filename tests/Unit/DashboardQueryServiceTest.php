<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\RolUsuarioEnum;
use App\Models\Usuario;
use App\Repositories\DashboardQueryRepository;
use App\Services\DashboardQueryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class DashboardQueryServiceTest extends TestCase
{
    public function test_it_builds_a_seven_day_activity_series_and_role_aware_metrics(): void
    {
        Carbon::setTestNow('2026-11-05 12:00:00');

        try {
            $repository = Mockery::mock(DashboardQueryRepository::class);
            $repository->shouldReceive('forUser')
                ->once()
                ->andReturn([
                    'reservationCount' => 4,
                    'orderCount' => 2,
                    'paymentCount' => 3,
                    'incidentCount' => 5,
                    'reservationActivity' => [
                        '2026-11-04' => 2,
                        '2026-11-05' => 4,
                    ],
                    'orderActivity' => [],
                    'recentReservations' => new Collection,
                    'recentOrders' => new Collection,
                    'recentPayments' => new Collection,
                    'recentIncidents' => new Collection,
                ]);

            $usuario = new Usuario(['rol' => RolUsuarioEnum::ADMINISTRADOR]);
            $overview = (new DashboardQueryService($repository))->overview($usuario);

            $this->assertCount(4, $overview['metrics']);
            $this->assertSame(4, $overview['metrics'][0]['value']);
            $this->assertSame('Total de incidencias', $overview['metrics'][3]['label']);
            $this->assertCount(7, $overview['reservationActivity']);
            $this->assertCount(7, $overview['orderActivity']);
            $this->assertSame(100, $overview['reservationActivity'][6]['height']);
            $this->assertSame(0, $overview['orderActivity'][6]['height']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_omits_payment_and_incident_metrics_for_customers(): void
    {
        $repository = Mockery::mock(DashboardQueryRepository::class);
        $repository->shouldReceive('forUser')
            ->once()
            ->andReturn([
                'reservationCount' => 1,
                'orderCount' => 2,
                'paymentCount' => null,
                'incidentCount' => null,
                'reservationActivity' => [],
                'orderActivity' => [],
                'recentReservations' => new Collection,
                'recentOrders' => new Collection,
                'recentPayments' => new Collection,
                'recentIncidents' => new Collection,
            ]);

        $usuario = new Usuario(['rol' => RolUsuarioEnum::CLIENTE]);
        $overview = (new DashboardQueryService($repository))->overview($usuario);
        $labels = array_column($overview['metrics'], 'label');

        $this->assertSame(['Mis reservas', 'Mis pedidos'], $labels);
    }
}
