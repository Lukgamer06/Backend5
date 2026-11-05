<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoAtencionEnum: string
{
    case INMEDIATO = 'INMEDIATO';
    case PROGRAMADO = 'PROGRAMADO';
}
