<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoMesaEnum;
use App\Enums\EstadoOperativoMesaEnum;
use App\Models\Mesa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mesa>
 */
class MesaFactory extends Factory
{
    protected $model = Mesa::class;

    public function definition(): array
    {
        return [
            'numero' => fake()->unique()->numberBetween(1, 9999),
            'capacidad' => fake()->numberBetween(2, 10),
            'estado' => EstadoMesaEnum::LIBRE,
            'estado_operativo' => EstadoOperativoMesaEnum::HABILITADA,
        ];
    }

    public function mantenimiento(): static
    {
        return $this->state(
            fn (): array => ['estado_operativo' => EstadoOperativoMesaEnum::MANTENIMIENTO]
        );
    }
}
