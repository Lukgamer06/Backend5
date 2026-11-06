<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Models\Incidencia;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('incidents.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_report_an_incident_with_optional_references(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $mesa = Mesa::factory()->create();
        $pedido = Pedido::factory()->create();

        $this->actingAs($operador)
            ->get(route('incidents.create'))
            ->assertOk()
            ->assertSee('Reportar incidencia')
            ->assertSee('Mesa '.$mesa->numero)
            ->assertSee('Pedido #'.$pedido->id);

        $this->post(route('incidents.store'), [
            'titulo' => 'Fuga en el baño',
            'descripcion' => 'Se observa una fuga de agua en el baño del pasillo.',
            'prioridad' => PrioridadIncidenciaEnum::ALTA->value,
            'mesa_id' => $mesa->id,
            'pedido_id' => $pedido->id,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $incidencia = Incidencia::query()->sole();
        $this->assertSame($operador->id, $incidencia->usuario_reporta_id);
        $this->assertSame($mesa->id, $incidencia->mesa_id);
        $this->assertSame($pedido->id, $incidencia->pedido_id);
        $this->assertSame(PrioridadIncidenciaEnum::ALTA, $incidencia->prioridad);
        $this->assertSame(EstadoIncidenciaEnum::ABIERTA, $incidencia->estado);

        $this->get(route('incidents.show', $incidencia))
            ->assertOk()
            ->assertSee('Fuga en el baño')
            ->assertSee('Se observa una fuga de agua')
            ->assertSee('Pedido #'.$pedido->id);
    }

    public function test_staff_can_update_incident_status_and_priority(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $incidencia = Incidencia::factory()->create();

        $this->actingAs($administrador)
            ->get(route('incidents.edit', $incidencia))
            ->assertOk()
            ->assertSee($incidencia->titulo);

        $this->put(route('incidents.update', $incidencia), [
            'estado' => EstadoIncidenciaEnum::EN_ATENCION->value,
            'prioridad' => PrioridadIncidenciaEnum::CRITICA->value,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('incidents.show', $incidencia));

        $this->assertDatabaseHas('incidencias', [
            'id' => $incidencia->id,
            'estado' => EstadoIncidenciaEnum::EN_ATENCION->value,
            'prioridad' => PrioridadIncidenciaEnum::CRITICA->value,
        ]);
    }

    public function test_incident_filters_and_pagination_are_applied(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $target = Incidencia::factory()->create([
            'titulo' => 'Cerradura dañada',
            'prioridad' => PrioridadIncidenciaEnum::ALTA,
            'estado' => EstadoIncidenciaEnum::ABIERTA,
        ]);
        Incidencia::factory()->create([
            'titulo' => 'Ruido en pasillo',
            'prioridad' => PrioridadIncidenciaEnum::BAJA,
            'estado' => EstadoIncidenciaEnum::RESUELTA,
        ]);

        $this->actingAs($operador)
            ->get(route('incidents.index', [
                'search' => 'Cerradura',
                'prioridad' => PrioridadIncidenciaEnum::ALTA->value,
                'estado' => EstadoIncidenciaEnum::ABIERTA->value,
            ]))
            ->assertOk()
            ->assertSee($target->titulo)
            ->assertViewHas(
                'incidents',
                fn ($incidents): bool => $incidents->total() === 1
                    && $incidents->contains('id', $target->id),
            );

        Incidencia::factory()->count(15)->create();
        $this->get(route('incidents.index'))
            ->assertViewHas(
                'incidents',
                fn ($incidents): bool => $incidents->total() === 17
                    && $incidents->perPage() === 15
                    && $incidents->count() === 15,
            );
    }

    public function test_customers_cannot_report_or_view_incidents(): void
    {
        $cliente = Usuario::factory()->create();

        $this->actingAs($cliente)
            ->get(route('incidents.index'))
            ->assertForbidden();
        $this->get(route('incidents.create'))->assertForbidden();
        $this->post(route('incidents.store'), [
            'titulo' => 'Reporte no permitido',
            'descripcion' => 'Un cliente no tiene acceso a este módulo.',
            'prioridad' => PrioridadIncidenciaEnum::MEDIA->value,
        ])->assertForbidden();
    }

    public function test_invalid_incident_data_and_protected_fields_are_rejected(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $otherUser = Usuario::factory()->create();

        $this->actingAs($operador)
            ->from(route('incidents.create'))
            ->post(route('incidents.store'), [
                'titulo' => '',
                'descripcion' => '',
                'prioridad' => 'URGENTE',
                'mesa_id' => 99999,
                'estado' => EstadoIncidenciaEnum::RESUELTA->value,
                'usuario_reporta_id' => $otherUser->id,
            ])
            ->assertRedirect(route('incidents.create'))
            ->assertSessionHasErrors([
                'titulo',
                'descripcion',
                'prioridad',
                'mesa_id',
                'estado',
                'usuario_reporta_id',
            ]);

        $this->assertDatabaseCount('incidencias', 0);
    }
}
