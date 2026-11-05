<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoPedidoEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition(): array
    {
        $tipoAtencion = fake()->randomElement(TipoAtencionEnum::cases());
        $modalidad = fake()->randomElement(ModalidadServicioEnum::cases());

        return [
            'usuario_id' => Usuario::factory(),
            'cliente_id' => Cliente::factory(),
            'reserva_id' => null,
            'mesa_id' => $modalidad === ModalidadServicioEnum::EN_MESA
                ? Mesa::factory()
                : null,
            'tipo_atencion' => $tipoAtencion,
            'modalidad_servicio' => $modalidad,
            'estado' => EstadoPedidoEnum::REGISTRADO,
            'fecha_programada' => $tipoAtencion === TipoAtencionEnum::PROGRAMADO
                ? Carbon::instance(fake()->dateTimeBetween('+1 hour', '+30 days'))
                : null,
            'total' => fake()->randomFloat(2, 1, 500000),
        ];
    }

    public function programado(): static
    {
        return $this->state(fn (): array => [
            'tipo_atencion' => TipoAtencionEnum::PROGRAMADO,
            'fecha_programada' => Carbon::instance(fake()->dateTimeBetween('+1 hour', '+30 days')),
        ]);
    }

    public function inmediato(): static
    {
        return $this->state(fn (): array => [
            'tipo_atencion' => TipoAtencionEnum::INMEDIATO,
            'fecha_programada' => null,
        ]);
    }

    public function sinMesa(): static
    {
        return $this->state(fn (): array => [
            'modalidad_servicio' => ModalidadServicioEnum::SIN_MESA,
            'mesa_id' => null,
        ]);
    }
}
