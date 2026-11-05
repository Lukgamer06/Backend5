<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reservations;

use App\Enums\RolUsuarioEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use App\Models\Usuario;

class UpdateReservationApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        $usuario = $this->user();
        $esPersonal = $usuario instanceof Usuario
            && in_array($usuario->rol, [
                RolUsuarioEnum::ADMINISTRADOR,
                RolUsuarioEnum::OPERADOR,
            ], true);

        $fechaFinRules = ['sometimes', 'required', 'date_format:Y-m-d\TH:i'];

        if ($this->has('fecha_inicio')) {
            $fechaFinRules[] = 'after:fecha_inicio';
        }

        return [
            'cliente_id' => $esPersonal
                ? ['sometimes', 'required', 'integer', 'exists:clientes,id']
                : ['prohibited'],
            'usuario_id' => ['prohibited'],
            'estado' => ['prohibited'],
            'mesa_id' => ['sometimes', 'required', 'integer', 'exists:mesas,id'],
            'fecha_inicio' => ['sometimes', 'required', 'date_format:Y-m-d\TH:i'],
            'fecha_fin' => $fechaFinRules,
            'numero_personas' => ['sometimes', 'required', 'integer', 'min:1', 'max:255'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
