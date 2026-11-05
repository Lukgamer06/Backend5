<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use App\Http\Requests\Api\V1\Incidents\StoreIncidentApiRequest;
use App\Http\Requests\Api\V1\Incidents\UpdateIncidentApiRequest;
use App\Http\Requests\Api\V1\Orders\IndexOrderApiRequest;
use App\Http\Requests\Api\V1\Orders\StoreOrderApiRequest;
use App\Http\Requests\Api\V1\Orders\UpdateOrderApiRequest;
use App\Http\Requests\Api\V1\Orders\UpdateOrderStatusApiRequest;
use App\Http\Requests\Api\V1\Payments\CreatePaymentApiRequest;
use App\Http\Requests\Api\V1\Products\IndexProductApiRequest;
use App\Http\Requests\Api\V1\Reservations\IndexReservationApiRequest;
use App\Http\Requests\Api\V1\Reservations\StoreReservationApiRequest;
use App\Http\Requests\Api\V1\Reservations\UpdateReservationApiRequest;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiFormRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_form_requests_require_an_authenticated_usuario(): void
    {
        $request = $this->makeRequest(IndexOrderApiRequest::class);
        $this->assertFalse($request->authorize());

        $request = $this->makeRequest(
            IndexOrderApiRequest::class,
            user: Usuario::factory()->create(),
        );
        $this->assertTrue($request->authorize());
    }

    public function test_customer_cannot_choose_reservation_owner_or_status(): void
    {
        $cliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create();
        $request = $this->validatorFor(
            StoreReservationApiRequest::class,
            [
                'mesa_id' => $mesa->id,
                'fecha_inicio' => '2026-12-01T10:00',
                'fecha_fin' => '2026-12-01T11:00',
                'numero_personas' => 2,
                'cliente_id' => $cliente->id,
                'estado' => 'CONFIRMADA',
            ],
            $cliente->usuario,
        );

        $this->assertTrue($request->fails());
        $this->assertArrayHasKey('cliente_id', $request->errors()->toArray());
        $this->assertArrayHasKey('estado', $request->errors()->toArray());
    }

    public function test_staff_can_assign_a_customer_and_reservation_update_accepts_a_partial_patch(): void
    {
        $usuario = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $store = $this->validatorFor(
            StoreReservationApiRequest::class,
            [
                'cliente_id' => $cliente->id,
                'mesa_id' => Mesa::factory()->create()->id,
                'fecha_inicio' => '2026-12-01T10:00',
                'fecha_fin' => '2026-12-01T11:00',
                'numero_personas' => 2,
            ],
            $usuario,
        );

        $this->assertFalse($store->fails(), $store->errors()->toJson());

        $patch = $this->validatorFor(
            UpdateReservationApiRequest::class,
            ['fecha_fin' => '2026-12-01T11:00'],
            $usuario,
        );
        $this->assertFalse($patch->fails(), $patch->errors()->toJson());

        $invalidDates = $this->validatorFor(
            UpdateReservationApiRequest::class,
            [
                'fecha_inicio' => '2026-12-01T12:00',
                'fecha_fin' => '2026-12-01T11:00',
            ],
            $usuario,
        );
        $this->assertTrue($invalidDates->fails());
        $this->assertArrayHasKey('fecha_fin', $invalidDates->errors()->toArray());
    }

    public function test_order_requests_validate_available_distinct_products_and_protect_server_fields(): void
    {
        $cliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create();
        $producto = Producto::factory()->create();
        $data = [
            'mesa_id' => $mesa->id,
            'tipo_atencion' => 'INMEDIATO',
            'modalidad_servicio' => 'EN_MESA',
            'lineas' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 2,
                    'observaciones' => null,
                ],
            ],
        ];

        $valid = $this->validatorFor(StoreOrderApiRequest::class, $data, $cliente->usuario);
        $this->assertFalse($valid->fails(), $valid->errors()->toJson());

        $withoutTable = $data;
        unset($withoutTable['mesa_id']);
        $missingTable = $this->validatorFor(
            StoreOrderApiRequest::class,
            $withoutTable,
            $cliente->usuario,
        );
        $this->assertTrue($missingTable->fails());
        $this->assertArrayHasKey('mesa_id', $missingTable->errors()->toArray());

        $scheduledWithoutDate = $this->validatorFor(
            StoreOrderApiRequest::class,
            [
                ...$data,
                'tipo_atencion' => 'PROGRAMADO',
            ],
            $cliente->usuario,
        );
        $this->assertTrue($scheduledWithoutDate->fails());
        $this->assertArrayHasKey('fecha_programada', $scheduledWithoutDate->errors()->toArray());

        $forged = $this->validatorFor(
            StoreOrderApiRequest::class,
            [...$data, 'total' => 1, 'estado' => 'ENTREGADO'],
            $cliente->usuario,
        );
        $this->assertTrue($forged->fails());
        $this->assertArrayHasKey('total', $forged->errors()->toArray());
        $this->assertArrayHasKey('estado', $forged->errors()->toArray());

        $unavailable = Producto::factory()->noDisponible()->create();
        $invalidProduct = $this->validatorFor(
            StoreOrderApiRequest::class,
            [
                ...$data,
                'lineas' => [[
                    'producto_id' => $unavailable->id,
                    'cantidad' => 1,
                ]],
            ],
            $cliente->usuario,
        );
        $this->assertTrue($invalidProduct->fails());
        $this->assertArrayHasKey('lineas.0.producto_id', $invalidProduct->errors()->toArray());
    }

    public function test_order_patch_and_status_requests_reject_protected_fields(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $update = $this->validatorFor(
            UpdateOrderApiRequest::class,
            ['lineas' => [['producto_id' => 1, 'cantidad' => 1]], 'estado' => 'ENTREGADO'],
            $operador,
        );
        $this->assertTrue($update->fails());
        $this->assertArrayHasKey('estado', $update->errors()->toArray());

        $scheduledPatch = $this->validatorFor(
            UpdateOrderApiRequest::class,
            ['tipo_atencion' => 'PROGRAMADO'],
            $operador,
        );
        $this->assertTrue($scheduledPatch->fails());
        $this->assertArrayHasKey('fecha_programada', $scheduledPatch->errors()->toArray());

        $status = $this->validatorFor(
            UpdateOrderStatusApiRequest::class,
            ['estado' => 'LISTO', 'total' => 1],
            $operador,
        );
        $this->assertTrue($status->fails());
        $this->assertArrayHasKey('total', $status->errors()->toArray());
    }

    public function test_index_requests_validate_filters_and_pagination_bounds(): void
    {
        $usuario = Usuario::factory()->create();
        $valid = $this->validatorFor(
            IndexReservationApiRequest::class,
            [
                'estado' => 'CONFIRMADA',
                'fecha_desde' => '2026-12-01',
                'fecha_hasta' => '2026-12-02',
                'page' => 1,
                'per_page' => 50,
            ],
            $usuario,
        );
        $this->assertFalse($valid->fails(), $valid->errors()->toJson());

        $dateUpperBoundOnly = $this->validatorFor(
            IndexReservationApiRequest::class,
            ['fecha_hasta' => '2026-12-01'],
            $usuario,
        );
        $this->assertFalse($dateUpperBoundOnly->fails(), $dateUpperBoundOnly->errors()->toJson());

        $invalid = $this->validatorFor(
            IndexOrderApiRequest::class,
            ['estado' => 'INVALIDO', 'fecha_desde' => '2026-12-02', 'fecha_hasta' => '2026-12-01', 'per_page' => 101],
            $usuario,
        );
        $this->assertTrue($invalid->fails());
        $errors = $invalid->errors()->toArray();
        $this->assertArrayHasKey('estado', $errors);
        $this->assertArrayHasKey('fecha_hasta', $errors);
        $this->assertArrayHasKey('per_page', $errors);

        $upperBoundOnly = $this->validatorFor(
            IndexOrderApiRequest::class,
            ['fecha_hasta' => '2026-12-01'],
            $usuario,
        );
        $this->assertFalse($upperBoundOnly->fails(), $upperBoundOnly->errors()->toJson());
    }

    public function test_only_administrators_can_filter_products_by_availability(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $adminFilter = $this->validatorFor(
            IndexProductApiRequest::class,
            ['disponible' => '0'],
            $admin,
        );
        $this->assertFalse($adminFilter->fails(), $adminFilter->errors()->toJson());

        $operador = Usuario::factory()->operador()->create();
        $staffFilter = $this->validatorFor(
            IndexProductApiRequest::class,
            ['disponible' => '0'],
            $operador,
        );
        $this->assertTrue($staffFilter->fails());
        $this->assertArrayHasKey('disponible', $staffFilter->errors()->toArray());
    }

    public function test_incident_requests_protect_reporter_and_allow_partial_updates(): void
    {
        $usuario = Usuario::factory()->operador()->create();
        $store = $this->validatorFor(
            StoreIncidentApiRequest::class,
            [
                'titulo' => 'Falla de iluminación',
                'descripcion' => 'La luz de la mesa no enciende.',
                'usuario_reporta_id' => $usuario->id,
                'estado' => 'RESUELTA',
            ],
            $usuario,
        );
        $this->assertTrue($store->fails());
        $this->assertArrayHasKey('usuario_reporta_id', $store->errors()->toArray());
        $this->assertArrayHasKey('estado', $store->errors()->toArray());

        $patch = $this->validatorFor(
            UpdateIncidentApiRequest::class,
            ['prioridad' => 'ALTA'],
            $usuario,
        );
        $this->assertFalse($patch->fails(), $patch->errors()->toJson());

        $emptyPatch = $this->validatorFor(UpdateIncidentApiRequest::class, [], $usuario);
        $this->assertTrue($emptyPatch->fails());
    }

    public function test_payment_uses_the_idempotency_header_and_does_not_accept_amount_or_provider_data(): void
    {
        $request = $this->makeRequest(
            CreatePaymentApiRequest::class,
            ['monto' => 1, 'proveedor' => 'cliente'],
            Usuario::factory()->create(),
            ['Idempotency-Key' => 'payment-request-001'],
        );
        $validator = Validator::make($request->validationData(), $request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('monto', $errors);
        $this->assertArrayHasKey('proveedor', $errors);

        $bodyKey = $this->validatorFor(
            CreatePaymentApiRequest::class,
            ['clave_idempotencia' => 'client-controlled-key'],
            Usuario::factory()->create(),
        );
        $this->assertTrue($bodyKey->fails());
        $this->assertArrayHasKey('clave_idempotencia', $bodyKey->errors()->toArray());

        $validRequest = $this->makeRequest(
            CreatePaymentApiRequest::class,
            [],
            Usuario::factory()->create(),
            ['Idempotency-Key' => 'payment-request-002'],
        );
        $valid = Validator::make($validRequest->validationData(), $validRequest->rules());
        $this->assertFalse($valid->fails(), $valid->errors()->toJson());
        $this->assertSame(
            'payment-request-002',
            $valid->validated()['idempotency_key'],
        );
    }

    /**
     * @param  class-string<AuthenticatedApiFormRequest>  $requestClass
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    private function makeRequest(
        string $requestClass,
        array $data = [],
        ?Usuario $user = null,
        array $headers = [],
    ): AuthenticatedApiFormRequest {
        $request = $requestClass::create('/api/v1/test', 'POST', $data);
        $request->setUserResolver(fn (): ?Usuario => $user);

        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        return $request;
    }

    /**
     * @param  class-string<AuthenticatedApiFormRequest>  $requestClass
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    private function validatorFor(
        string $requestClass,
        array $data,
        ?Usuario $user = null,
        array $headers = [],
    ): ValidatorContract {
        $request = $this->makeRequest($requestClass, $data, $user, $headers);

        return Validator::make($request->validationData(), $request->rules());
    }
}
