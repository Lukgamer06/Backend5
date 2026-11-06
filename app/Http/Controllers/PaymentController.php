<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Payments\IndexPaymentRequest;
use App\Models\Pago;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController
{
    public function index(
        IndexPaymentRequest $request,
        PaymentService $paymentService,
    ): View {
        return view('payments.index', [
            'payments' => $paymentService->paginate($request->validated()),
            'filters' => $request->validated(),
            'breadcrumbs' => [
                ['label' => 'Pagos'],
            ],
        ]);
    }

    public function show(Pago $pago, PaymentService $paymentService): View
    {
        Gate::authorize('view', $pago);

        $payment = $paymentService->find($pago->getKey());

        abort_if($payment === null, 404);

        return view('payments.show', [
            'payment' => $payment,
            'breadcrumbs' => [
                ['label' => 'Pagos', 'url' => route('payments.index')],
                ['label' => 'Historial del pago'],
            ],
        ]);
    }
}
