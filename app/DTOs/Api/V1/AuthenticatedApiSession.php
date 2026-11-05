<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1;

use App\Models\Usuario;

final readonly class AuthenticatedApiSession
{
    public function __construct(
        public ?Usuario $usuario,
        public ?string $accessToken = null,
    ) {}
}
