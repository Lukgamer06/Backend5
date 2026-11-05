<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentCollection extends ResourceCollection
{
    public $collects = PagoResource::class;

    public function with(Request $request): array
    {
        return [
            'links' => (object) [],
            'message' => 'Pagos del pedido consultados correctamente.',
            'meta' => [
                'count' => $this->collection->count(),
            ],
        ];
    }
}
