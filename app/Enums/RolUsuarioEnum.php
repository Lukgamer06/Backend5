<?php

declare(strict_types=1);

namespace App\Enums;

enum RolUsuarioEnum: string
{
    case ADMINISTRADOR = 'ADMINISTRADOR';
    case OPERADOR = 'OPERADOR';
    case CLIENTE = 'CLIENTE';
}
