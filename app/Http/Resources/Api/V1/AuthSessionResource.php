<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\DTOs\Api\V1\AuthenticatedApiSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuthenticatedApiSession
 */
class AuthSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource->usuario === null) {
            return ['logged_out' => true];
        }

        $usuario = $this->resource->usuario;
        $data = [
            'usuario' => [
                'id' => $usuario->id,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'correo' => $usuario->correo,
                'rol' => $usuario->rol->value,
            ],
        ];

        if ($this->resource->accessToken !== null) {
            $data['access_token'] = $this->resource->accessToken;
            $data['token_type'] = 'Bearer';
        }

        return $data;
    }
}
