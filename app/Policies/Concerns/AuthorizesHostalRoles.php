<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\RolUsuarioEnum;
use App\Models\Usuario;

trait AuthorizesHostalRoles
{
    protected function hasRole(Usuario $usuario, RolUsuarioEnum ...$roles): bool
    {
        return in_array($usuario->rol, $roles, true);
    }

    protected function isAdministrator(Usuario $usuario): bool
    {
        return $this->hasRole($usuario, RolUsuarioEnum::ADMINISTRADOR);
    }

    protected function isStaff(Usuario $usuario): bool
    {
        return $this->hasRole(
            $usuario,
            RolUsuarioEnum::ADMINISTRADOR,
            RolUsuarioEnum::OPERADOR,
        );
    }

    protected function ownsCustomerRecord(Usuario $usuario, int $clienteId): bool
    {
        return $usuario->cliente()->whereKey($clienteId)->exists();
    }
}
