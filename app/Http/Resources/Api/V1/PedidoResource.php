<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\DetallePedido;
use Illuminate\Http\Request;

class PedidoResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'cliente_id' => $this->cliente_id,
            'reserva_id' => $this->reserva_id,
            'mesa_id' => $this->mesa_id,
            'tipo_atencion' => $this->tipo_atencion->value,
            'modalidad_servicio' => $this->modalidad_servicio->value,
            'estado' => $this->estado->value,
            'fecha_programada' => $this->fecha_programada?->toIso8601String(),
            'total' => $this->total,
            'cliente' => ClienteResource::make($this->whenLoaded('cliente')),
            'detalles' => $this->whenLoaded(
                'detalles',
                fn () => $this->detalles->map(fn (DetallePedido $detalle): array => [
                    'id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal,
                    'observaciones' => $detalle->observaciones,
                    'producto' => $detalle->relationLoaded('producto')
                        ? ProductoResource::make($detalle->producto)
                        : null,
                ])->values(),
            ),
            'pagos' => PagoResource::collection($this->whenLoaded('pagos')),
        ];
    }
}
