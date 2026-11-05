<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RolUsuarioEnum;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        $nombres = fake()->firstName();
        $apellidos = fake()->lastName();
        $correo = fake()->unique()->safeEmail();
        $telefono = fake()->phoneNumber();

        return [
            'usuario_id' => Usuario::factory()->state([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'correo' => $correo,
                'telefono' => $telefono,
                'rol' => RolUsuarioEnum::CLIENTE,
            ]),
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'correo' => $correo,
            'telefono' => $telefono,
        ];
    }

    public function sinUsuario(): static
    {
        return $this->state(fn (): array => ['usuario_id' => null]);
    }
}
