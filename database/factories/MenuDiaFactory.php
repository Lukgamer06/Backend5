<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoMenuDiaEnum;
use App\Models\MenuDia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<MenuDia>
 */
class MenuDiaFactory extends Factory
{
    protected $model = MenuDia::class;

    public function definition(): array
    {
        $diasDesdeHoy = fake()->unique()->numberBetween(0, 3650);

        return [
            'fecha' => Carbon::today()->addDays($diasDesdeHoy)->toDateString(),
            'estado' => EstadoMenuDiaEnum::BORRADOR,
        ];
    }

    public function publicado(): static
    {
        return $this->state(fn (): array => ['estado' => EstadoMenuDiaEnum::PUBLICADO]);
    }
}
