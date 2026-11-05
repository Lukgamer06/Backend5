<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoPagoEnum: string
{
    case PENDIENTE = 'PENDIENTE';
    case APROBADO = 'APROBADO';
    case RECHAZADO = 'RECHAZADO';
    case EXPIRADO = 'EXPIRADO';
    case CANCELADO = 'CANCELADO';
    case REEMBOLSADO = 'REEMBOLSADO';
}
