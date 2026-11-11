<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuarioEnum;
use App\Models\Usuario;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_only_the_three_demo_accounts_with_expected_roles(): void
    {
        config(['demo_accounts.password' => 'test-only-demo-password']);

        $this->seed(DemoAccountsSeeder::class);

        $this->assertDatabaseCount('usuarios', 3);
        $this->assertDatabaseHas('usuarios', [
            'correo' => 'example@unab.edu.co',
            'rol' => RolUsuarioEnum::CLIENTE->value,
        ]);
        $this->assertDatabaseHas('usuarios', [
            'correo' => 'operador@unab.edu.co',
            'rol' => RolUsuarioEnum::OPERADOR->value,
        ]);
        $this->assertDatabaseHas('usuarios', [
            'correo' => 'admin@unab.edu.co',
            'rol' => RolUsuarioEnum::ADMINISTRADOR->value,
        ]);

        $cliente = Usuario::query()->where('correo', 'example@unab.edu.co')->firstOrFail();
        $this->assertTrue(Hash::check('test-only-demo-password', $cliente->password));
        $this->assertDatabaseHas('clientes', [
            'usuario_id' => $cliente->id,
            'correo' => 'example@unab.edu.co',
        ]);
        $this->assertDatabaseCount('clientes', 1);
    }
}
