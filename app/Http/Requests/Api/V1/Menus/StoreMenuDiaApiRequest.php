<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Menus;

use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use App\Models\MenuDia;
use Illuminate\Validation\Rule;

class StoreMenuDiaApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        return [
            'fecha' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (MenuDia::query()->whereDate('fecha', $value)->exists()) {
                        $fail('Ya existe un menú para la fecha seleccionada.');
                    }
                },
            ],
            'productos' => ['sometimes', 'nullable', 'array', 'max:100'],
            'productos.*' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')],
        ];
    }
}
