<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pedido;

abstract class OrderRepository
{
    public function __construct() {}

    abstract public function find(int $pedidoId): ?Pedido;

    abstract public function save(Pedido $pedido): Pedido;
}
