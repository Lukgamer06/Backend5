<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Incidencia;

abstract class IncidentService
{
    public function __construct() {}

    abstract public function report(Incidencia $incidencia): Incidencia;
}
