<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\ModalidadServicioEnum;
use App\Enums\RolUsuarioEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Pedido::class) ?? false;
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
            'reserva_id' => [
                'nullable',
                'integer',
                'exists:reservas,id',
                Rule::prohibitedIf(
                    $this->input('modalidad_servicio') === ModalidadServicioEnum::SIN_MESA->value,
                ),
            ],
            'mesa_id' => [
                'nullable',
                'integer',
                'exists:mesas,id',
                Rule::requiredIf(
                    $this->input('modalidad_servicio') === ModalidadServicioEnum::EN_MESA->value,
                ),
                Rule::prohibitedIf(
                    $this->input('modalidad_servicio') === ModalidadServicioEnum::SIN_MESA->value,
                ),
            ],
            'tipo_atencion' => ['required', Rule::enum(TipoAtencionEnum::class)],
            'modalidad_servicio' => ['required', Rule::enum(ModalidadServicioEnum::class)],
            'fecha_programada' => [
                'nullable',
                'required_if:tipo_atencion,'.TipoAtencionEnum::PROGRAMADO->value,
                'date_format:Y-m-d\TH:i',
                'after:now',
            ],
            'lineas' => ['required', 'array', 'min:1', 'max:30'],
            'lineas.*' => ['required', 'array:producto_id,cantidad,observaciones'],
            'lineas.*.producto_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('productos', 'id')->where('disponible', true),
            ],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1', 'max:65535'],
            'lineas.*.observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
