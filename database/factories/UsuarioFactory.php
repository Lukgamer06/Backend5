<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RolUsuarioEnum;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'rol' => RolUsuarioEnum::CLIENTE,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn (): array => ['rol' => RolUsuarioEnum::ADMINISTRADOR]);
    }

    public function operador(): static
    {
        return $this->state(fn (): array => ['rol' => RolUsuarioEnum::OPERADOR]);
    }

    public function cliente(): static
    {
        return $this->state(fn (): array => ['rol' => RolUsuarioEnum::CLIENTE]);
    }
}
