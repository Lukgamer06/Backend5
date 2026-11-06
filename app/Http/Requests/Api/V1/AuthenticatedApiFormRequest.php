<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

abstract class AuthenticatedApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Usuario;
    }

    /**
     * @return array<string, string>
     */
    protected function paginationRules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
