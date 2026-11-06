<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Producto::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'disponible' => ['nullable', Rule::in(['0', '1'])],
        ];
    }
}
