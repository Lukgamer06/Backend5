<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1;

use App\Enums\CanalPagoEnum;

final readonly class PaymentCreationData
{
    public function __construct(
        public string $idempotencyKey,
        public CanalPagoEnum $channel,
    ) {}
}
