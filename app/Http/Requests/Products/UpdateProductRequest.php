<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $producto = $this->route('producto');

        return $producto instanceof Producto
            && ($this->user()?->can('update', $producto) ?? false);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
        ];
    }
}
