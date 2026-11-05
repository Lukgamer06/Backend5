<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Enums\CanalPagoEnum;
use App\Enums\EstadoPagoEnum;
use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Pago::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoPagoEnum::class)],
            'canal' => ['nullable', Rule::enum(CanalPagoEnum::class)],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
        ];
    }
}
