<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoReservaEnum;
use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $usuario_id
 * @property int $cliente_id
 * @property int $mesa_id
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property int $numero_personas
 * @property EstadoReservaEnum $estado
 * @property string|null $observaciones
 */
class Reserva extends Model
{
    /** @use HasFactory<ReservaFactory> */
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'cliente_id',
        'mesa_id',
        'fecha_inicio',
        'fecha_fin',
        'numero_personas',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'numero_personas' => 'integer',
            'estado' => EstadoReservaEnum::class,
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

    /** @return BelongsTo<Mesa, $this> */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    /** @return HasMany<Pedido, $this> */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }
}
