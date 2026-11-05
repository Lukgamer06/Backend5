<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiResource extends JsonResource
{
    /**
     * @return array{message: string, meta: object}
     */
    public function with(Request $request): array
    {
        return [
            'message' => 'Solicitud exitosa.',
            'meta' => (object) [],
        ];
    }
}
