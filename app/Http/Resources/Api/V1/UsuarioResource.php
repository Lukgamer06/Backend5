<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class UsuarioResource extends ApiResource
{
    /**
     * @return array{id: int, nombres: string, apellidos: string, correo: string, telefono: ?string, rol: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'correo' => $this->correo,
            'telefono' => $this->telefono,
            'rol' => $this->rol->value,
        ];
    }
}
