<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoMesaEnum;
use App\Enums\EstadoOperativoMesaEnum;
use Database\Factories\MesaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $numero
 * @property int $capacidad
 * @property EstadoMesaEnum $estado
 * @property EstadoOperativoMesaEnum $estado_operativo
 */
class Mesa extends Model
{
    /** @use HasFactory<MesaFactory> */
    use HasFactory;

    protected $fillable = [
        'numero',
        'capacidad',
        'estado',
        'estado_operativo',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'capacidad' => 'integer',
            'estado' => EstadoMesaEnum::class,
            'estado_operativo' => EstadoOperativoMesaEnum::class,
        ];
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
        return $this->hasMany(Incidencia::class);
    }
}
