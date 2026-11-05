<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoPedidoEnum;
use App\Http\Requests\Api\V1\Orders\IndexOrderApiRequest;
use App\Http\Requests\Api\V1\Orders\StoreOrderApiRequest;
use App\Http\Requests\Api\V1\Orders\UpdateOrderApiRequest;
use App\Http\Requests\Api\V1\Orders\UpdateOrderStatusApiRequest;
use App\Http\Resources\Api\V1\OrderCollection;
use App\Http\Resources\Api\V1\PedidoResource;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Services\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(IndexOrderApiRequest $request): OrderCollection
    {
        $usuario = $this->authenticatedUser($request);
        Gate::authorize('viewAny', Pedido::class);

        return OrderCollection::make(
            $this->orderService->paginate($usuario, $request->validated()),
        );
    }

    public function show(Pedido $pedido): PedidoResource
    {
        Gate::authorize('view', $pedido);

        return PedidoResource::make($this->orderService->findOrFail($pedido->getKey()));
    }

    public function store(StoreOrderApiRequest $request): JsonResponse
    {
        $usuario = $this->authenticatedUser($request);
        Gate::authorize('create', Pedido::class);
        $data = $request->validated();
        $lineas = $data['lineas'];
        unset($data['lineas']);

        $pedido = $this->orderService->register($usuario, $data, $lineas);

        return PedidoResource::make($pedido)
            ->withMessage('Pedido creado correctamente.')
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateOrderApiRequest $request, Pedido $pedido): PedidoResource
    {
        Gate::authorize('update', $pedido);

        return PedidoResource::make(
            $this->orderService->updatePartial($pedido, $request->validated()),
        )->withMessage('Pedido actualizado correctamente.');
    }

    public function cancel(Request $request, Pedido $pedido): PedidoResource
    {
        Gate::authorize('cancel', $pedido);

        return PedidoResource::make($this->orderService->cancel($pedido))
            ->withMessage('Pedido cancelado correctamente.');
    }

    public function changeStatus(
        UpdateOrderStatusApiRequest $request,
        Pedido $pedido,
    ): PedidoResource {
        Gate::authorize('changeStatus', $pedido);
        $estado = EstadoPedidoEnum::from($request->validated('estado'));

        return PedidoResource::make($this->orderService->changeStatus($pedido, $estado))
            ->withMessage('Estado del pedido actualizado correctamente.');
    }

    private function authenticatedUser(Request $request): Usuario
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new AuthorizationException('Se requiere una sesión autenticada.');
        }

        return $usuario;
    }
}
