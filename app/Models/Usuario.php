<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolUsuarioEnum;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $nombres
 * @property string $apellidos
 * @property string $correo
 * @property string|null $telefono
 * @property RolUsuarioEnum $rol
 */
class Usuario extends Model
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory;

    protected $fillable = [
        'nombres',
        'apellidos',
        'correo',
        'telefono',
        'rol',
    ];

    protected function casts(): array
    {
        return [
            'rol' => RolUsuarioEnum::class,
        ];
    }

    /** @return HasOne<Cliente, $this> */
    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'usuario_id');
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

    /** @return HasMany<Incidencia, $this> */
    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class, 'usuario_reporta_id');
    }
}
