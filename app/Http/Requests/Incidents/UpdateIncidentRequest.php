<?php

declare(strict_types=1);

namespace App\Http\Requests\Incidents;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Models\Incidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incidencia = $this->route('incidencia');

        return $incidencia instanceof Incidencia
            && ($this->user()?->can('update', $incidencia) ?? false);
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoIncidenciaEnum::class)],
            'prioridad' => ['required', Rule::enum(PrioridadIncidenciaEnum::class)],
        ];
    }
}
