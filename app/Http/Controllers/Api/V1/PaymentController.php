<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Api\V1\PaymentCreationData;
use App\Enums\CanalPagoEnum;
use App\Http\Requests\Api\V1\Payments\CreatePaymentApiRequest;
use App\Http\Resources\Api\V1\PagoResource;
use App\Http\Resources\Api\V1\PaymentCollection;
use App\Models\Pago;
use App\Models\Pedido;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function show(Pago $pago): PagoResource
    {
        Gate::authorize('view', $pago);

        return PagoResource::make($this->paymentService->findOrFail($pago->getKey()));
    }

    public function indexForOrder(Pedido $pedido): PaymentCollection
    {
        Gate::authorize('view', $pedido);

        return PaymentCollection::make($this->paymentService->forOrder($pedido));
    }

    public function createOnline(
        CreatePaymentApiRequest $request,
        Pedido $pedido,
    ): JsonResponse {
        return $this->createForOrder($request, $pedido, CanalPagoEnum::EN_LINEA);
    }

    public function createInPerson(
        CreatePaymentApiRequest $request,
        Pedido $pedido,
    ): JsonResponse {
        return $this->createForOrder($request, $pedido, CanalPagoEnum::PRESENCIAL);
    }

    private function createForOrder(
        CreatePaymentApiRequest $request,
        Pedido $pedido,
        CanalPagoEnum $channel,
    ): JsonResponse {
        Gate::authorize('create', Pedido::class);
        Gate::authorize('view', $pedido);

        $result = $this->paymentService->createForOrder(
            $pedido,
            new PaymentCreationData(
                $request->validated('idempotency_key'),
                $channel,
            ),
        );

        $message = match (true) {
            ! $result['created'] => 'Solicitud idempotente: se devuelve el pago existente.',
            $channel === CanalPagoEnum::EN_LINEA => 'Solicitud de pago en línea registrada pendiente de procesamiento.',
            default => 'Pago presencial registrado pendiente de confirmación.',
        };

        $resource = PagoResource::make($result['payment'])->withMessage($message);

        return $resource
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }
}
