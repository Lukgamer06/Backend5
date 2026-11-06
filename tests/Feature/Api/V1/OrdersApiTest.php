<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\EstadoPedidoEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrdersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_only_their_orders_with_filters_and_pagination(): void
    {
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $propio = $this->createOrder($cliente);
        $this->createOrder($otroCliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->getJson('/api/v1/pedidos?estado=REGISTRADO&per_page=1')
            ->assertOk()
            ->assertJsonPath('message', 'Pedidos consultados correctamente.')
            ->assertJsonPath('data.0.id', $propio->id)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure(['data', 'links', 'meta', 'message']);
    }

    public function test_customer_can_read_their_order_but_not_another_customers_order(): void
    {
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $propio = $this->createOrder($cliente);
        $ajeno = $this->createOrder($otroCliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->getJson("/api/v1/pedidos/{$propio->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $propio->id)
            ->assertJsonStructure(['data' => ['detalles', 'pagos'], 'message', 'meta']);

        $this->getJson("/api/v1/pedidos/{$ajeno->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Acceso denegado.');
    }

    public function test_customer_can_create_an_order_with_server_calculated_total(): void
    {
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['precio' => '12.35']);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->postJson('/api/v1/pedidos', [
                'tipo_atencion' => TipoAtencionEnum::INMEDIATO->value,
                'modalidad_servicio' => ModalidadServicioEnum::SIN_MESA->value,
                'lineas' => [
                    ['producto_id' => $producto->id, 'cantidad' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Pedido creado correctamente.')
            ->assertJsonPath('data.cliente_id', $cliente->id)
            ->assertJsonPath('data.usuario_id', $cliente->usuario_id)
            ->assertJsonPath('data.total', '24.70')
            ->assertJsonPath('data.detalles.0.subtotal', '24.70')
            ->assertJsonStructure(['data' => ['id', 'estado', 'detalles'], 'message', 'meta']);
    }

    public function test_store_rejects_client_supplied_owner_status_and_total(): void
    {
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create();

        $this->actingAs($cliente->usuario, 'sanctum')
            ->postJson('/api/v1/pedidos', [
                'usuario_id' => $cliente->usuario_id,
                'estado' => EstadoPedidoEnum::ENTREGADO->value,
                'total' => '0.01',
                'tipo_atencion' => TipoAtencionEnum::INMEDIATO->value,
                'modalidad_servicio' => ModalidadServicioEnum::SIN_MESA->value,
                'lineas' => [
                    ['producto_id' => $producto->id, 'cantidad' => 1],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Los datos enviados no son válidos.')
            ->assertJsonStructure(['data', 'message', 'meta' => ['errors']]);
    }

    public function test_staff_can_partially_update_an_order_without_repricing_existing_details(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['precio' => '10.00']);
        $pedido = $this->createOrder($cliente, $producto, '10.00');
        $programada = Carbon::now()->addDays(3)->startOfHour();
        $producto->update(['precio' => '99.00']);

        $this->actingAs($operador, 'sanctum')
            ->patchJson("/api/v1/pedidos/{$pedido->id}", [
                'tipo_atencion' => TipoAtencionEnum::PROGRAMADO->value,
                'fecha_programada' => $programada->format('Y-m-d\TH:i'),
            ])
            ->assertOk()
            ->assertJsonPath('data.tipo_atencion', TipoAtencionEnum::PROGRAMADO->value)
            ->assertJsonPath('data.total', '10.00')
            ->assertJsonPath('data.detalles.0.precio_unitario', '10.00')
            ->assertJsonPath('message', 'Pedido actualizado correctamente.');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'tipo_atencion' => TipoAtencionEnum::PROGRAMADO->value,
            'total' => '10.00',
        ]);
    }

    public function test_staff_can_replace_order_lines_and_total_is_recalculated(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $original = Producto::factory()->create(['precio' => '8.00']);
        $replacement = Producto::factory()->create(['precio' => '3.50']);
        $pedido = $this->createOrder($cliente, $original, '8.00');

        $this->actingAs($operador, 'sanctum')
            ->patchJson("/api/v1/pedidos/{$pedido->id}", [
                'lineas' => [
                    ['producto_id' => $replacement->id, 'cantidad' => 3],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.total', '10.50')
            ->assertJsonPath('data.detalles.0.producto_id', $replacement->id)
            ->assertJsonPath('data.detalles.0.subtotal', '10.50');
    }

    public function test_customer_can_cancel_their_own_registered_order(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->postJson("/api/v1/pedidos/{$pedido->id}/cancelacion")
            ->assertOk()
            ->assertJsonPath('data.estado', EstadoPedidoEnum::CANCELADO->value)
            ->assertJsonPath('message', 'Pedido cancelado correctamente.');
    }

    public function test_staff_can_advance_order_status_and_invalid_transition_returns_409(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);

        $this->actingAs($operador, 'sanctum')
            ->patchJson("/api/v1/pedidos/{$pedido->id}/estado", [
                'estado' => EstadoPedidoEnum::EN_PREPARACION->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.estado', EstadoPedidoEnum::EN_PREPARACION->value);

        $this->patchJson("/api/v1/pedidos/{$pedido->id}/estado", [
            'estado' => EstadoPedidoEnum::ENTREGADO->value,
        ])
            ->assertStatus(409)
            ->assertJsonPath('data', null)
            ->assertJsonPath(
                'message',
                'El estado del pedido no permite la transición solicitada.',
            )
            ->assertJsonPath(
                'meta.errors.estado.0',
                'La transición de estado solicitada no está permitida.',
            );
    }

    public function test_customer_cannot_change_status_and_missing_orders_return_404(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->patchJson("/api/v1/pedidos/{$pedido->id}/estado", [
                'estado' => EstadoPedidoEnum::EN_PREPARACION->value,
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Acceso denegado.');

        $this->patchJson('/api/v1/pedidos/999999/estado', [
            'estado' => EstadoPedidoEnum::EN_PREPARACION->value,
        ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }

    public function test_guest_cannot_access_orders_api(): void
    {
        $this->getJson('/api/v1/pedidos')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');
    }

    private function createOrder(
        Cliente $cliente,
        ?Producto $producto = null,
        string $price = '7.25',
    ): Pedido {
        $producto ??= Producto::factory()->create(['precio' => $price]);
        $pedido = Pedido::factory()
            ->inmediato()
            ->sinMesa()
            ->create([
                'usuario_id' => $cliente->usuario_id,
                'cliente_id' => $cliente->id,
                'total' => $price,
            ]);
        $pedido->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => $price,
            'subtotal' => $price,
        ]);

        return $pedido;
    }
}
