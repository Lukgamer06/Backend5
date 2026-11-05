<?php

declare(strict_types=1);

namespace App\Enums;

enum EstadoMenuDiaEnum: string
{
    case BORRADOR = 'BORRADOR';
    case PUBLICADO = 'PUBLICADO';
}
