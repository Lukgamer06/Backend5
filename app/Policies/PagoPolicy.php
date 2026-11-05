<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pago;
use App\Models\Usuario;
use App\Policies\Concerns\AuthorizesHostalRoles;

class PagoPolicy
{
    use AuthorizesHostalRoles;

    public function viewAny(Usuario $usuario): bool
    {
        return $this->isAdministrator($usuario);
    }

    public function view(Usuario $usuario, Pago $pago): bool
    {
        return $this->isAdministrator($usuario);
    }
}
