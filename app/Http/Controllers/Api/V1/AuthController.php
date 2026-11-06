<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Api\V1\AuthenticatedApiSession;
use App\Http\Requests\Api\V1\Auth\LoginApiRequest;
use App\Http\Resources\Api\V1\AuthSessionResource;
use App\Models\Usuario;
use App\Services\Api\V1\ApiAuthenticationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class AuthController
{
    public function __construct(
        private readonly ApiAuthenticationService $authenticationService,
    ) {}

    public function login(LoginApiRequest $request): AuthSessionResource
    {
        $credentials = $request->validated();
        $session = $this->authenticationService->authenticate(
            $credentials['correo'],
            $credentials['password'],
        );

        return AuthSessionResource::make($session)->additional([
            'message' => 'Autenticación exitosa.',
            'meta' => (object) [],
        ]);
    }

    public function logout(Request $request): AuthSessionResource
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new AuthenticationException;
        }

        $this->authenticationService->revokeCurrentToken($usuario);

        return AuthSessionResource::make(new AuthenticatedApiSession(null))->additional([
            'message' => 'Sesión cerrada correctamente.',
            'meta' => (object) [],
        ]);
    }

    public function me(Request $request): AuthSessionResource
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new AuthenticationException;
        }

        return AuthSessionResource::make(
            $this->authenticationService->currentSession($usuario),
        )->additional([
            'message' => 'Usuario autenticado.',
            'meta' => (object) [],
        ]);
    }
}
