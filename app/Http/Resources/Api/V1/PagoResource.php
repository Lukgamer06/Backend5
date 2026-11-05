<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class PagoResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pedido_id' => $this->pedido_id,
            'monto' => $this->monto,
            'canal' => $this->canal->value,
            'estado' => $this->estado->value,
            'referencia_operacion' => $this->referencia_operacion,
        ];
    }
}
