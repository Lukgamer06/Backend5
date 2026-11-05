<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EstadoMenuDiaEnum;
use App\Enums\ModalidadServicioEnum;
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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            fake()->seed(20261105);

            Usuario::factory()->administrador()->count(3)->create();
            $operadores = Usuario::factory()->operador()->count(5)->create();

            $clientes = Cliente::factory()->count(20)->create();
            $mesas = Mesa::factory()->count(30)->create();
            $productos = Producto::factory()->count(40)->create();

            $menuDia = MenuDia::factory()->publicado()->create([
                'fecha' => now()->toDateString(),
                'estado' => EstadoMenuDiaEnum::PUBLICADO,
            ]);
            $menuDia->productos()->attach($productos->modelKeys());

            $reservas = collect();
            for ($index = 0; $index < 60; $index++) {
                $cliente = $clientes->get($index % $clientes->count());
                $mesa = $mesas->get($index % $mesas->count());

                $reservas->push(Reserva::factory()->create([
                    'usuario_id' => $cliente->usuario_id,
                    'cliente_id' => $cliente->id,
                    'mesa_id' => $mesa->id,
                    'numero_personas' => fake()->numberBetween(1, $mesa->capacidad),
                ]));
            }

            $pedidos = collect();
            for ($index = 0; $index < 50; $index++) {
                $reserva = $reservas->get($index);
                $cliente = $clientes->firstWhere('id', $reserva->cliente_id);
                $esProgramado = $index >= 25;

                $pedidoFactory = Pedido::factory();
                $pedidoFactory = $esProgramado
                    ? $pedidoFactory->programado()
                    : $pedidoFactory->inmediato();

                $pedido = $pedidoFactory->create([
                    'usuario_id' => $cliente->usuario_id,
                    'cliente_id' => $cliente->id,
                    'reserva_id' => $esProgramado ? $reserva->id : null,
                    'mesa_id' => $reserva->mesa_id,
                    'modalidad_servicio' => ModalidadServicioEnum::EN_MESA,
                    'fecha_programada' => $esProgramado
                        ? $reserva->fecha_inicio
                        : null,
                    'total' => 0,
                ]);

                $total = 0.0;
                for ($detailIndex = 0; $detailIndex < 3; $detailIndex++) {
                    $producto = $productos->get(($index * 3 + $detailIndex) % $productos->count());
                    $cantidad = fake()->numberBetween(1, 3);
                    $precioUnitario = (float) $producto->precio;
                    $subtotal = round($cantidad * $precioUnitario, 2);

                    DetallePedido::factory()->create([
                        'pedido_id' => $pedido->id,
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $subtotal,
                    ]);

                    $total += $subtotal;
                }

                $pedido->update(['total' => round($total, 2)]);
                $pedidos->push($pedido);
            }

            for ($index = 0; $index < 25; $index++) {
                $pedido = $pedidos->get($index);

                Incidencia::factory()->create([
                    'usuario_reporta_id' => $operadores->random()->id,
                    'mesa_id' => $pedido->mesa_id,
                    'pedido_id' => $pedido->id,
                ]);
            }

            foreach ($pedidos as $pedido) {
                $primerMonto = round((float) $pedido->total / 2, 2);
                $segundoMonto = round((float) $pedido->total - $primerMonto, 2);

                Pago::factory()->create([
                    'pedido_id' => $pedido->id,
                    'monto' => $primerMonto,
                ]);
                Pago::factory()->create([
                    'pedido_id' => $pedido->id,
                    'monto' => $segundoMonto,
                ]);
            }
        });
    }
}
