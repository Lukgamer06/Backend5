<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class PaymentIdempotencyConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('La clave de idempotencia ya se utilizó para otra solicitud.');
    }
}
