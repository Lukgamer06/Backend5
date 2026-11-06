<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservations;

use App\Enums\RolUsuarioEnum;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Reserva::class) ?? false;
    }

    public function rules(): array
    {
        $usuario = $this->user();
        $esPersonal = $usuario instanceof Usuario
            && in_array($usuario->rol, [
                RolUsuarioEnum::ADMINISTRADOR,
                RolUsuarioEnum::OPERADOR,
            ], true);

        return [
            'cliente_id' => $esPersonal
                ? ['required', 'integer', 'exists:clientes,id']
                : ['prohibited'],
            'mesa_id' => ['required', 'integer', 'exists:mesas,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d\TH:i'],
            'fecha_fin' => [
                'required',
                'date_format:Y-m-d\TH:i',
                'after:fecha_inicio',
            ],
            'numero_personas' => ['required', 'integer', 'min:1', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
