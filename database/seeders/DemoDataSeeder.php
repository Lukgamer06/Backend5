<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CanalPagoEnum;
use App\Enums\EstadoIncidenciaEnum;
use App\Enums\EstadoMenuDiaEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoPedidoEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Enums\RolUsuarioEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\Incidencia;
use App\Models\MenuDia;
use App\Models\Mesa;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $clienteUsuario = Usuario::query()
                ->where('correo', 'example@unab.edu.co')
                ->where('rol', RolUsuarioEnum::CLIENTE)
                ->first();
            $operador = Usuario::query()
                ->where('correo', 'operador@unab.edu.co')
                ->where('rol', RolUsuarioEnum::OPERADOR)
                ->first();

            if ($clienteUsuario === null || $operador === null) {
                throw new RuntimeException(
                    'Create the three demo accounts before seeding demo domain data.',
                );
            }

            $cliente = Cliente::query()->firstOrCreate(
                ['usuario_id' => $clienteUsuario->id],
                [
                    'nombres' => $clienteUsuario->nombres,
                    'apellidos' => $clienteUsuario->apellidos,
                    'correo' => $clienteUsuario->correo,
                    'telefono' => $clienteUsuario->telefono,
                ],
            );

            $mesas = collect([
                ['numero' => 101, 'capacidad' => 2],
                ['numero' => 102, 'capacidad' => 4],
                ['numero' => 103, 'capacidad' => 4],
                ['numero' => 104, 'capacidad' => 6],
                ['numero' => 105, 'capacidad' => 8],
            ])->map(
                fn (array $mesa): Mesa => Mesa::query()->firstOrCreate(
                    ['numero' => $mesa['numero']],
                    ['capacidad' => $mesa['capacidad']],
                ),
            );

            $productos = collect([
                ['nombre' => 'Carne asada', 'descripcion' => 'Carne asada con acompañamiento.', 'precio' => 15000],
                ['nombre' => 'Pollo a la plancha', 'descripcion' => 'Pechuga a la plancha con ensalada.', 'precio' => 18000],
                ['nombre' => 'Sopa de verduras', 'descripcion' => 'Sopa casera de verduras de temporada.', 'precio' => 9000],
                ['nombre' => 'Arepa con queso', 'descripcion' => 'Arepa de maiz con queso.', 'precio' => 7000],
                ['nombre' => 'Jugo natural', 'descripcion' => 'Jugo natural preparado al momento.', 'precio' => 6000],
            ])->map(
                fn (array $producto): Producto => Producto::query()->firstOrCreate(
                    ['nombre' => $producto['nombre']],
                    [
                        'descripcion' => $producto['descripcion'],
                        'precio' => $producto['precio'],
                        'disponible' => true,
                    ],
                ),
            );

            $menu = MenuDia::query()->whereDate('fecha', today())->first()
                ?? MenuDia::query()->create([
                    'fecha' => today(),
                    'estado' => EstadoMenuDiaEnum::PUBLICADO,
                ]);
            $menu->productos()->syncWithoutDetaching($productos->pluck('id')->all());

            $reservas = collect();
            foreach ($mesas->take(3)->values() as $index => $mesa) {
                $observaciones = sprintf('Demo HostalConnect - reserva %02d', $index + 1);
                $fechaInicio = now()->addDays($index + 1)->setTime(18 + $index, 0);

                $reservas->push(Reserva::query()->firstOrCreate(
                    [
                        'cliente_id' => $cliente->id,
                        'mesa_id' => $mesa->id,
                        'observaciones' => $observaciones,
                    ],
                    [
                        'usuario_id' => $clienteUsuario->id,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaInicio->copy()->addHour(),
                        'numero_personas' => min(2, $mesa->capacidad),
                        'estado' => EstadoReservaEnum::CONFIRMADA,
                    ],
                ));
            }

            foreach ($reservas as $index => $reserva) {
                $pedido = Pedido::query()->firstOrCreate(
                    ['reserva_id' => $reserva->id],
                    [
                        'usuario_id' => $clienteUsuario->id,
                        'cliente_id' => $cliente->id,
                        'mesa_id' => $reserva->mesa_id,
                        'tipo_atencion' => TipoAtencionEnum::PROGRAMADO,
                        'modalidad_servicio' => ModalidadServicioEnum::EN_MESA,
                        'estado' => [
                            EstadoPedidoEnum::REGISTRADO,
                            EstadoPedidoEnum::EN_PREPARACION,
                            EstadoPedidoEnum::LISTO,
                        ][$index],
                        'fecha_programada' => $reserva->fecha_inicio,
                        'total' => 0,
                    ],
                );

                $primerProducto = $productos->get($index);
                $segundoProducto = $productos->get(($index + 1) % $productos->count());
                foreach ([$primerProducto, $segundoProducto] as $producto) {
                    $cantidad = $producto->nombre === 'Jugo natural' ? 2 : 1;
                    DetallePedido::query()->firstOrCreate(
                        [
                            'pedido_id' => $pedido->id,
                            'producto_id' => $producto->id,
                        ],
                        [
                            'cantidad' => $cantidad,
                            'precio_unitario' => $producto->precio,
                            'subtotal' => $cantidad * (float) $producto->precio,
                        ],
                    );
                }

                $detalles = $pedido->detalles()->get();
                $pedido->update([
                    'total' => round($detalles->sum(
                        fn (DetallePedido $detalle): float => $detalle->cantidad
                            * (float) $detalle->precio_unitario,
                    ), 2),
                ]);

                $referencia = sprintf('DEMO-AZURE-RESERVA-%02d', $index + 1);
                $pago = Pago::query()->firstOrCreate(
                    ['referencia_operacion' => $referencia],
                    [
                        'pedido_id' => $pedido->id,
                        'monto' => $pedido->total,
                        'canal' => CanalPagoEnum::PRESENCIAL,
                        'estado' => EstadoPagoEnum::PENDIENTE,
                        'clave_idempotencia' => sprintf('DEMO-AZURE-IDEMPOTENCIA-%02d', $index + 1),
                    ],
                );

                if ($pago->pedido_id !== $pedido->id) {
                    throw new RuntimeException(
                        "Demo payment reference {$referencia} is already linked to another order.",
                    );
                }
            }

            foreach ($reservas->take(2)->values() as $index => $reserva) {
                $pedido = $reserva->pedidos()->firstOrFail();
                $titulo = sprintf('Demo HostalConnect - incidencia %02d', $index + 1);

                Incidencia::query()->firstOrCreate(
                    [
                        'usuario_reporta_id' => $operador->id,
                        'mesa_id' => $reserva->mesa_id,
                        'pedido_id' => $pedido->id,
                        'titulo' => $titulo,
                    ],
                    [
                        'descripcion' => 'Incidencia de demostracion para revisar el flujo de atencion.',
                        'prioridad' => $index === 0
                            ? PrioridadIncidenciaEnum::MEDIA
                            : PrioridadIncidenciaEnum::BAJA,
                        'estado' => EstadoIncidenciaEnum::ABIERTA,
                    ],
                );
            }
        });
    }
}
