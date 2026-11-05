<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoPedidoEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('orders.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_create_update_and_delete_an_order_with_details(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create();
        $producto = Producto::factory()->create(['precio' => '12.35']);
        $payload = $this->orderPayload($cliente, [
            $this->line($producto),
        ], [
            'modalidad_servicio' => ModalidadServicioEnum::EN_MESA->value,
            'mesa_id' => $mesa->id,
        ]);

        $this->actingAs($operador)
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee($producto->nombre)
            ->assertSee('add-order-line')
            ->assertSee('order-line-template');

        $this->post(route('orders.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $pedido = Pedido::query()->sole();
        $this->assertSame($operador->id, $pedido->usuario_id);
        $this->assertSame($cliente->id, $pedido->cliente_id);
        $this->assertSame('24.70', $pedido->total);
        $this->assertSame(EstadoPedidoEnum::REGISTRADO, $pedido->estado);
        $this->assertDatabaseHas('detalle_pedidos', [
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => '12.35',
            'subtotal' => '24.70',
        ]);

        $this->get(route('orders.show', $pedido))
            ->assertOk()
            ->assertSee($producto->nombre)
            ->assertSee('24,70');
        $this->get(route('orders.edit', $pedido))
            ->assertOk()
            ->assertSee('Guardar cambios');

        $secondProduct = Producto::factory()->create(['precio' => '4.20']);
        $this->put(route('orders.update', $pedido), $this->orderPayload(
            $cliente,
            [$this->line($secondProduct, 3)],
            ['estado' => EstadoPedidoEnum::EN_PREPARACION->value],
        ))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.show', $pedido));

        $pedido->refresh();
        $this->assertSame('12.60', $pedido->total);
        $this->assertSame(EstadoPedidoEnum::EN_PREPARACION, $pedido->estado);
        $this->assertDatabaseMissing('detalle_pedidos', [
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
        ]);
        $this->assertDatabaseHas('detalle_pedidos', [
            'pedido_id' => $pedido->id,
            'producto_id' => $secondProduct->id,
            'cantidad' => 3,
        ]);

        $this->actingAs(Usuario::factory()->administrador()->create())
            ->delete(route('orders.destroy', $pedido))
            ->assertRedirect(route('orders.index'));
        $this->assertDatabaseMissing('pedidos', ['id' => $pedido->id]);
        $this->assertDatabaseMissing('detalle_pedidos', ['pedido_id' => $pedido->id]);
    }

    public function test_customer_can_only_see_and_create_own_orders(): void
    {
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create([
            'nombres' => 'Cliente Pedido Privado',
        ]);
        $cliente->update(['nombres' => 'Cliente Pedido Propio']);
        $ownProduct = Producto::factory()->create();
        $otherProduct = Producto::factory()->create();
        $ownOrder = $this->createOrder($cliente, $ownProduct);
        $otherOrder = $this->createOrder($otroCliente, $otherProduct);

        $this->actingAs($cliente->usuario)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Cliente Pedido Propio')
            ->assertDontSee('Cliente Pedido Privado')
            ->assertSee('Nuevo pedido');

        $this->get(route('orders.show', $otherOrder))
            ->assertForbidden();

        $this->get(route('orders.create'))
            ->assertOk()
            ->assertDontSee('name="cliente_id"', false);

        $this->post(route('orders.store'), $this->orderPayload(
            $otroCliente,
            [$this->line(Producto::factory()->create())],
        ))->assertSessionHasErrors('cliente_id');

        $this->put(route('orders.update', $ownOrder), [])
            ->assertForbidden();
        $this->delete(route('orders.destroy', $ownOrder))
            ->assertForbidden();
    }

    public function test_customer_can_cancel_own_registered_order_but_not_another_order(): void
    {
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $product = Producto::factory()->create();
        $ownOrder = $this->createOrder($cliente, $product);
        $otherOrder = $this->createOrder($otroCliente, $product);

        $this->actingAs($cliente->usuario)
            ->patch(route('orders.cancel', $ownOrder))
            ->assertRedirect(route('orders.show', $ownOrder));

        $this->assertDatabaseHas('pedidos', [
            'id' => $ownOrder->id,
            'estado' => EstadoPedidoEnum::CANCELADO->value,
        ]);

        $this->patch(route('orders.cancel', $otherOrder))
            ->assertForbidden();

        $inPreparation = $this->createOrder($cliente, $product);
        $inPreparation->update(['estado' => EstadoPedidoEnum::EN_PREPARACION]);
        $this->patch(route('orders.cancel', $inPreparation))
            ->assertForbidden();
    }

    public function test_staff_can_register_a_scheduled_order_linked_to_the_customers_reservation(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create();
        $reserva = Reserva::factory()->create([
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa->id,
        ]);
        $producto = Producto::factory()->create(['precio' => '20.00']);
        $fechaProgramada = Carbon::now()->addDays(2)->format('Y-m-d\TH:i');

        $this->actingAs($operador)
            ->post(route('orders.store'), $this->orderPayload(
                $cliente,
                [$this->line($producto)],
                [
                    'tipo_atencion' => TipoAtencionEnum::PROGRAMADO->value,
                    'modalidad_servicio' => ModalidadServicioEnum::EN_MESA->value,
                    'fecha_programada' => $fechaProgramada,
                    'mesa_id' => $mesa->id,
                    'reserva_id' => $reserva->id,
                ],
            ))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('pedidos', [
            'cliente_id' => $cliente->id,
            'reserva_id' => $reserva->id,
            'mesa_id' => $mesa->id,
            'tipo_atencion' => TipoAtencionEnum::PROGRAMADO->value,
            'modalidad_servicio' => ModalidadServicioEnum::EN_MESA->value,
            'fecha_programada' => Carbon::parse($fechaProgramada)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_staff_status_transitions_are_validated(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $product = Producto::factory()->create();
        $pedido = $this->createOrder($cliente, $product);

        $this->actingAs($operador)
            ->patch(route('orders.status', $pedido), [
                'estado' => EstadoPedidoEnum::EN_PREPARACION->value,
            ])
            ->assertRedirect(route('orders.show', $pedido));

        $this->patch(route('orders.status', $pedido), [
            'estado' => EstadoPedidoEnum::ENTREGADO->value,
        ])->assertSessionHasErrors('estado');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'estado' => EstadoPedidoEnum::EN_PREPARACION->value,
        ]);
    }

    public function test_programmed_orders_require_a_future_date_and_only_available_products(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $unavailable = Producto::factory()->noDisponible()->create();
        $payload = $this->orderPayload($cliente, [$this->line($unavailable)], [
            'tipo_atencion' => TipoAtencionEnum::PROGRAMADO->value,
            'fecha_programada' => Carbon::now()->subDay()->format('Y-m-d\TH:i'),
        ]);

        $this->actingAs($operador)
            ->from(route('orders.create'))
            ->post(route('orders.store'), $payload)
            ->assertRedirect(route('orders.create'))
            ->assertSessionHasErrors(['fecha_programada', 'lineas.0.producto_id']);
    }

    public function test_order_reservation_must_belong_to_selected_customer_and_table(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create();
        $otraMesa = Mesa::factory()->create();
        $reserva = Reserva::factory()->create([
            'cliente_id' => $otroCliente->id,
            'mesa_id' => $mesa->id,
        ]);
        $product = Producto::factory()->create();

        $this->actingAs($operador)
            ->from(route('orders.create'))
            ->post(route('orders.store'), $this->orderPayload($cliente, [
                $this->line($product),
            ], [
                'modalidad_servicio' => ModalidadServicioEnum::EN_MESA->value,
                'mesa_id' => $otraMesa->id,
                'reserva_id' => $reserva->id,
            ]))
            ->assertRedirect(route('orders.create'))
            ->assertSessionHasErrors('reserva_id');

        $reservaPropia = Reserva::factory()->create([
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa->id,
        ]);
        $this->post(route('orders.store'), $this->orderPayload($cliente, [
            $this->line($product),
        ], [
            'modalidad_servicio' => ModalidadServicioEnum::EN_MESA->value,
            'mesa_id' => $otraMesa->id,
            'reserva_id' => $reservaPropia->id,
        ]))
            ->assertRedirect(route('orders.create'))
            ->assertSessionHasErrors('mesa_id');
    }

    public function test_order_filters_and_pagination_are_applied(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $product = Producto::factory()->create();
        $target = $this->createOrder($cliente, $product);
        Pedido::factory()->count(15)->create();

        $this->actingAs($operador)
            ->get(route('orders.index', [
                'search' => (string) $target->id,
                'estado' => EstadoPedidoEnum::REGISTRADO->value,
                'tipo_atencion' => TipoAtencionEnum::INMEDIATO->value,
                'fecha_desde' => Carbon::now()->toDateString(),
                'fecha_hasta' => Carbon::now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas(
                'orders',
                fn ($orders): bool => $orders->total() === 1
                    && $orders->contains('id', $target->id),
            );

        $this->get(route('orders.index'))
            ->assertViewHas(
                'orders',
                fn ($orders): bool => $orders->total() === 16
                    && $orders->perPage() === 15
                    && $orders->count() === 15,
            );
    }

    public function test_paid_order_cannot_be_edited_deleted_or_cancelled_but_can_advance_status(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create();
        $product = Producto::factory()->create();
        $pedido = $this->createOrder($cliente, $product);
        Pago::factory()->create(['pedido_id' => $pedido->id]);

        $this->actingAs($administrador)
            ->get(route('orders.edit', $pedido))
            ->assertForbidden();
        $this->delete(route('orders.destroy', $pedido))
            ->assertSessionHasErrors('pedido');
        $this->patch(route('orders.status', $pedido), [
            'estado' => EstadoPedidoEnum::EN_PREPARACION->value,
        ])->assertRedirect(route('orders.show', $pedido));
        $this->patch(route('orders.cancel', $pedido))
            ->assertForbidden();
    }

    private function createOrder(Cliente $cliente, Producto $producto): Pedido
    {
        $usuario = $cliente->usuario;

        $pedido = Pedido::factory()->create([
            'usuario_id' => $usuario->id,
            'cliente_id' => $cliente->id,
            'tipo_atencion' => TipoAtencionEnum::INMEDIATO,
            'modalidad_servicio' => ModalidadServicioEnum::SIN_MESA,
            'mesa_id' => null,
            'fecha_programada' => null,
        ]);
        $pedido->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => $producto->precio,
            'subtotal' => $producto->precio,
        ]);

        return $pedido;
    }

    /**
     * @return array{producto_id: int, cantidad: int}
     */
    private function line(Producto $producto, int $cantidad = 2): array
    {
        return [
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
        ];
    }

    /**
     * @param  list<array<string, int>>  $lines
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function orderPayload(
        Cliente $cliente,
        array $lines,
        array $attributes = [],
    ): array {
        return [
            'cliente_id' => $cliente->id,
            'tipo_atencion' => TipoAtencionEnum::INMEDIATO->value,
            'modalidad_servicio' => ModalidadServicioEnum::SIN_MESA->value,
            'fecha_programada' => '',
            'mesa_id' => '',
            'reserva_id' => '',
            'lineas' => $lines,
            ...$attributes,
        ];
    }
}
