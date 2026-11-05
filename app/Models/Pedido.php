<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoPedidoEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\TipoAtencionEnum;
use Database\Factories\PedidoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $usuario_id
 * @property int $cliente_id
 * @property int|null $reserva_id
 * @property int|null $mesa_id
 * @property TipoAtencionEnum $tipo_atencion
 * @property ModalidadServicioEnum $modalidad_servicio
 * @property EstadoPedidoEnum $estado
 * @property Carbon|null $fecha_programada
 * @property string $total
 */
class Pedido extends Model
{
    /** @use HasFactory<PedidoFactory> */
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'cliente_id',
        'reserva_id',
        'mesa_id',
        'tipo_atencion',
        'modalidad_servicio',
        'estado',
        'fecha_programada',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'tipo_atencion' => TipoAtencionEnum::class,
            'modalidad_servicio' => ModalidadServicioEnum::class,
            'estado' => EstadoPedidoEnum::class,
            'fecha_programada' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    /** @return BelongsTo<Mesa, $this> */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    /** @return HasMany<DetallePedido, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }

    /** @return HasMany<Pago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}
