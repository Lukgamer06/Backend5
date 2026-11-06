<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use App\Enums\EstadoPedidoEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoPedidoEnum::class)],
            'usuario_id' => ['prohibited'],
            'cliente_id' => ['prohibited'],
            'total' => ['prohibited'],
        ];
    }
}
