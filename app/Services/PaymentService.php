<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Api\V1\PaymentCreationData;
use App\Enums\EstadoPagoEnum;
use App\Models\Pago;
use App\Models\Pedido;
use App\Repositories\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function findOrFail(int $pagoId): Pago
    {
        return $this->find($pagoId)
            ?? throw (new ModelNotFoundException)->setModel(Pago::class, [$pagoId]);
    }

    public function findByIdempotencyKey(string $claveIdempotencia): ?Pago
    {
        return $this->paymentRepository->findByIdempotencyKey($claveIdempotencia);
    }

    public function register(Pago $pago): Pago
    {
        return $this->paymentRepository->save($pago);
    }

    /**
     * @return Collection<int, Pago>
     */
    public function forOrder(Pedido $pedido): Collection
    {
        return $this->paymentRepository->forOrder($pedido->getKey());
    }

    /**
     * @return array{payment: Pago, created: bool}
     */
    public function createForOrder(
        Pedido $pedido,
        PaymentCreationData $data,
    ): array {
        try {
            return DB::transaction(function () use ($pedido, $data): array {
                $lockedPedido = $this->paymentRepository->findOrderForUpdate(
                    $pedido->getKey(),
                );

                if (! $lockedPedido instanceof Pedido) {
                    throw (new ModelNotFoundException)->setModel(Pedido::class, [
                        $pedido->getKey(),
                    ]);
                }

                $existing = $this->findByIdempotencyKey($data->idempotencyKey);

                if ($existing instanceof Pago) {
                    $this->assertSameRequest($existing, $lockedPedido, $data);

                    return ['payment' => $existing, 'created' => false];
                }

                $payment = $this->paymentRepository->save(new Pago([
                    'pedido_id' => $lockedPedido->getKey(),
                    'monto' => $lockedPedido->total,
                    'canal' => $data->channel,
                    'estado' => EstadoPagoEnum::PENDIENTE,
                    'referencia_operacion' => (string) Str::uuid(),
                    'clave_idempotencia' => $data->idempotencyKey,
                ]));

                return ['payment' => $payment, 'created' => true];
            });
        } catch (QueryException $exception) {
            $existing = $this->findByIdempotencyKey($data->idempotencyKey);

            if (! $existing instanceof Pago) {
                throw $exception;
            }

            $this->assertSameRequest($existing, $pedido, $data);

            return ['payment' => $existing, 'created' => false];
        }
    }

    private function assertSameRequest(
        Pago $payment,
        Pedido $pedido,
        PaymentCreationData $data,
    ): void {
        if (
            $payment->pedido_id !== $pedido->getKey()
            || $payment->canal !== $data->channel
        ) {
            throw new PaymentIdempotencyConflictException;
        }
    }
}
