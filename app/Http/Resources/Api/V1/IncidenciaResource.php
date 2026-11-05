<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class IncidenciaResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario_reporta_id' => $this->usuario_reporta_id,
            'mesa_id' => $this->mesa_id,
            'pedido_id' => $this->pedido_id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'prioridad' => $this->prioridad->value,
            'estado' => $this->estado->value,
            'usuario' => UsuarioResource::make($this->whenLoaded('usuario')),
        ];
    }
}
