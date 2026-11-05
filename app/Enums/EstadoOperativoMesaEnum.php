<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoOperativoMesaEnum: string
{
    case HABILITADA = 'HABILITADA';
    case MANTENIMIENTO = 'MANTENIMIENTO';
    case INACTIVA = 'INACTIVA';
}
