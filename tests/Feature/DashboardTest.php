<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Incidencia;
use App\Models\Mesa;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_administrator_sees_operational_totals_and_recent_records(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create();
        $reserva = Reserva::factory()->create([
            'usuario_id' => $administrador->id,
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa->id,
        ]);
        $pedido = Pedido::factory()->create([
            'usuario_id' => $administrador->id,
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa->id,
        ]);
        $pago = Pago::factory()->create(['pedido_id' => $pedido->id]);
        $incidencia = Incidencia::factory()->create([
            'usuario_reporta_id' => $administrador->id,
            'mesa_id' => $mesa->id,
            'pedido_id' => $pedido->id,
        ]);

        $this->actingAs($administrador)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Total de reservas')
            ->assertSee('Total de pedidos')
            ->assertSee('Total de pagos')
            ->assertSee('Total de incidencias')
            ->assertSee($pago->referencia_operacion)
            ->assertSee($incidencia->titulo)
            ->assertSee($cliente->nombres)
            ->assertViewHas('reservationActivity', fn (array $activity): bool => count($activity) === 7)
            ->assertViewHas('orderActivity', fn (array $activity): bool => count($activity) === 7)
            ->assertViewHas('recentReservations', fn ($reservations): bool => $reservations->contains('id', $reserva->id));
    }

    public function test_operator_can_see_incidents_but_not_payment_data(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create();
        $pedido = Pedido::factory()->create([
            'usuario_id' => $operador->id,
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa->id,
        ]);
        $pago = Pago::factory()->create(['pedido_id' => $pedido->id]);
        $incidencia = Incidencia::factory()->create([
            'usuario_reporta_id' => $operador->id,
            'mesa_id' => $mesa->id,
            'pedido_id' => $pedido->id,
        ]);

        $this->actingAs($operador)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Total de incidencias')
            ->assertSee($incidencia->titulo)
            ->assertDontSee('Total de pagos')
            ->assertDontSee('Últimos pagos')
            ->assertDontSee($pago->referencia_operacion);
    }

    public function test_customer_sees_only_own_reservations_and_orders(): void
    {
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create([
            'nombres' => 'Cliente privado',
            'correo' => 'cliente.privado@example.test',
        ]);
        $mesaPropia = Mesa::factory()->create(['numero' => 9876]);
        $mesaAjena = Mesa::factory()->create(['numero' => 9877]);
        Reserva::factory()->create([
            'usuario_id' => $cliente->usuario_id,
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesaPropia->id,
        ]);
        Reserva::factory()->create([
            'usuario_id' => $otroCliente->usuario_id,
            'cliente_id' => $otroCliente->id,
            'mesa_id' => $mesaAjena->id,
        ]);
        Pedido::factory()->create([
            'usuario_id' => $cliente->usuario_id,
            'cliente_id' => $cliente->id,
            'total' => 123.45,
        ]);
        Pedido::factory()->create([
            'usuario_id' => $otroCliente->usuario_id,
            'cliente_id' => $otroCliente->id,
            'total' => 98765.43,
        ]);

        $this->actingAs($cliente->usuario)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Mis reservas')
            ->assertSee('Mis pedidos')
            ->assertSee('9876')
            ->assertDontSee('9877')
            ->assertSee('$123,45')
            ->assertDontSee('$98.765,43')
            ->assertDontSee('Total de pagos')
            ->assertDontSee('Total de incidencias')
            ->assertDontSee('Cliente privado');
    }

    public function test_customer_with_no_activity_sees_empty_states(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($cliente->usuario)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Todavía no hay reservas para mostrar.')
            ->assertSee('Todavía no hay pedidos para mostrar.')
            ->assertSee('Mis reservas')
            ->assertSee('Mis pedidos')
            ->assertDontSee('Últimos pagos')
            ->assertDontSee('Últimas incidencias');
    }
}
