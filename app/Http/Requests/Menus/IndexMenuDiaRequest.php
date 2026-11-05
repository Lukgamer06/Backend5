<?php

declare(strict_types=1);

namespace App\Http\Requests\Menus;

use App\Enums\EstadoMenuDiaEnum;
use App\Models\MenuDia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMenuDiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', MenuDia::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::enum(EstadoMenuDiaEnum::class)],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
        ];
    }
}
