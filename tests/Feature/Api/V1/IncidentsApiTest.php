<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Models\Incidencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_filter_and_paginate_incidents(): void
    {
        $operador = Usuario::factory()->operador()->create();
        Incidencia::factory()->count(2)->create([
            'titulo' => 'Falla eléctrica',
            'prioridad' => PrioridadIncidenciaEnum::ALTA,
            'estado' => EstadoIncidenciaEnum::ABIERTA,
        ]);
        Incidencia::factory()->create([
            'titulo' => 'Daño en habitación',
            'prioridad' => PrioridadIncidenciaEnum::BAJA,
        ]);

        $this->actingAs($operador, 'sanctum')
            ->getJson('/api/v1/incidencias?search=eléctrica&prioridad=ALTA&estado=ABIERTA&per_page=1')
            ->assertOk()
            ->assertJsonPath('message', 'Incidencias consultadas correctamente.')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Falla eléctrica')
            ->assertJsonStructure(['data', 'links', 'meta', 'message']);
    }

    public function test_staff_can_report_an_incident_with_server_owned_reporter_and_default_values(): void
    {
        $operador = Usuario::factory()->operador()->create();

        $this->actingAs($operador, 'sanctum')
            ->postJson('/api/v1/incidencias', [
                'titulo' => 'Falla eléctrica',
                'descripcion' => 'La iluminación del pasillo dejó de funcionar.',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Incidencia reportada correctamente.')
            ->assertJsonPath('data.usuario_reporta_id', $operador->id)
            ->assertJsonPath('data.prioridad', PrioridadIncidenciaEnum::MEDIA->value)
            ->assertJsonPath('data.estado', EstadoIncidenciaEnum::ABIERTA->value)
            ->assertJsonStructure(['data' => ['id', 'usuario', 'mesa_id', 'pedido_id'], 'message', 'meta']);

        $this->assertDatabaseHas('incidencias', [
            'usuario_reporta_id' => $operador->id,
            'prioridad' => PrioridadIncidenciaEnum::MEDIA->value,
            'estado' => EstadoIncidenciaEnum::ABIERTA->value,
        ]);
    }

    public function test_incident_creation_rejects_client_controlled_reporter_and_status(): void
    {
        $operador = Usuario::factory()->operador()->create();

        $this->actingAs($operador, 'sanctum')
            ->postJson('/api/v1/incidencias', [
                'titulo' => 'Falla eléctrica',
                'descripcion' => 'La iluminación del pasillo dejó de funcionar.',
                'usuario_reporta_id' => $operador->id,
                'estado' => EstadoIncidenciaEnum::RESUELTA->value,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.usuario_reporta_id.0', 'The usuario reporta id field is prohibited.')
            ->assertJsonPath('meta.errors.estado.0', 'The estado field is prohibited.');

        $this->assertDatabaseCount('incidencias', 0);
    }

    public function test_staff_can_patch_one_incident_field_without_changing_the_other(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $incidencia = Incidencia::factory()->create([
            'estado' => EstadoIncidenciaEnum::ABIERTA,
            'prioridad' => PrioridadIncidenciaEnum::BAJA,
        ]);

        $this->actingAs($operador, 'sanctum')
            ->patchJson("/api/v1/incidencias/{$incidencia->id}", [
                'prioridad' => PrioridadIncidenciaEnum::CRITICA->value,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Incidencia actualizada correctamente.')
            ->assertJsonPath('data.estado', EstadoIncidenciaEnum::ABIERTA->value)
            ->assertJsonPath('data.prioridad', PrioridadIncidenciaEnum::CRITICA->value);

        $this->assertDatabaseHas('incidencias', [
            'id' => $incidencia->id,
            'estado' => EstadoIncidenciaEnum::ABIERTA->value,
            'prioridad' => PrioridadIncidenciaEnum::CRITICA->value,
        ]);
    }

    public function test_incident_routes_require_staff_authorization_and_existing_records(): void
    {
        $this->getJson('/api/v1/incidencias')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');

        $cliente = Usuario::factory()->cliente()->create();
        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/v1/incidencias')
            ->assertForbidden();

        $operador = Usuario::factory()->operador()->create();
        $this->actingAs($operador, 'sanctum')
            ->patchJson('/api/v1/incidencias/999999', ['estado' => EstadoIncidenciaEnum::RESUELTA->value])
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }
}
