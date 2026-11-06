<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Cliente;

abstract class CustomerRepository
{
    abstract public function find(int $clienteId): ?Cliente;

    abstract public function save(Cliente $cliente): Cliente;
}
