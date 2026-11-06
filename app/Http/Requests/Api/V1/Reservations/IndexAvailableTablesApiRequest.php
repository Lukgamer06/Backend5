<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reservations;

use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;

class IndexAvailableTablesApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        return [
            'fecha_inicio' => ['required', 'date_format:Y-m-d\TH:i'],
            'fecha_fin' => [
                'required',
                'date_format:Y-m-d\TH:i',
                'after:fecha_inicio',
            ],
            'numero_personas' => ['required', 'integer', 'min:1', 'max:255'],
        ];
    }
}
