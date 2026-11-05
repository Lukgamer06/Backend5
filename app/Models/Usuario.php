<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolUsuarioEnum;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $nombres
 * @property string $apellidos
 * @property string $correo
 * @property string|null $password
 * @property string|null $telefono
 * @property RolUsuarioEnum $rol
 */
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasApiTokens;

    use HasFactory;

    protected $fillable = [
        'nombres',
        'apellidos',
        'correo',
        'password',
        'telefono',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'rol' => RolUsuarioEnum::class,
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->getAttribute($this->getAuthPasswordName());
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
