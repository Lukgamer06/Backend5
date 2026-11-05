<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\EstadoPedidoEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pedido = $this->route('pedido');

        return $pedido instanceof Pedido
            && ($this->user()?->can('update', $pedido) ?? false);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
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
            'estado' => ['required', Rule::enum(EstadoPedidoEnum::class)],
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
