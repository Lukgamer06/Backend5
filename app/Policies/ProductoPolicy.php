<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Producto;
use App\Models\Usuario;
use App\Policies\Concerns\AuthorizesHostalRoles;

class ProductoPolicy
{
    use AuthorizesHostalRoles;

    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Producto $producto): bool
    {
        return $this->isAdministrator($usuario) || $producto->disponible;
    }

    public function create(Usuario $usuario): bool
    {
        return $this->isAdministrator($usuario);
    }

    public function update(Usuario $usuario, Producto $producto): bool
    {
        return $this->isAdministrator($usuario);
    }

    public function activate(Usuario $usuario, Producto $producto): bool
    {
        return $this->isAdministrator($usuario);
    }

    public function deactivate(Usuario $usuario, Producto $producto): bool
    {
        return $this->isAdministrator($usuario);
    }

    public function delete(Usuario $usuario, Producto $producto): bool
    {
        return $this->isAdministrator($usuario);
    }
}
