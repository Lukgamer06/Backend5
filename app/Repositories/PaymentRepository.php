<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pago;

abstract class PaymentRepository
{
    public function __construct() {}

    abstract public function findByIdempotencyKey(string $claveIdempotencia): ?Pago;

    abstract public function save(Pago $pago): Pago;
}
