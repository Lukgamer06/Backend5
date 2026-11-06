<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\CanalPagoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_an_online_payment_intent_with_server_amount(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente, '123.45');

        $this->actingAs($cliente->usuario, 'sanctum')
            ->withHeader('Idempotency-Key', 'online-payment-001')
            ->postJson("/api/v1/pedidos/{$pedido->id}/pagos/enlinea")
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Solicitud de pago en línea registrada pendiente de procesamiento.',
            )
            ->assertJsonPath('data.pedido_id', $pedido->id)
            ->assertJsonPath('data.monto', '123.45')
            ->assertJsonPath('data.canal', CanalPagoEnum::EN_LINEA->value)
            ->assertJsonPath('data.estado', EstadoPagoEnum::PENDIENTE->value)
            ->assertJsonStructure(['data' => ['id', 'referencia_operacion'], 'message', 'meta'])
            ->assertJsonMissingPath('data.clave_idempotencia');

        $this->assertDatabaseHas('pagos', [
            'pedido_id' => $pedido->id,
            'monto' => '123.45',
            'canal' => CanalPagoEnum::EN_LINEA->value,
            'estado' => EstadoPagoEnum::PENDIENTE->value,
            'clave_idempotencia' => 'online-payment-001',
            'proveedor' => null,
            'transaccion_externa' => null,
        ]);
    }

    public function test_customer_can_record_an_in_person_payment_as_pending_confirmation(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente, '49.90');

        $this->actingAs($cliente->usuario, 'sanctum')
            ->withHeader('Idempotency-Key', 'in-person-payment-001')
            ->postJson("/api/v1/pedidos/{$pedido->id}/pagos/presenciales")
            ->assertCreated()
            ->assertJsonPath('message', 'Pago presencial registrado pendiente de confirmación.')
            ->assertJsonPath('data.canal', CanalPagoEnum::PRESENCIAL->value)
            ->assertJsonPath('data.estado', EstadoPagoEnum::PENDIENTE->value)
            ->assertJsonPath('data.monto', '49.90');

        $this->assertDatabaseHas('pagos', [
            'pedido_id' => $pedido->id,
            'canal' => CanalPagoEnum::PRESENCIAL->value,
            'estado' => EstadoPagoEnum::PENDIENTE->value,
            'proveedor' => null,
            'transaccion_externa' => null,
        ]);
    }

    public function test_repeating_the_same_idempotency_key_returns_the_existing_payment(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);
        $endpoint = "/api/v1/pedidos/{$pedido->id}/pagos/enlinea";

        $firstResponse = $this->actingAs($cliente->usuario, 'sanctum')
            ->withHeader('Idempotency-Key', 'same-payment-request')
            ->postJson($endpoint)
            ->assertCreated();

        $paymentId = $firstResponse->json('data.id');

        $this->withHeader('Idempotency-Key', 'same-payment-request')
            ->postJson($endpoint)
            ->assertOk()
            ->assertJsonPath('data.id', $paymentId)
            ->assertJsonPath(
                'message',
                'Solicitud idempotente: se devuelve el pago existente.',
            );

        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_reusing_an_idempotency_key_for_a_different_request_returns_409(): void
    {
        $cliente = Cliente::factory()->create();
        $firstOrder = $this->createOrder($cliente);
        $secondOrder = $this->createOrder($cliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->withHeader('Idempotency-Key', 'unique-request-key')
            ->postJson("/api/v1/pedidos/{$firstOrder->id}/pagos/enlinea")
            ->assertCreated();

        $this->withHeader('Idempotency-Key', 'unique-request-key')
            ->postJson("/api/v1/pedidos/{$secondOrder->id}/pagos/enlinea")
            ->assertStatus(409)
            ->assertJsonPath('data', null)
            ->assertJsonPath(
                'message',
                'La clave de idempotencia ya fue utilizada para otro pedido o canal.',
            );

        $this->withHeader('Idempotency-Key', 'unique-request-key')
            ->postJson("/api/v1/pedidos/{$firstOrder->id}/pagos/presenciales")
            ->assertStatus(409);

        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_order_owner_can_list_payments_but_another_customer_cannot(): void
    {
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);
        $pago = Pago::factory()->create([
            'pedido_id' => $pedido->id,
            'monto' => $pedido->total,
        ]);
        $otherOrder = $this->createOrder($otroCliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->getJson("/api/v1/pedidos/{$pedido->id}/pagos")
            ->assertOk()
            ->assertJsonPath('message', 'Pagos del pedido consultados correctamente.')
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $pago->id)
            ->assertJsonStructure(['data', 'links', 'meta', 'message']);

        $this->getJson("/api/v1/pedidos/{$otherOrder->id}/pagos")
            ->assertForbidden()
            ->assertJsonPath('message', 'Acceso denegado.');
    }

    public function test_only_administrators_can_read_a_payment_by_id(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);
        $pago = Pago::factory()->create(['pedido_id' => $pedido->id]);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->getJson("/api/v1/pagos/{$pago->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Acceso denegado.');

        $administrator = Usuario::factory()->administrador()->create();

        $this->actingAs($administrator, 'sanctum')
            ->getJson("/api/v1/pagos/{$pago->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $pago->id)
            ->assertJsonPath('data.pedido_id', $pedido->id)
            ->assertJsonMissingPath('data.clave_idempotencia');
    }

    public function test_payment_creation_requires_a_valid_idempotency_key_and_authentication(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->postJson("/api/v1/pedidos/{$pedido->id}/pagos/enlinea", [
                'clave_idempotencia' => 'body-controlled-key',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.idempotency_key.0', 'The idempotency key field is required.')
            ->assertJsonPath('meta.errors.clave_idempotencia.0', 'The clave idempotencia field is prohibited.');

    }

    public function test_payment_creation_rejects_client_supplied_amount_channel_and_provider(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->withHeader('Idempotency-Key', 'protected-payment-fields')
            ->postJson("/api/v1/pedidos/{$pedido->id}/pagos/enlinea", [
                'monto' => '0.01',
                'canal' => CanalPagoEnum::PRESENCIAL->value,
                'proveedor' => 'forged-provider',
                'transaccion_externa' => 'forged-transaction',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.monto.0', 'The monto field is prohibited.')
            ->assertJsonPath('meta.errors.canal.0', 'The canal field is prohibited.')
            ->assertJsonPath('meta.errors.proveedor.0', 'The proveedor field is prohibited.')
            ->assertJsonPath(
                'meta.errors.transaccion_externa.0',
                'The transaccion externa field is prohibited.',
            );

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_guest_cannot_create_a_payment(): void
    {
        $cliente = Cliente::factory()->create();
        $pedido = $this->createOrder($cliente);

        $this->postJson("/api/v1/pedidos/{$pedido->id}/pagos/presenciales")
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');
    }

    public function test_customer_cannot_create_a_payment_for_another_customers_order(): void
    {
        $customer = Cliente::factory()->create();
        $otherCustomer = Cliente::factory()->create();
        $otherOrder = $this->createOrder($otherCustomer);

        $this->actingAs($customer->usuario, 'sanctum')
            ->withHeader('Idempotency-Key', 'unauthorized-payment')
            ->postJson("/api/v1/pedidos/{$otherOrder->id}/pagos/enlinea")
            ->assertForbidden()
            ->assertJsonPath('message', 'Acceso denegado.');

        $this->assertDatabaseCount('pagos', 0);
    }

    private function createOrder(Cliente $cliente, string $total = '25.00'): Pedido
    {
        return Pedido::factory()
            ->inmediato()
            ->sinMesa()
            ->create([
                'usuario_id' => $cliente->usuario_id,
                'cliente_id' => $cliente->id,
                'tipo_atencion' => TipoAtencionEnum::INMEDIATO,
                'modalidad_servicio' => ModalidadServicioEnum::SIN_MESA,
                'total' => $total,
            ]);
    }
}
