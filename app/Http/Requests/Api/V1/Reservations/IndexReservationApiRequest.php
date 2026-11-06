<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reservations;

use App\Enums\EstadoReservaEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use Illuminate\Validation\Rule;

class IndexReservationApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        $fechaHastaRules = ['sometimes', 'nullable', 'date_format:Y-m-d'];

        if ($this->filled('fecha_desde')) {
            $fechaHastaRules[] = 'after_or_equal:fecha_desde';
        }

        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'estado' => ['sometimes', 'nullable', Rule::enum(EstadoReservaEnum::class)],
            'fecha_desde' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => $fechaHastaRules,
            ...$this->paginationRules(),
        ];
    }
}
