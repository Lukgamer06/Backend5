<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Incidencia;
use App\Models\MenuDia;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reserva;
use App\Policies\IncidenciaPolicy;
use App\Policies\MenuDiaPolicy;
use App\Policies\PagoPolicy;
use App\Policies\PedidoPolicy;
use App\Policies\ProductoPolicy;
use App\Policies\ReservaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Incidencia::class, IncidenciaPolicy::class);
        Gate::policy(MenuDia::class, MenuDiaPolicy::class);
        Gate::policy(Pago::class, PagoPolicy::class);
        Gate::policy(Pedido::class, PedidoPolicy::class);
        Gate::policy(Producto::class, ProductoPolicy::class);
        Gate::policy(Reserva::class, ReservaPolicy::class);
    }
}
