<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_administrator_can_create_edit_activate_and_deactivate_products(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Nuevo producto');

        $this->post(route('products.store'), [
            'nombre' => 'Café especial',
            'descripcion' => 'Café de origen local.',
            'precio' => '8.50',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $producto = Producto::query()->sole();
        $this->assertSame('8.50', $producto->precio);
        $this->assertTrue($producto->disponible);

        $this->get(route('products.edit', $producto))
            ->assertOk()
            ->assertSee('Café especial');

        $this->put(route('products.update', $producto), [
            'nombre' => 'Café premium',
            'descripcion' => 'Nueva descripción.',
            'precio' => '9.25',
            'disponible' => false,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre' => 'Café premium',
            'precio' => '9.25',
            'disponible' => true,
        ]);

        $this->patch(route('products.deactivate', $producto))
            ->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'disponible' => false,
        ]);

        $this->patch(route('products.activate', $producto))
            ->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'disponible' => true,
        ]);
    }

    public function test_non_administrator_can_only_browse_available_products(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $available = Producto::factory()->create(['nombre' => 'Producto disponible']);
        $unavailable = Producto::factory()->noDisponible()->create([
            'nombre' => 'Producto inactivo privado',
        ]);

        $this->actingAs($operador)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee($available->nombre)
            ->assertDontSee($unavailable->nombre)
            ->assertDontSee('Nuevo producto');

        $this->get(route('products.create'))
            ->assertForbidden();
        $this->get(route('products.edit', $available))
            ->assertForbidden();
        $this->patch(route('products.deactivate', $available))
            ->assertForbidden();
        $this->patch(route('products.activate', $unavailable))
            ->assertForbidden();
    }

    public function test_client_can_browse_active_products_but_cannot_manage_them(): void
    {
        $cliente = Cliente::factory()->create();
        $available = Producto::factory()->create(['nombre' => 'Catálogo cliente visible']);
        $unavailable = Producto::factory()->noDisponible()->create([
            'nombre' => 'Catálogo cliente oculto',
        ]);

        $this->actingAs($cliente->usuario)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee($available->nombre)
            ->assertDontSee($unavailable->nombre)
            ->assertDontSee('Nuevo producto');
    }

    public function test_product_search_availability_filter_and_pagination_work(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $target = Producto::factory()->create([
            'nombre' => 'Infusión azul',
            'disponible' => false,
        ]);
        Producto::factory()->create([
            'nombre' => 'Producto activo diferente',
            'disponible' => true,
        ]);

        $this->actingAs($administrador)
            ->get(route('products.index', [
                'search' => 'Infusión',
                'disponible' => '0',
            ]))
            ->assertOk()
            ->assertSee($target->nombre)
            ->assertViewHas(
                'products',
                fn ($products): bool => $products->total() === 1
                    && $products->contains('id', $target->id),
            );

        Producto::factory()->count(15)->create();
        $this->get(route('products.index'))
            ->assertViewHas(
                'products',
                fn ($products): bool => $products->total() === 17
                    && $products->perPage() === 15
                    && $products->count() === 15,
            );
    }

    public function test_invalid_product_data_is_rejected_and_availability_is_not_mass_assignable(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'nombre' => '',
                'precio' => '-1.000',
                'disponible' => false,
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['nombre', 'precio']);

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_deactivating_product_preserves_existing_order_details(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create();
        $pedido = Pedido::factory()->create([
            'usuario_id' => $administrador->id,
            'cliente_id' => $cliente->id,
        ]);
        $detalle = DetallePedido::factory()->create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
        ]);

        $this->actingAs($administrador)
            ->patch(route('products.deactivate', $producto))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('detalle_pedidos', [
            'id' => $detalle->id,
            'producto_id' => $producto->id,
        ]);
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'disponible' => false,
        ]);
    }
}
