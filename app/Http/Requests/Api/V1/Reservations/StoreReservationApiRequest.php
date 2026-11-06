<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reservations;

use App\Enums\RolUsuarioEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use App\Models\Usuario;

class StoreReservationApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        $usuario = $this->user();
        $esPersonal = $usuario instanceof Usuario
            && in_array($usuario->rol, [
                RolUsuarioEnum::ADMINISTRADOR,
                RolUsuarioEnum::OPERADOR,
            ], true);

        return [
            'cliente_id' => $esPersonal
                ? ['required', 'integer', 'exists:clientes,id']
                : ['prohibited'],
            'usuario_id' => ['prohibited'],
            'estado' => ['prohibited'],
            'mesa_id' => ['required', 'integer', 'exists:mesas,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d\TH:i'],
            'fecha_fin' => [
                'required',
                'date_format:Y-m-d\TH:i',
                'after:fecha_inicio',
            ],
            'numero_personas' => ['required', 'integer', 'min:1', 'max:255'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
