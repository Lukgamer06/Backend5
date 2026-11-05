<?php

declare(strict_types=1);

namespace App\Http\Requests\Incidents;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Models\Incidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Incidencia::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'prioridad' => ['nullable', Rule::enum(PrioridadIncidenciaEnum::class)],
            'estado' => ['nullable', Rule::enum(EstadoIncidenciaEnum::class)],
        ];
    }
}
