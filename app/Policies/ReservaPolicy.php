<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Reserva;
use App\Models\Usuario;
use App\Policies\Concerns\AuthorizesHostalRoles;

class ReservaPolicy
{
    use AuthorizesHostalRoles;

    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Reserva $reserva): bool
    {
        return $this->isStaff($usuario)
            || $this->ownsCustomerRecord($usuario, $reserva->cliente_id);
    }

    public function create(Usuario $usuario): bool
    {
        return $this->isStaff($usuario) || $usuario->cliente()->exists();
    }

    public function update(Usuario $usuario, Reserva $reserva): bool
    {
        return $this->isStaff($usuario);
    }

    public function cancel(Usuario $usuario, Reserva $reserva): bool
    {
        return $this->isStaff($usuario)
            || $this->ownsCustomerRecord($usuario, $reserva->cliente_id);
    }

    public function delete(Usuario $usuario, Reserva $reserva): bool
    {
        return $this->isStaff($usuario);
    }
}
