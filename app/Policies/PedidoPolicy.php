<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pedido;
use App\Models\Usuario;
use App\Policies\Concerns\AuthorizesHostalRoles;

class PedidoPolicy
{
    use AuthorizesHostalRoles;

    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Pedido $pedido): bool
    {
        return $this->isStaff($usuario)
            || $this->ownsCustomerRecord($usuario, $pedido->cliente_id);
    }

    public function create(Usuario $usuario): bool
    {
        return $this->isStaff($usuario) || $usuario->cliente()->exists();
    }

    public function update(Usuario $usuario, Pedido $pedido): bool
    {
        return $this->isStaff($usuario);
    }

    public function cancel(Usuario $usuario, Pedido $pedido): bool
    {
        return $this->isStaff($usuario)
            || $this->ownsCustomerRecord($usuario, $pedido->cliente_id);
    }

    public function delete(Usuario $usuario, Pedido $pedido): bool
    {
        return $this->isAdministrator($usuario);
    }
}
