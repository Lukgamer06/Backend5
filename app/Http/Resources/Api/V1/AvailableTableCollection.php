<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AvailableTableCollection extends ResourceCollection
{
    public $collects = MesaDisponibleResource::class;

    public function with(Request $request): array
    {
        return [
            'links' => (object) [],
            'message' => 'Mesas disponibles consultadas correctamente.',
            'meta' => (object) [],
        ];
    }
}
