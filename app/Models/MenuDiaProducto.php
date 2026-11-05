<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MenuDiaProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $menu_dia_id
 * @property int $producto_id
 */
class MenuDiaProducto extends Pivot
{
    /** @use HasFactory<MenuDiaProductoFactory> */
    use HasFactory;

    protected $table = 'menu_dia_productos';

    public $incrementing = true;

    protected $fillable = [
        'menu_dia_id',
        'producto_id',
    ];

    protected function casts(): array
    {
        return [
            'menu_dia_id' => 'integer',
            'producto_id' => 'integer',
        ];
    }

    /** @return BelongsTo<MenuDia, $this> */
    public function menuDia(): BelongsTo
    {
        return $this->belongsTo(MenuDia::class);
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
