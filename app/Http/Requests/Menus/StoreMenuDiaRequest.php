<?php

declare(strict_types=1);

namespace App\Http\Requests\Menus;

use App\Models\MenuDia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuDiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MenuDia::class) ?? false;
    }

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
            'productos' => ['nullable', 'array'],
            'productos.*' => [
                'integer',
                'distinct',
                Rule::exists('productos', 'id')->where('disponible', true),
            ],
        ];
    }
}
