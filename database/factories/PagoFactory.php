<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CanalPagoEnum;
use App\Enums\EstadoPagoEnum;
use App\Models\Pago;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        $canal = fake()->randomElement(CanalPagoEnum::cases());

        return [
            'pedido_id' => Pedido::factory(),
            'monto' => fake()->randomFloat(2, 1, 1000000),
            'canal' => $canal,
            'estado' => EstadoPagoEnum::PENDIENTE,
            'referencia_operacion' => Str::uuid()->toString(),
            'clave_idempotencia' => Str::uuid()->toString(),
            'proveedor' => $canal === CanalPagoEnum::EN_LINEA ? 'simulador' : null,
            'transaccion_externa' => $canal === CanalPagoEnum::EN_LINEA
                ? Str::uuid()->toString()
                : null,
        ];
    }

    public function aprobado(): static
    {
        return $this->state(fn (): array => ['estado' => EstadoPagoEnum::APROBADO]);
    }
}
