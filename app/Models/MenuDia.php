<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoMenuDiaEnum;
use Database\Factories\MenuDiaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $fecha
 * @property EstadoMenuDiaEnum $estado
 */
class MenuDia extends Model
{
    /** @use HasFactory<MenuDiaFactory> */
    use HasFactory;

    protected $table = 'menus_dia';

    protected $fillable = [
        'fecha',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoMenuDiaEnum::class,
        ];
    }

    /** @return BelongsToMany<Producto, $this, MenuDiaProducto> */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'menu_dia_productos')
            ->using(MenuDiaProducto::class)
            ->withTimestamps();
    }
}
