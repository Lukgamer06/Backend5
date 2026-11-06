<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\EstadoReservaEnum;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_only_their_reservations_with_pagination_envelope(): void
    {
        $cliente = Cliente::factory()->create();
        $otraCliente = Cliente::factory()->create();
        $propia = $this->createReservation($cliente);
        $this->createReservation($otraCliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->getJson('/api/v1/reservas?per_page=1')
            ->assertOk()
            ->assertJsonPath('message', 'Reservas consultadas correctamente.')
            ->assertJsonPath('data.0.id', $propia->id)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure(['data', 'links', 'meta', 'message']);
    }

    public function test_customer_can_read_their_reservation_but_not_another_customers_reservation(): void
    {
        $cliente = Cliente::factory()->create();
        $otraCliente = Cliente::factory()->create();
        $propia = $this->createReservation($cliente);
        $ajena = $this->createReservation($otraCliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->getJson("/api/v1/reservas/{$propia->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $propia->id)
            ->assertJsonStructure(['data' => ['id', 'estado'], 'message', 'meta']);

        $this->getJson("/api/v1/reservas/{$ajena->id}")
            ->assertForbidden()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Acceso denegado.');
    }

    public function test_customer_can_create_a_reservation_for_their_own_profile(): void
    {
        $cliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create(['capacidad' => 4]);
        $inicio = Carbon::now()->addDays(8)->startOfHour();

        $this->actingAs($cliente->usuario, 'sanctum')
            ->postJson('/api/v1/reservas', [
                'mesa_id' => $mesa->id,
                'fecha_inicio' => $inicio->format('Y-m-d\TH:i'),
                'fecha_fin' => $inicio->copy()->addHour()->format('Y-m-d\TH:i'),
                'numero_personas' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Reserva creada correctamente.')
            ->assertJsonPath('data.cliente_id', $cliente->id)
            ->assertJsonPath('data.usuario_id', $cliente->usuario_id)
            ->assertJsonStructure(['data' => ['id', 'estado'], 'message', 'meta']);

        $this->assertDatabaseHas('reservas', [
            'cliente_id' => $cliente->id,
            'usuario_id' => $cliente->usuario_id,
            'mesa_id' => $mesa->id,
        ]);
    }

    public function test_store_rejects_protected_fields_with_a_standardized_validation_response(): void
    {
        $cliente = Cliente::factory()->create();
        $inicio = Carbon::now()->addDays(8)->startOfHour();

        $this->actingAs($cliente->usuario, 'sanctum')
            ->postJson('/api/v1/reservas', [
                'usuario_id' => $cliente->usuario_id,
                'estado' => EstadoReservaEnum::ACTIVA->value,
                'mesa_id' => Mesa::factory()->create()->id,
                'fecha_inicio' => $inicio->format('Y-m-d\TH:i'),
                'fecha_fin' => $inicio->copy()->addHour()->format('Y-m-d\TH:i'),
                'numero_personas' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Los datos enviados no son válidos.')
            ->assertJsonStructure(['data', 'message', 'meta' => ['errors']]);
    }

    public function test_staff_can_patch_only_the_fields_sent_by_the_client(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $reserva = $this->createReservation($cliente);
        $fechaInicio = $reserva->fecha_inicio->toIso8601String();

        $this->actingAs($operador, 'sanctum')
            ->patchJson("/api/v1/reservas/{$reserva->id}", [
                'observaciones' => 'Llegaremos temprano.',
            ])
            ->assertOk()
            ->assertJsonPath('data.observaciones', 'Llegaremos temprano.')
            ->assertJsonPath('data.fecha_inicio', $fechaInicio)
            ->assertJsonPath('message', 'Reserva actualizada correctamente.');

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'observaciones' => 'Llegaremos temprano.',
        ]);
    }

    public function test_customer_can_cancel_their_reservation(): void
    {
        $cliente = Cliente::factory()->create();
        $reserva = $this->createReservation($cliente);

        $this->actingAs($cliente->usuario, 'sanctum')
            ->postJson("/api/v1/reservas/{$reserva->id}/cancelacion")
            ->assertOk()
            ->assertJsonPath('data.estado', EstadoReservaEnum::CANCELADA->value)
            ->assertJsonPath('message', 'Reserva cancelada correctamente.');

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => EstadoReservaEnum::CANCELADA->value,
        ]);
    }

    public function test_availability_conflict_returns_a_standardized_409_response(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = Mesa::factory()->create();
        $reservaExistente = $this->createReservation($cliente, $mesa);
        $inicio = $reservaExistente->fecha_inicio->copy()->addMinutes(30);

        $this->actingAs($operador, 'sanctum')
            ->postJson('/api/v1/reservas', [
                'cliente_id' => Cliente::factory()->create()->id,
                'mesa_id' => $mesa->id,
                'fecha_inicio' => $inicio->format('Y-m-d\TH:i'),
                'fecha_fin' => $inicio->copy()->addHour()->format('Y-m-d\TH:i'),
                'numero_personas' => 2,
            ])
            ->assertStatus(409)
            ->assertJsonPath('data', null)
            ->assertJsonPath(
                'message',
                'La reserva entra en conflicto con la disponibilidad de la mesa.',
            )
            ->assertJsonPath(
                'meta.errors.mesa_id.0',
                'La mesa ya tiene una reserva en el horario seleccionado.',
            );
    }

    public function test_reservation_api_requires_authentication_and_returns_404_for_missing_records(): void
    {
        $this->getJson('/api/v1/reservas')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');

        $usuario = Usuario::factory()->administrador()->create();

        $this->actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/reservas/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }

    private function createReservation(
        Cliente $cliente,
        ?Mesa $mesa = null,
    ): Reserva {
        $inicio = Carbon::now()->addDays(15)->startOfHour();

        return Reserva::factory()->create([
            'usuario_id' => $cliente->usuario_id,
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa?->id ?? Mesa::factory()->create()->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $inicio->copy()->addHour(),
        ]);
    }
}
