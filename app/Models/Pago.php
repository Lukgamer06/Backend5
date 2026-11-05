<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CanalPagoEnum;
use App\Enums\EstadoPagoEnum;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pedido_id
 * @property string $monto
 * @property CanalPagoEnum $canal
 * @property EstadoPagoEnum $estado
 * @property string $referencia_operacion
 * @property string $clave_idempotencia
 * @property string|null $proveedor
 * @property string|null $transaccion_externa
 */
class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'monto',
        'canal',
        'estado',
        'referencia_operacion',
        'clave_idempotencia',
        'proveedor',
        'transaccion_externa',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'canal' => CanalPagoEnum::class,
            'estado' => EstadoPagoEnum::class,
        ];
    }

    /** @return BelongsTo<Pedido, $this> */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
