<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Incidents;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use Illuminate\Validation\Rule;

class IndexIncidentApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'prioridad' => ['sometimes', 'nullable', Rule::enum(PrioridadIncidenciaEnum::class)],
            'estado' => ['sometimes', 'nullable', Rule::enum(EstadoIncidenciaEnum::class)],
            ...$this->paginationRules(),
        ];
    }
}
