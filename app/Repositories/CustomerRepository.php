<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Cliente;

abstract class CustomerRepository
{
    public function __construct() {}

    abstract public function find(int $clienteId): ?Cliente;

    abstract public function save(Cliente $cliente): Cliente;
}
