<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiResource extends JsonResource
{
    protected string $message = 'Solicitud exitosa.';

    public function withMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array{message: string, meta: object}
     */
    public function with(Request $request): array
    {
        return [
            'message' => $this->message,
            'meta' => (object) [],
        ];
    }
}
