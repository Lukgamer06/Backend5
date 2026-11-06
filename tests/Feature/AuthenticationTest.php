<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_is_redirected_to_the_dashboard(): void
    {
        $usuario = Usuario::factory()->create([
            'correo' => 'usuario@example.test',
            'password' => 'correct-password',
        ]);

        $this->get(route('login'))->assertOk();

        $this->post(route('login.store'), [
            'correo' => 'usuario@example.test',
            'password' => 'correct-password',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($usuario);
        $this->get(route('home'))->assertOk();
    }

    public function test_invalid_credentials_are_rejected_without_authenticating(): void
    {
        Usuario::factory()->create([
            'correo' => 'usuario@example.test',
            'password' => 'correct-password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'correo' => 'usuario@example.test',
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('correo');

        $this->assertGuest();
    }

    public function test_remember_me_creates_a_persistent_login_cookie(): void
    {
        Usuario::factory()->create([
            'correo' => 'usuario@example.test',
            'password' => 'correct-password',
        ]);

        $response = $this->post(route('login.store'), [
            'correo' => 'usuario@example.test',
            'password' => 'correct-password',
            'remember' => '1',
        ])->assertRedirect(route('home'));

        $rememberCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie): bool => str_starts_with($cookie->getName(), 'remember_web_'));

        $this->assertNotNull($rememberCookie);
        $this->assertNotSame(0, $rememberCookie->getExpiresTime());
        $this->assertAuthenticated();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $usuario = Usuario::factory()->create([
            'password' => 'correct-password',
        ]);

        $this->actingAs($usuario)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
