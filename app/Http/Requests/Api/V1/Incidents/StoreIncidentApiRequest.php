<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Incidents;

use App\Enums\PrioridadIncidenciaEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:65535'],
            'prioridad' => ['sometimes', Rule::enum(PrioridadIncidenciaEnum::class)],
            'mesa_id' => ['sometimes', 'nullable', 'integer', 'exists:mesas,id'],
            'pedido_id' => ['sometimes', 'nullable', 'integer', 'exists:pedidos,id'],
            'usuario_reporta_id' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }
}
