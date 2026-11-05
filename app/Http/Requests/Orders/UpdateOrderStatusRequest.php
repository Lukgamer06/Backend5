<?php

declare(strict_types=1);

namespace App\Http\Requests\Orders;

use App\Enums\EstadoPedidoEnum;
use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pedido = $this->route('pedido');

        return $pedido instanceof Pedido
            && ($this->user()?->can('changeStatus', $pedido) ?? false);
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoPedidoEnum::class)],
        ];
    }
}
