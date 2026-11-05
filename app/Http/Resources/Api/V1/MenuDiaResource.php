<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class MenuDiaResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha->toDateString(),
            'estado' => $this->estado->value,
            'productos' => ProductoResource::collection($this->whenLoaded('productos')),
        ];
    }
}
