<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $usuario_id
 * @property string $nombres
 * @property string $apellidos
 * @property string|null $correo
 * @property string|null $telefono
 */
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'nombres',
        'apellidos',
        'correo',
        'telefono',
    ];

    protected function casts(): array
    {
        return [];
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    /** @return HasMany<Pedido, $this> */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }
}
