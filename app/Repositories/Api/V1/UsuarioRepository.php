<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1;

use App\Models\Usuario;

class UsuarioRepository
{
    public function findByCorreo(string $correo): ?Usuario
    {
        return Usuario::query()->where('correo', $correo)->first();
    }
}
