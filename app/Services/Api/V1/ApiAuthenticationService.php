<?php

declare(strict_types=1);

namespace App\Services\Api\V1;

use App\DTOs\Api\V1\AuthenticatedApiSession;
use App\Models\Usuario;
use App\Repositories\Api\V1\UsuarioRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class ApiAuthenticationService
{
    public function __construct(
        private readonly UsuarioRepository $usuarioRepository,
    ) {}

    public function authenticate(string $correo, string $password): AuthenticatedApiSession
    {
        $usuario = $this->usuarioRepository->findByCorreo($correo);

        if (
            $usuario === null
            || $usuario->password === null
            || ! Hash::check($password, $usuario->password)
        ) {
            throw new AuthenticationException;
        }

        $token = $usuario->createToken('api-v1');

        return new AuthenticatedApiSession($usuario, $token->plainTextToken);
    }

    public function currentSession(Usuario $usuario): AuthenticatedApiSession
    {
        return new AuthenticatedApiSession($usuario);
    }

    public function revokeCurrentToken(Usuario $usuario): void
    {
        $token = $usuario->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            throw new AuthenticationException;
        }

        $token->delete();
    }
}
