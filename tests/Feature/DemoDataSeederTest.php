<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuarioEnum;
use App\Models\Usuario;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_populates_demo_workflows_without_adding_user_accounts_and_is_repeatable(): void
    {
        Usuario::factory()->create([
            'correo' => 'example@unab.edu.co',
            'rol' => RolUsuarioEnum::CLIENTE,
        ]);
        Usuario::factory()->create([
            'correo' => 'operador@unab.edu.co',
            'rol' => RolUsuarioEnum::OPERADOR,
        ]);
        Usuario::factory()->create([
            'correo' => 'admin@unab.edu.co',
            'rol' => RolUsuarioEnum::ADMINISTRADOR,
        ]);

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('usuarios', 3);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('mesas', 5);
        $this->assertDatabaseCount('productos', 5);
        $this->assertDatabaseCount('menus_dia', 1);
        $this->assertDatabaseCount('menu_dia_productos', 5);
        $this->assertDatabaseCount('reservas', 3);
        $this->assertDatabaseCount('pedidos', 3);
        $this->assertDatabaseCount('detalle_pedidos', 6);
        $this->assertDatabaseCount('pagos', 3);
        $this->assertDatabaseCount('incidencias', 2);

        $this->assertDatabaseHas('pagos', [
            'referencia_operacion' => 'DEMO-AZURE-RESERVA-01',
            'estado' => 'PENDIENTE',
        ]);
    }
}
