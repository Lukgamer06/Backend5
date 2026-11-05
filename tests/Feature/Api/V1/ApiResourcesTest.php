<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\DTOs\Api\V1\AuthenticatedApiSession;
use App\Enums\CanalPagoEnum;
use App\Enums\EstadoPedidoEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\RolUsuarioEnum;
use App\Http\Resources\Api\V1\AuthSessionResource;
use App\Http\Resources\Api\V1\ClienteResource;
use App\Http\Resources\Api\V1\IncidenciaResource;
use App\Http\Resources\Api\V1\MenuDiaResource;
use App\Http\Resources\Api\V1\OrderCollection;
use App\Http\Resources\Api\V1\PagoResource;
use App\Http\Resources\Api\V1\PedidoResource;
use App\Http\Resources\Api\V1\ProductCollection;
use App\Http\Resources\Api\V1\ProductoResource;
use App\Http\Resources\Api\V1\ReservaResource;
use App\Http\Resources\Api\V1\ReservationCollection;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\Cliente;
use App\Models\Incidencia;
use App\Models\MenuDia;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ApiResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_resource_exposes_only_public_user_fields(): void
    {
        $usuario = Usuario::factory()->create([
            'password' => 'sensitive-password',
            'rol' => RolUsuarioEnum::CLIENTE,
        ]);
        $usuario->makeVisible(['password', 'remember_token']);

        $response = UsuarioResource::make($usuario)->response()->getData(true);

        $this->assertSame($usuario->id, $response['data']['id']);
        $this->assertSame('CLIENTE', $response['data']['rol']);
        $this->assertArrayNotHasKey('password', $response['data']);
        $this->assertArrayNotHasKey('remember_token', $response['data']);
        $this->assertSame('Solicitud exitosa.', $response['message']);
        $this->assertSame([], $response['meta']);
    }

    public function test_cliente_resource_includes_usuario_only_when_loaded(): void
    {
        $cliente = Cliente::factory()->create();

        $withoutRelation = ClienteResource::make($cliente)->resolve(request());
        $this->assertArrayNotHasKey('usuario', $withoutRelation);

        $withRelation = ClienteResource::make($cliente->load('usuario'))->resolve(request());
        $this->assertSame($cliente->usuario_id, $withRelation['usuario']['id']);
    }

    public function test_reservation_resource_serializes_enums_dates_and_loaded_relations(): void
    {
        $reserva = Reserva::factory()->create([
            'estado' => EstadoReservaEnum::CONFIRMADA,
            'fecha_inicio' => '2026-12-01 10:00:00',
            'fecha_fin' => '2026-12-01 11:00:00',
        ])->load(['usuario', 'cliente']);

        $response = ReservaResource::make($reserva)->response()->getData(true);

        $this->assertSame('CONFIRMADA', $response['data']['estado']);
        $this->assertStringContainsString('2026-12-01T10:00:00', $response['data']['fecha_inicio']);
        $this->assertSame($reserva->usuario_id, $response['data']['usuario']['id']);
        $this->assertSame($reserva->cliente_id, $response['data']['cliente']['id']);
        $this->assertSame('Solicitud exitosa.', $response['message']);
    }

    public function test_pedido_resource_includes_loaded_details_products_and_payments_without_secrets(): void
    {
        $pedido = Pedido::factory()
            ->inmediato()
            ->create(['estado' => EstadoPedidoEnum::REGISTRADO]);
        $producto = Producto::factory()->create();
        $pedido->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => '12.50',
            'subtotal' => '25.00',
            'observaciones' => null,
        ]);
        $pago = Pago::factory()->create([
            'pedido_id' => $pedido->id,
            'canal' => CanalPagoEnum::PRESENCIAL,
            'clave_idempotencia' => 'private-idempotency-key',
            'transaccion_externa' => null,
        ]);

        $pedido->load(['cliente', 'detalles.producto', 'pagos']);
        $response = PedidoResource::make($pedido)->response()->getData(true);

        $this->assertSame('REGISTRADO', $response['data']['estado']);
        $this->assertSame($pedido->cliente_id, $response['data']['cliente']['id']);
        $this->assertSame($producto->id, $response['data']['detalles'][0]['producto']['id']);
        $this->assertSame('12.50', $response['data']['detalles'][0]['precio_unitario']);
        $this->assertSame($pago->id, $response['data']['pagos'][0]['id']);
        $this->assertArrayNotHasKey('clave_idempotencia', $response['data']['pagos'][0]);
    }

    public function test_producto_resource_uses_an_explicit_allowlist(): void
    {
        $producto = Producto::factory()->create();

        $data = ProductoResource::make($producto)->resolve(request());

        $this->assertSame([
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'precio' => $producto->precio,
            'disponible' => $producto->disponible,
        ], $data);
    }

    public function test_menu_dia_resource_includes_products_only_when_loaded(): void
    {
        $menu = MenuDia::factory()->create();
        $producto = Producto::factory()->create();
        $menu->productos()->attach($producto);

        $withoutRelation = MenuDiaResource::make($menu)->resolve(request());
        $this->assertArrayNotHasKey('productos', $withoutRelation);

        $withRelation = MenuDiaResource::make($menu->load('productos'))->resolve(request());
        $this->assertSame($producto->id, $withRelation['productos'][0]['id']);
    }

    public function test_incident_resource_returns_reporter_and_optional_foreign_keys(): void
    {
        $incidencia = Incidencia::factory()->create([
            'mesa_id' => null,
            'pedido_id' => null,
        ])->load('usuario');

        $data = IncidenciaResource::make($incidencia)->resolve(request());

        $this->assertSame($incidencia->usuario_reporta_id, $data['usuario']['id']);
        $this->assertNull($data['mesa_id']);
        $this->assertNull($data['pedido_id']);
        $this->assertArrayNotHasKey('password', $data['usuario']);
    }

    public function test_payment_resource_omits_idempotency_and_external_transaction_credentials(): void
    {
        $pago = Pago::factory()->create([
            'clave_idempotencia' => 'private-idempotency-key',
            'transaccion_externa' => 'private-external-transaction',
        ]);

        $data = PagoResource::make($pago)->resolve(request());

        $this->assertSame($pago->referencia_operacion, $data['referencia_operacion']);
        $this->assertArrayNotHasKey('clave_idempotencia', $data);
        $this->assertArrayNotHasKey('transaccion_externa', $data);
        $this->assertArrayNotHasKey('proveedor', $data);
    }

    public function test_auth_session_resource_uses_the_shared_usuario_resource(): void
    {
        $usuario = Usuario::factory()->create();
        $dto = new AuthenticatedApiSession($usuario, 'plain-text-token');

        $response = AuthSessionResource::make($dto)->additional([
            'message' => 'Autenticación exitosa.',
            'meta' => (object) [],
        ])->response()->getData(true);

        $this->assertSame($usuario->id, $response['data']['usuario']['id']);
        $this->assertSame('plain-text-token', $response['data']['access_token']);
        $this->assertArrayNotHasKey('password', $response['data']['usuario']);
    }

    public function test_domain_collections_use_their_resource_and_paginated_links_meta_shape(): void
    {
        $reservas = Reserva::factory()->count(2)->create();
        $pedidos = Pedido::factory()->count(2)->create();
        $productos = Producto::factory()->count(2)->create();

        $reservationResponse = $this->paginatedCollectionResponse(
            ReservationCollection::make($this->paginator($reservas)),
        );
        $orderResponse = $this->paginatedCollectionResponse(
            OrderCollection::make($this->paginator($pedidos)),
        );
        $productResponse = $this->paginatedCollectionResponse(
            ProductCollection::make($this->paginator($productos)),
        );

        foreach ([$reservationResponse, $orderResponse, $productResponse] as $response) {
            $this->assertCount(2, $response['data']);
            $this->assertArrayHasKey('first', $response['links']);
            $this->assertSame(2, $response['meta']['total']);
        }

        $this->assertArrayHasKey('fecha_inicio', $reservationResponse['data'][0]);
        $this->assertArrayHasKey('tipo_atencion', $orderResponse['data'][0]);
        $this->assertArrayHasKey('precio', $productResponse['data'][0]);
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return LengthAwarePaginator<int, mixed>
     */
    private function paginator(Collection $items): LengthAwarePaginator
    {
        return new Paginator($items, $items->count(), $items->count(), 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function paginatedCollectionResponse(ResourceCollection $collection): array
    {
        return $collection->response()->getData(true);
    }
}
