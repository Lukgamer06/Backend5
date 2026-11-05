<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(3, true),
            'descripcion' => fake()->sentence(),
            'precio' => fake()->randomFloat(2, 1, 250000),
            'disponible' => true,
        ];
    }

    public function noDisponible(): static
    {
        return $this->state(fn (): array => ['disponible' => false]);
    }
}
