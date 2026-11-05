<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetallePedido>
 */
class DetallePedidoFactory extends Factory
{
    protected $model = DetallePedido::class;

    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 20);
        $precioUnitario = fake()->randomFloat(2, 1, 100000);

        return [
            'pedido_id' => Pedido::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => round($cantidad * $precioUnitario, 2),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
