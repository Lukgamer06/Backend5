<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReservationConflictException extends ValidationException
{
    public function __construct()
    {
        $validator = Validator::make([], []);
        $validator->errors()->add(
            'mesa_id',
            'La mesa ya tiene una reserva en el horario seleccionado.',
        );

        parent::__construct($validator);
    }
}
