<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MenuDiaCollection extends ResourceCollection
{
    public $collects = MenuDiaResource::class;

    public function with(Request $request): array
    {
        return [
            'message' => 'Menús del día consultados correctamente.',
        ];
    }
}
