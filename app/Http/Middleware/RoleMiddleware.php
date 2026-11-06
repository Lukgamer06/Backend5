<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RolUsuarioEnum;
use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $rolesPermitidos = array_map(
            static fn (string $rol): ?RolUsuarioEnum => RolUsuarioEnum::tryFrom($rol),
            $roles,
        );

        abort_unless(
            $usuario instanceof Usuario
                && in_array($usuario->rol, $rolesPermitidos, true),
            403,
            'No tienes permiso para acceder a este recurso.',
        );

        return $next($request);
    }
}
