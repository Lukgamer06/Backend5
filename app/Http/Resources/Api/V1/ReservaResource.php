<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class ReservaResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'cliente_id' => $this->cliente_id,
            'mesa_id' => $this->mesa_id,
            'fecha_inicio' => $this->fecha_inicio->toIso8601String(),
            'fecha_fin' => $this->fecha_fin->toIso8601String(),
            'numero_personas' => $this->numero_personas,
            'estado' => $this->estado->value,
            'observaciones' => $this->observaciones,
            'mesa' => MesaDisponibleResource::make($this->whenLoaded('mesa')),
            'usuario' => UsuarioResource::make($this->whenLoaded('usuario')),
            'cliente' => ClienteResource::make($this->whenLoaded('cliente')),
        ];
    }
}
