<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Incidents;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        return [
            'estado' => [
                'required_without:prioridad',
                Rule::enum(EstadoIncidenciaEnum::class),
            ],
            'prioridad' => [
                'required_without:estado',
                Rule::enum(PrioridadIncidenciaEnum::class),
            ],
            'usuario_reporta_id' => ['prohibited'],
            'mesa_id' => ['prohibited'],
            'pedido_id' => ['prohibited'],
            'titulo' => ['prohibited'],
            'descripcion' => ['prohibited'],
        ];
    }
}
