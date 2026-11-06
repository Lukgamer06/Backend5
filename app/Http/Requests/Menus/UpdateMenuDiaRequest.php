<?php

declare(strict_types=1);

namespace App\Http\Requests\Menus;

use App\Models\MenuDia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuDiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $menuDia = $this->route('menuDia');

        return $menuDia instanceof MenuDia
            && ($this->user()?->can('update', $menuDia) ?? false);
    }

    public function rules(): array
    {
        $menuDia = $this->route('menuDia');

        return [
            'fecha' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($menuDia): void {
                    if (! $menuDia instanceof MenuDia) {
                        return;
                    }

                    $exists = MenuDia::query()
                        ->whereDate('fecha', $value)
                        ->whereKeyNot($menuDia->getKey())
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un menú para la fecha seleccionada.');
                    }
                },
            ],
            'productos' => ['nullable', 'array'],
            'productos.*' => [
                'integer',
                'distinct',
                Rule::exists('productos', 'id'),
            ],
        ];
    }
}
