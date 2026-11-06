<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoPedidoEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\TipoAtencionEnum;
use App\Http\Requests\Orders\IndexOrderRequest;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Requests\Orders\UpdateOrderStatusRequest;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OrderController
{
    public function index(
        IndexOrderRequest $request,
        OrderService $orderService,
    ): View {
        $usuario = $this->authenticatedUser($request);

        return view('orders.index', [
            'orders' => $orderService->paginate($usuario, $request->validated()),
            'filters' => $request->validated(),
            'breadcrumbs' => [
                ['label' => 'Pedidos'],
            ],
        ]);
    }

    public function create(
        Request $request,
        OrderService $orderService,
    ): View {
        Gate::authorize('create', Pedido::class);
        $usuario = $this->authenticatedUser($request);

        return view('orders.create', [
            ...$orderService->formOptions($usuario),
            'modalities' => ModalidadServicioEnum::cases(),
            'attentionTypes' => TipoAtencionEnum::cases(),
            'breadcrumbs' => [
                ['label' => 'Pedidos', 'url' => route('orders.index')],
                ['label' => 'Nuevo pedido'],
            ],
        ]);
    }

    public function store(
        StoreOrderRequest $request,
        OrderService $orderService,
    ): RedirectResponse {
        $pedido = $orderService->register(
            $this->authenticatedUser($request),
            $request->validated(),
            $request->validated('lineas'),
        );

        return redirect()
            ->route('orders.show', $pedido)
            ->with('status', 'El pedido fue registrado correctamente.');
    }

    public function show(Pedido $pedido, OrderService $orderService): View
    {
        Gate::authorize('view', $pedido);

        return view('orders.show', [
            'order' => $pedido->loadMissing([
                'cliente',
                'mesa',
                'reserva',
                'usuario',
                'detalles.producto',
                'pagos',
            ]),
            'availableTransitions' => $orderService->availableTransitions($pedido),
            'breadcrumbs' => [
                ['label' => 'Pedidos', 'url' => route('orders.index')],
                ['label' => 'Detalle de pedido'],
            ],
        ]);
    }

    public function edit(
        Pedido $pedido,
        Request $request,
        OrderService $orderService,
    ): View {
        Gate::authorize('update', $pedido);
        $pedido->loadMissing('detalles.producto');

        return view('orders.edit', [
            'order' => $pedido,
            'statuses' => $orderService->availableTransitions($pedido),
            'initialLines' => $pedido->detalles->map(static fn ($detail): array => [
                'producto_id' => $detail->producto_id,
                'cantidad' => $detail->cantidad,
                'observaciones' => $detail->observaciones,
            ])->all(),
            ...$orderService->formOptions(
                $this->authenticatedUser($request),
                $pedido->cliente_id,
            ),
            'modalities' => ModalidadServicioEnum::cases(),
            'attentionTypes' => TipoAtencionEnum::cases(),
            'breadcrumbs' => [
                ['label' => 'Pedidos', 'url' => route('orders.index')],
                ['label' => 'Editar pedido'],
            ],
        ]);
    }

    public function update(
        UpdateOrderRequest $request,
        Pedido $pedido,
        OrderService $orderService,
    ): RedirectResponse {
        $data = $request->validated();
        $orderService->update($pedido, $data, $data['lineas']);

        return redirect()
            ->route('orders.show', $pedido)
            ->with('status', 'El pedido fue actualizado correctamente.');
    }

    public function cancel(
        Pedido $pedido,
        OrderService $orderService,
    ): RedirectResponse {
        Gate::authorize('cancel', $pedido);
        $orderService->cancel($pedido);

        return redirect()
            ->route('orders.show', $pedido)
            ->with('status', 'El pedido fue cancelado.');
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Pedido $pedido,
        OrderService $orderService,
    ): RedirectResponse {
        $orderService->changeStatus(
            $pedido,
            EstadoPedidoEnum::from($request->validated('estado')),
        );

        return redirect()
            ->route('orders.show', $pedido)
            ->with('status', 'El estado del pedido fue actualizado.');
    }

    public function destroy(
        Pedido $pedido,
        OrderService $orderService,
    ): RedirectResponse {
        Gate::authorize('delete', $pedido);
        $orderService->delete($pedido);

        return redirect()
            ->route('orders.index')
            ->with('status', 'El pedido fue eliminado correctamente.');
    }

    private function authenticatedUser(Request $request): Usuario
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new UnauthorizedHttpException('web');
        }

        return $usuario;
    }
}
