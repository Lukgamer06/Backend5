<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Payments;

use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;

class CreatePaymentApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:100'],
            'clave_idempotencia' => ['prohibited'],
            'pedido_id' => ['prohibited'],
            'monto' => ['prohibited'],
            'canal' => ['prohibited'],
            'estado' => ['prohibited'],
            'referencia_operacion' => ['prohibited'],
            'proveedor' => ['prohibited'],
            'transaccion_externa' => ['prohibited'],
        ];
    }

    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'idempotency_key' => $this->header('Idempotency-Key'),
        ];
    }
}
