<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Models\Incidencia;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incidencia>
 */
class IncidenciaFactory extends Factory
{
    protected $model = Incidencia::class;

    public function definition(): array
    {
        return [
            'usuario_reporta_id' => Usuario::factory(),
            'mesa_id' => fake()->boolean(70) ? Mesa::factory() : null,
            'pedido_id' => fake()->boolean(50) ? Pedido::factory() : null,
            'titulo' => fake()->sentence(4),
            'descripcion' => fake()->paragraph(),
            'prioridad' => fake()->randomElement(PrioridadIncidenciaEnum::cases()),
            'estado' => EstadoIncidenciaEnum::ABIERTA,
        ];
    }
}
