<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Incidencia;
use App\Models\Usuario;
use App\Policies\Concerns\AuthorizesHostalRoles;

class IncidenciaPolicy
{
    use AuthorizesHostalRoles;

    public function viewAny(Usuario $usuario): bool
    {
        return $this->isStaff($usuario);
    }

    public function view(Usuario $usuario, Incidencia $incidencia): bool
    {
        return $this->isStaff($usuario);
    }

    public function create(Usuario $usuario): bool
    {
        return $this->isStaff($usuario);
    }

    public function update(Usuario $usuario, Incidencia $incidencia): bool
    {
        return $this->isStaff($usuario);
    }

    public function delete(Usuario $usuario, Incidencia $incidencia): bool
    {
        return $this->isAdministrator($usuario);
    }
}
