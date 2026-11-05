<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cliente;
use App\Repositories\CustomerRepository;

abstract class CustomerService
{
    public function __construct(
        protected readonly CustomerRepository $customerRepository,
    ) {}

    abstract public function find(int $clienteId): ?Cliente;

    abstract public function save(Cliente $cliente): Cliente;
}
