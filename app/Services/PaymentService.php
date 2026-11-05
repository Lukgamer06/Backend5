<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pago;
use App\Repositories\PaymentRepository;

abstract class PaymentService
{
    public function __construct(
        protected readonly PaymentRepository $paymentRepository,
    ) {}

    abstract public function findByIdempotencyKey(string $claveIdempotencia): ?Pago;

    abstract public function register(Pago $pago): Pago;
}
