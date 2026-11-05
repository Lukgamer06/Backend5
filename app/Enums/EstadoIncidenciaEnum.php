<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoIncidenciaEnum: string
{
    case ABIERTA = 'ABIERTA';
    case EN_ATENCION = 'EN_ATENCION';
    case RESUELTA = 'RESUELTA';
}
