<?php

declare(strict_types=1);

namespace App\Http\Requests\Incidents;

use App\Enums\PrioridadIncidenciaEnum;
use App\Models\Incidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Incidencia::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:65535'],
            'prioridad' => ['required', Rule::enum(PrioridadIncidenciaEnum::class)],
            'mesa_id' => ['nullable', 'integer', 'exists:mesas,id'],
            'pedido_id' => ['nullable', 'integer', 'exists:pedidos,id'],
            'usuario_reporta_id' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }
}
