<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use App\Enums\ModalidadServicioEnum;
use App\Enums\RolUsuarioEnum;
use App\Enums\TipoAtencionEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use App\Models\Usuario;
use Illuminate\Validation\Rule;

class UpdateOrderApiRequest extends AuthenticatedApiFormRequest
{
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
                ? ['sometimes', 'required', 'integer', 'exists:clientes,id']
                : ['prohibited'],
            'usuario_id' => ['prohibited'],
            'estado' => ['prohibited'],
            'total' => ['prohibited'],
            'reserva_id' => [
                'sometimes',
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
            'tipo_atencion' => ['sometimes', 'required', Rule::enum(TipoAtencionEnum::class)],
            'modalidad_servicio' => [
                'sometimes',
                'required',
                Rule::enum(ModalidadServicioEnum::class),
            ],
            'fecha_programada' => [
                'nullable',
                'required_if:tipo_atencion,'.TipoAtencionEnum::PROGRAMADO->value,
                'date_format:Y-m-d\TH:i',
                'after:now',
            ],
            'lineas' => ['sometimes', 'required', 'array', 'min:1', 'max:30'],
            'lineas.*' => ['required', 'array:producto_id,cantidad,observaciones'],
            'lineas.*.producto_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('productos', 'id')->where('disponible', true),
            ],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1', 'max:65535'],
            'lineas.*.observaciones' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
