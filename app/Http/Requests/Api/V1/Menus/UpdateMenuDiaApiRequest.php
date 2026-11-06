<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Menus;

use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use App\Models\MenuDia;
use Illuminate\Validation\Rule;

class UpdateMenuDiaApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        $menuDia = $this->route('menuDia');
        $menuDiaId = $menuDia instanceof MenuDia ? $menuDia->getKey() : null;

        return [
            'fecha' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($menuDiaId): void {
                    $query = MenuDia::query()->whereDate('fecha', $value);

                    if ($menuDiaId !== null) {
                        $query->whereKeyNot($menuDiaId);
                    }

                    if ($query->exists()) {
                        $fail('Ya existe un menú para la fecha seleccionada.');
                    }
                },
            ],
            'productos' => ['sometimes', 'nullable', 'array', 'max:100'],
            'productos.*' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')],
        ];
    }
}
