<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RolUsuarioEnum;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('demo_accounts.password');
        if (! is_string($password) || $password === '') {
            throw new RuntimeException('DEMO_ACCOUNT_PASSWORD must be set to create demo accounts.');
        }

        $accounts = [
            [
                'correo' => 'example@unab.edu.co',
                'nombres' => 'Example',
                'apellidos' => 'HostalConnect',
                'rol' => RolUsuarioEnum::CLIENTE,
            ],
            [
                'correo' => 'operador@unab.edu.co',
                'nombres' => 'Operador',
                'apellidos' => 'HostalConnect',
                'rol' => RolUsuarioEnum::OPERADOR,
            ],
            [
                'correo' => 'admin@unab.edu.co',
                'nombres' => 'Administrador',
                'apellidos' => 'HostalConnect',
                'rol' => RolUsuarioEnum::ADMINISTRADOR,
            ],
        ];

        foreach ($accounts as $account) {
            $user = Usuario::query()->updateOrCreate(
                ['correo' => $account['correo']],
                [
                    'nombres' => $account['nombres'],
                    'apellidos' => $account['apellidos'],
                    'rol' => $account['rol'],
                    'password' => $password,
                ],
            );

            if ($account['rol'] === RolUsuarioEnum::CLIENTE) {
                Cliente::query()->updateOrCreate(
                    ['usuario_id' => $user->id],
                    [
                        'nombres' => $account['nombres'],
                        'apellidos' => $account['apellidos'],
                        'correo' => $account['correo'],
                    ],
                );
            }
        }
    }
}
