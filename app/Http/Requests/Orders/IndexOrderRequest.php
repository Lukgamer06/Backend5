<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\EstadoPedidoEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Pedido::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoPedidoEnum::class)],
            'tipo_atencion' => ['nullable', Rule::enum(TipoAtencionEnum::class)],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
        ];
    }
}
