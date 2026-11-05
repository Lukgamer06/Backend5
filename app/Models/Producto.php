<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $precio
 * @property bool $disponible
 */
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'disponible',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'disponible' => 'boolean',
        ];
    }

    /** @return HasMany<DetallePedido, $this> */
    public function detallesPedido(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'producto_id');
    }

    /** @return BelongsToMany<MenuDia, $this, MenuDiaProducto> */
    public function menusDia(): BelongsToMany
    {
        return $this->belongsToMany(MenuDia::class, 'menu_dia_productos')
            ->using(MenuDiaProducto::class)
            ->withTimestamps();
    }
}
