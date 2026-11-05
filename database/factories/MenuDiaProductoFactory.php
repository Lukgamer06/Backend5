<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MenuDia;
use App\Models\MenuDiaProducto;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuDiaProducto>
 */
class MenuDiaProductoFactory extends Factory
{
    protected $model = MenuDiaProducto::class;

    public function definition(): array
    {
        return [
            'menu_dia_id' => MenuDia::factory(),
            'producto_id' => Producto::factory(),
        ];
    }
}
