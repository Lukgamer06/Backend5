<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class IncidentCollection extends ResourceCollection
{
    public $collects = IncidenciaResource::class;

    public function with(Request $request): array
    {
        return [
            'message' => 'Incidencias consultadas correctamente.',
        ];
    }
}
