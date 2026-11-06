<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoOperativoMesaEnum;
use App\Enums\EstadoReservaEnum;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reservations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_create_view_update_and_delete_a_reservation(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = $this->availableTable();
        $inicio = Carbon::now()->addDays(3)->startOfHour();
        $fin = $inicio->copy()->addHour();
        $payload = $this->reservationPayload($cliente, $mesa, $inicio, $fin);

        $this->actingAs($operador)
            ->get(route('reservations.create'))
            ->assertOk()
            ->assertSee($cliente->nombres)
            ->assertSee('Nueva reserva');

        $response = $this
            ->post(route('reservations.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $reserva = Reserva::query()->sole();
        $response->assertRedirect(route('reservations.show', $reserva));
        $this->assertSame($operador->id, $reserva->usuario_id);
        $this->assertSame($cliente->id, $reserva->cliente_id);
        $this->assertSame(EstadoReservaEnum::CONFIRMADA, $reserva->estado);

        $this->get(route('reservations.show', $reserva))
            ->assertOk()
            ->assertSee($cliente->nombres)
            ->assertSee('Detalle de reserva');
        $this->get(route('reservations.edit', $reserva))
            ->assertOk()
            ->assertSee('Guardar cambios');

        $nuevaInicio = $inicio->copy()->addHours(3);
        $nuevaMesa = $this->availableTable();
        $this->put(route('reservations.update', $reserva), [
            ...$this->reservationPayload(
                $cliente,
                $nuevaMesa,
                $nuevaInicio,
                $nuevaInicio->copy()->addHour(),
            ),
            'estado' => EstadoReservaEnum::ACTIVA->value,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reservations.show', $reserva));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'mesa_id' => $nuevaMesa->id,
            'estado' => EstadoReservaEnum::ACTIVA->value,
        ]);

        $this->delete(route('reservations.destroy', $reserva))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reservations.index'));

        $this->assertDatabaseMissing('reservas', ['id' => $reserva->id]);
    }

    public function test_customer_can_only_see_and_create_own_reservations(): void
    {
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $propia = $this->reservationFor($cliente, $this->availableTable());
        $ajena = $this->reservationFor($otroCliente, $this->availableTable());

        $this->actingAs($cliente->usuario)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertSee($propia->cliente->nombres)
            ->assertDontSee($ajena->cliente->nombres)
            ->assertSee(route('reservations.index'))
            ->assertSee('Nueva reserva');

        $this->get(route('reservations.show', $ajena))
            ->assertForbidden();

        $inicio = Carbon::now()->addDays(10)->startOfHour();
        $this->post(route('reservations.store'), [
            'cliente_id' => $otroCliente->id,
            'mesa_id' => $this->availableTable()->id,
            'fecha_inicio' => $inicio->format('Y-m-d\TH:i'),
            'fecha_fin' => $inicio->copy()->addHour()->format('Y-m-d\TH:i'),
            'numero_personas' => 2,
        ])->assertSessionHasErrors('cliente_id');

        $this->post(route('reservations.store'), [
            'mesa_id' => $this->availableTable()->id,
            'fecha_inicio' => $inicio->format('Y-m-d\TH:i'),
            'fecha_fin' => $inicio->copy()->addHour()->format('Y-m-d\TH:i'),
            'numero_personas' => 2,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('reservas', [
            'usuario_id' => $cliente->usuario_id,
            'cliente_id' => $cliente->id,
        ]);

        $this->put(route('reservations.update', $propia), [
            'cliente_id' => $otroCliente->id,
        ])->assertForbidden();

        $this->delete(route('reservations.destroy', $propia))
            ->assertForbidden();
    }

    public function test_customer_can_cancel_own_reservation_but_not_delete_it(): void
    {
        $cliente = Cliente::factory()->create();
        $reserva = $this->reservationFor($cliente, $this->availableTable());

        $this->actingAs($cliente->usuario)
            ->patch(route('reservations.cancel', $reserva))
            ->assertRedirect(route('reservations.show', $reserva));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => EstadoReservaEnum::CANCELADA->value,
        ]);
    }

    public function test_reservation_rejects_schedule_conflicts(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = $this->availableTable(['capacidad' => 2]);
        $inicio = Carbon::now()->addDays(5)->startOfHour();
        $fin = $inicio->copy()->addHour();
        $this->reservationFor($cliente, $mesa, $inicio, $fin);

        $this->actingAs($administrador)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->reservationPayload(
                $cliente,
                $mesa,
                $inicio->copy()->addMinutes(30),
                $fin->copy()->addMinutes(30),
            ))
            ->assertRedirect(route('reservations.create'))
            ->assertSessionHasErrors('mesa_id');
    }

    public function test_reservation_rejects_party_size_above_table_capacity(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = $this->availableTable(['capacidad' => 2]);
        $inicio = Carbon::now()->addDays(6)->startOfHour();

        $this->actingAs($administrador)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->reservationPayload(
                $cliente,
                $mesa,
                $inicio,
                $inicio->copy()->addHour(),
                3,
            ))
            ->assertRedirect(route('reservations.create'))
            ->assertSessionHasErrors('numero_personas');
    }

    public function test_search_status_and_date_filters_are_applied(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $cliente = Cliente::factory()->create(['nombres' => 'Marina']);
        $otherClient = Cliente::factory()->create(['nombres' => 'Samuel']);
        $inicio = Carbon::now()->addDays(4)->startOfHour();
        $target = $this->reservationFor($cliente, $this->availableTable(), $inicio);
        $this->reservationFor(
            $otherClient,
            $this->availableTable(),
            $inicio->copy()->addDays(1),
            null,
            EstadoReservaEnum::CANCELADA,
        );

        $this->actingAs($operador)
            ->get(route('reservations.index', [
                'search' => 'Marina',
                'estado' => EstadoReservaEnum::CONFIRMADA->value,
                'fecha_desde' => $inicio->toDateString(),
                'fecha_hasta' => $inicio->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Marina')
            ->assertDontSee('Samuel')
            ->assertViewHas(
                'reservations',
                fn ($reservations): bool => $reservations->total() === 1
                    && $reservations->contains('id', $target->id),
            );
    }

    public function test_reservation_index_is_paginated(): void
    {
        $operador = Usuario::factory()->operador()->create();
        Reserva::factory()->count(16)->create();

        $this->actingAs($operador)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertViewHas(
                'reservations',
                fn ($reservations): bool => $reservations->total() === 16
                    && $reservations->perPage() === 15
                    && $reservations->count() === 15,
            );
    }

    public function test_reservation_with_orders_cannot_be_deleted(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = $this->availableTable();
        $reserva = $this->reservationFor($cliente, $mesa);
        Pedido::factory()->create([
            'usuario_id' => $administrador->id,
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa->id,
            'reserva_id' => $reserva->id,
        ]);

        $this->actingAs($administrador)
            ->delete(route('reservations.destroy', $reserva))
            ->assertSessionHasErrors('reserva');

        $this->assertDatabaseHas('reservas', ['id' => $reserva->id]);
    }

    public function test_disabled_table_cannot_be_reserved(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create();
        $mesa = $this->availableTable([
            'estado_operativo' => EstadoOperativoMesaEnum::MANTENIMIENTO,
        ]);
        $inicio = Carbon::now()->addDays(8)->startOfHour();

        $this->actingAs($administrador)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->reservationPayload(
                $cliente,
                $mesa,
                $inicio,
                $inicio->copy()->addHour(),
            ))
            ->assertRedirect(route('reservations.create'))
            ->assertSessionHasErrors('mesa_id');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function availableTable(array $attributes = []): Mesa
    {
        return Mesa::factory()->create([
            'estado_operativo' => EstadoOperativoMesaEnum::HABILITADA,
            ...$attributes,
        ]);
    }

    private function reservationFor(
        Cliente $cliente,
        Mesa $mesa,
        ?Carbon $fechaInicio = null,
        ?Carbon $fechaFin = null,
        EstadoReservaEnum $estado = EstadoReservaEnum::CONFIRMADA,
    ): Reserva {
        $fechaInicio ??= Carbon::now()->addDays(2)->startOfHour();

        return Reserva::factory()->create([
            'usuario_id' => $cliente->usuario_id,
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa->id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin ?? $fechaInicio->copy()->addHour(),
            'estado' => $estado,
        ]);
    }

    /**
     * @return array{
     *     cliente_id: int,
     *     mesa_id: int,
     *     fecha_inicio: string,
     *     fecha_fin: string,
     *     numero_personas: int
     * }
     */
    private function reservationPayload(
        Cliente $cliente,
        Mesa $mesa,
        Carbon $fechaInicio,
        Carbon $fechaFin,
        int $numeroPersonas = 2,
    ): array {
        return [
            'cliente_id' => $cliente->id,
            'mesa_id' => $mesa->id,
            'fecha_inicio' => $fechaInicio->format('Y-m-d\TH:i'),
            'fecha_fin' => $fechaFin->format('Y-m-d\TH:i'),
            'numero_personas' => $numeroPersonas,
        ];
    }
}
