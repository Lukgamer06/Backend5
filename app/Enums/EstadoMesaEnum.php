<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoMesaEnum: string
{
    case LIBRE = 'LIBRE';
    case OCUPADA = 'OCUPADA';
}
