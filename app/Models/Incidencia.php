<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use Database\Factories\IncidenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $usuario_reporta_id
 * @property int|null $mesa_id
 * @property int|null $pedido_id
 * @property string $titulo
 * @property string $descripcion
 * @property PrioridadIncidenciaEnum $prioridad
 * @property EstadoIncidenciaEnum $estado
 */
class Incidencia extends Model
{
    /** @use HasFactory<IncidenciaFactory> */
    use HasFactory;

    protected $fillable = [
        'usuario_reporta_id',
        'mesa_id',
        'pedido_id',
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'prioridad' => PrioridadIncidenciaEnum::class,
            'estado' => EstadoIncidenciaEnum::class,
        ];
    }

    /** @return BelongsTo<Mesa, $this> */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    /** @return BelongsTo<Pedido, $this> */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_reporta_id');
    }
}
