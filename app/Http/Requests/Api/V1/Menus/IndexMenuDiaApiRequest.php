<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Menus;

use App\Enums\EstadoMenuDiaEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use Illuminate\Validation\Rule;

class IndexMenuDiaApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        return [
            'estado' => ['sometimes', 'nullable', Rule::enum(EstadoMenuDiaEnum::class)],
            'fecha_desde' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:fecha_desde',
            ],
            ...$this->paginationRules(),
        ];
    }
}
