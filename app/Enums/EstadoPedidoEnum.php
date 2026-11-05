<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoPedidoEnum: string
{
    case REGISTRADO = 'REGISTRADO';
    case EN_PREPARACION = 'EN_PREPARACION';
    case LISTO = 'LISTO';
    case ENTREGADO = 'ENTREGADO';
    case CANCELADO = 'CANCELADO';
}
