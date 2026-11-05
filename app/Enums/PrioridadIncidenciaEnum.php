<?php

declare(strict_types=1);

namespace App\Enums;

enum PrioridadIncidenciaEnum: string
{
    case BAJA = 'BAJA';
    case MEDIA = 'MEDIA';
    case ALTA = 'ALTA';
    case CRITICA = 'CRITICA';
}
