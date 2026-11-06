<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pago;
use App\Repositories\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository,
    ) {}

    /**
     * @param  array{
     *     search?: string,
     *     estado?: string,
     *     canal?: string,
     *     fecha_desde?: string,
     *     fecha_hasta?: string
     * }  $filters
     * @return LengthAwarePaginator<int, Pago>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->paymentRepository->paginate($filters);
    }

    public function find(int $pagoId): ?Pago
    {
        return $this->paymentRepository->find($pagoId);
    }

    public function findByIdempotencyKey(string $claveIdempotencia): ?Pago
    {
        return $this->paymentRepository->findByIdempotencyKey($claveIdempotencia);
    }

    public function register(Pago $pago): Pago
    {
        return $this->paymentRepository->save($pago);
    }
}
