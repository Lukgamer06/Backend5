<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderStateConflictException extends ValidationException
{
    public function __construct()
    {
        $validator = Validator::make([], []);
        $validator->errors()->add(
            'estado',
            'La transición de estado solicitada no está permitida.',
        );

        parent::__construct($validator);
    }
}
