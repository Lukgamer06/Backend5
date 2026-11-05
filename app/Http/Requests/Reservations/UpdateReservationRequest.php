<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservations;

use App\Enums\EstadoReservaEnum;
use App\Models\Reserva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reserva = $this->route('reserva');

        return $reserva instanceof Reserva
            && ($this->user()?->can('update', $reserva) ?? false);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'mesa_id' => ['required', 'integer', 'exists:mesas,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d\TH:i'],
            'fecha_fin' => [
                'required',
                'date_format:Y-m-d\TH:i',
                'after:fecha_inicio',
            ],
            'numero_personas' => ['required', 'integer', 'min:1', 'max:255'],
            'estado' => ['required', Rule::enum(EstadoReservaEnum::class)],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
