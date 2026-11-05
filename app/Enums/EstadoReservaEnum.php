<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoReservaEnum: string
{
    case CONFIRMADA = 'CONFIRMADA';
    case ACTIVA = 'ACTIVA';
    case FINALIZADA = 'FINALIZADA';
    case CANCELADA = 'CANCELADA';
}
