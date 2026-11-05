<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CanalPagoEnum;
use App\Enums\EstadoPagoEnum;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('payments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_administrator_can_view_payment_list_and_history(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create([
            'nombres' => 'Cliente Historial',
            'apellidos' => 'Pago',
        ]);
        $pedido = Pedido::factory()->create(['cliente_id' => $cliente->id]);
        $pago = Pago::factory()->create([
            'pedido_id' => $pedido->id,
            'monto' => '275.50',
            'canal' => CanalPagoEnum::EN_LINEA,
            'estado' => EstadoPagoEnum::APROBADO,
            'referencia_operacion' => 'REF-HISTORIAL-001',
            'proveedor' => 'Proveedor de pruebas',
            'transaccion_externa' => 'TX-HISTORIAL-001',
        ]);

        $this->actingAs($administrador)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee($pago->referencia_operacion)
            ->assertSee('Cliente Historial')
            ->assertSee('275,50')
            ->assertSee(route('payments.show', $pago));

        $this->get(route('payments.show', $pago))
            ->assertOk()
            ->assertSee($pago->referencia_operacion)
            ->assertSee('Proveedor de pruebas')
            ->assertSee('TX-HISTORIAL-001')
            ->assertDontSee($pago->clave_idempotencia)
            ->assertSee('No se realizan operaciones de cobro');
    }

    public function test_payment_filters_and_pagination_are_applied(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create([
            'nombres' => 'FiltrarPago',
            'apellidos' => 'Cliente',
        ]);
        $targetOrder = Pedido::factory()->create(['cliente_id' => $cliente->id]);
        $target = Pago::factory()->create([
            'pedido_id' => $targetOrder->id,
            'referencia_operacion' => 'REF-FILTRO-ESPECIAL',
            'canal' => CanalPagoEnum::PRESENCIAL,
            'estado' => EstadoPagoEnum::APROBADO,
        ]);
        Pago::factory()->create([
            'canal' => CanalPagoEnum::EN_LINEA,
            'estado' => EstadoPagoEnum::PENDIENTE,
        ]);

        $this->actingAs($administrador)
            ->get(route('payments.index', [
                'search' => 'FiltrarPago',
                'estado' => EstadoPagoEnum::APROBADO->value,
                'canal' => CanalPagoEnum::PRESENCIAL->value,
            ]))
            ->assertOk()
            ->assertSee($target->referencia_operacion)
            ->assertViewHas(
                'payments',
                fn ($payments): bool => $payments->total() === 1
                    && $payments->contains('id', $target->id),
            );

        Pago::factory()->count(15)->create();
        $this->get(route('payments.index'))
            ->assertViewHas(
                'payments',
                fn ($payments): bool => $payments->total() === 17
                    && $payments->perPage() === 15
                    && $payments->count() === 15,
            );
    }

    public function test_only_administrators_can_view_payment_history(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Usuario::factory()->create();
        $pago = Pago::factory()->create();

        $this->actingAs($operador)
            ->get(route('payments.index'))
            ->assertForbidden();
        $this->get(route('payments.show', $pago))->assertForbidden();
        $this->actingAs($cliente)
            ->get(route('payments.index'))
            ->assertForbidden();
    }

    public function test_payment_filters_reject_invalid_values_and_date_ranges(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->from(route('payments.index'))
            ->get(route('payments.index', [
                'estado' => 'FRAUDULENTO',
                'canal' => 'DESCONOCIDO',
                'fecha_desde' => '2026-11-10',
                'fecha_hasta' => '2026-11-09',
            ]))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasErrors(['estado', 'canal', 'fecha_hasta']);
    }
}
