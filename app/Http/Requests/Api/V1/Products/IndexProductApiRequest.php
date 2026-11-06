<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Products;

use App\Enums\RolUsuarioEnum;
use App\Http\Requests\Api\V1\AuthenticatedApiFormRequest;
use App\Models\Usuario;
use Illuminate\Validation\Rule;

class IndexProductApiRequest extends AuthenticatedApiFormRequest
{
    public function rules(): array
    {
        $usuario = $this->user();
        $esAdministrador = $usuario instanceof Usuario
            && $usuario->rol === RolUsuarioEnum::ADMINISTRADOR;

        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'disponible' => $esAdministrador
                ? ['sometimes', Rule::in(['0', '1', 0, 1, true, false])]
                : ['prohibited'],
            ...$this->paginationRules(),
        ];
    }
}
