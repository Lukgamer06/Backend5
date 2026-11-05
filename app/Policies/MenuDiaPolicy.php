<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EstadoMenuDiaEnum;
use App\Models\MenuDia;
use App\Models\Usuario;
use App\Policies\Concerns\AuthorizesHostalRoles;

class MenuDiaPolicy
{
    use AuthorizesHostalRoles;

    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, MenuDia $menuDia): bool
    {
        return $this->isStaff($usuario)
            || $menuDia->estado === EstadoMenuDiaEnum::PUBLICADO;
    }

    public function create(Usuario $usuario): bool
    {
        return $this->isStaff($usuario);
    }

    public function update(Usuario $usuario, MenuDia $menuDia): bool
    {
        return $this->isStaff($usuario);
    }

    public function publish(Usuario $usuario, MenuDia $menuDia): bool
    {
        return $this->isStaff($usuario);
    }

    public function delete(Usuario $usuario, MenuDia $menuDia): bool
    {
        return $this->isAdministrator($usuario);
    }
}
