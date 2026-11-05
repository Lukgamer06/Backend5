<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoReservaEnum;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Reserva>
 */
class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    public function definition(): array
    {
        $capacidad = fake()->numberBetween(2, 10);
        $fechaInicio = Carbon::instance(fake()->dateTimeBetween('+1 day', '+30 days'));

        return [
            'usuario_id' => Usuario::factory(),
            'cliente_id' => Cliente::factory(),
            'mesa_id' => Mesa::factory()->state(['capacidad' => $capacidad]),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaInicio->copy()->addHour(),
            'numero_personas' => fake()->numberBetween(1, $capacidad),
            'estado' => EstadoReservaEnum::CONFIRMADA,
            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    public function cancelada(): static
    {
        return $this->state(fn (): array => ['estado' => EstadoReservaEnum::CANCELADA]);
    }
}
