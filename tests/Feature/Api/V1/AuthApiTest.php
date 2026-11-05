<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_receive_a_personal_access_token(): void
    {
        Usuario::factory()->create([
            'correo' => 'usuario@example.test',
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'correo' => 'usuario@example.test',
            'password' => 'correct-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Autenticación exitosa.')
            ->assertJsonPath('data.usuario.correo', 'usuario@example.test')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('meta', [])
            ->assertJsonStructure([
                'data' => ['usuario' => ['id', 'nombres', 'apellidos', 'correo', 'rol'], 'access_token'],
                'message',
                'meta',
            ]);

        $plainTextToken = $response->json('data.access_token');

        $this->assertIsString($plainTextToken);
        [$tokenId, $tokenSecret] = explode('|', $plainTextToken, 2);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'token' => hash('sha256', $tokenSecret),
            'name' => 'api-v1',
        ]);
    }

    public function test_login_rejects_invalid_credentials_with_a_standardized_401(): void
    {
        Usuario::factory()->create([
            'correo' => 'usuario@example.test',
            'password' => 'correct-password',
        ]);

        $this->postJson('/api/v1/login', [
            'correo' => 'usuario@example.test',
            'password' => 'incorrect-password',
        ])
            ->assertUnauthorized()
            ->assertExactJson([
                'data' => null,
                'message' => 'No autenticado.',
                'meta' => [],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_validation_uses_the_standardized_422_response(): void
    {
        $this->postJson('/api/v1/login', [
            'correo' => 'not-an-email',
            'password' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Los datos enviados no son válidos.')
            ->assertJsonPath('meta.errors.correo.0', 'El correo debe ser una dirección válida.')
            ->assertJsonPath('meta.errors.password.0', 'La contraseña es obligatoria.');
    }

    public function test_authenticated_user_can_read_their_public_profile(): void
    {
        $usuario = Usuario::factory()->create([
            'password' => 'correct-password',
        ]);
        $token = $usuario->createToken('api-v1')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('message', 'Usuario autenticado.')
            ->assertJsonPath('data.usuario.id', $usuario->id)
            ->assertJsonPath('data.usuario.correo', $usuario->correo)
            ->assertJsonMissingPath('data.access_token')
            ->assertJsonMissingPath('data.usuario.password')
            ->assertJsonStructure([
                'data' => ['usuario' => ['id', 'nombres', 'apellidos', 'correo', 'rol']],
                'message',
                'meta',
            ]);
    }

    public function test_private_authentication_routes_return_a_standardized_401_without_a_token(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertExactJson([
                'data' => null,
                'message' => 'No autenticado.',
                'meta' => [],
            ]);

        $this->postJson('/api/v1/logout')
            ->assertUnauthorized()
            ->assertExactJson([
                'data' => null,
                'message' => 'No autenticado.',
                'meta' => [],
            ]);
    }

    public function test_unknown_api_routes_return_a_standardized_404(): void
    {
        $this->getJson('/api/v1/no-existe')
            ->assertNotFound()
            ->assertExactJson([
                'data' => null,
                'message' => 'Recurso no encontrado.',
                'meta' => [],
            ]);
    }

    public function test_login_is_rate_limited_with_a_standardized_response(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/v1/login', [
                'correo' => 'usuario@example.test',
                'password' => 'incorrect-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/login', [
            'correo' => 'usuario@example.test',
            'password' => 'incorrect-password',
        ])
            ->assertStatus(429)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Demasiadas solicitudes. Inténtalo nuevamente más tarde.')
            ->assertJsonStructure(['data', 'message', 'meta' => ['retry_after']]);
    }

    public function test_logout_revokes_only_the_current_personal_access_token(): void
    {
        $usuario = Usuario::factory()->create();
        $currentToken = $usuario->createToken('api-v1');
        $otherToken = $usuario->createToken('other-device');

        $this->withToken($currentToken->plainTextToken)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('data.logged_out', true)
            ->assertJsonPath('message', 'Sesión cerrada correctamente.')
            ->assertJsonStructure(['data', 'message', 'meta']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $currentToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->id,
        ]);

        $this->app['auth']->forgetGuards();

        $this->withToken($currentToken->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }
}
