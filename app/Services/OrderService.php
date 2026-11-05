<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pedido;
use App\Repositories\OrderRepository;

abstract class OrderService
{
    public function __construct(
        protected readonly OrderRepository $orderRepository,
    ) {}

    abstract public function find(int $pedidoId): ?Pedido;

    abstract public function register(Pedido $pedido): Pedido;
}
