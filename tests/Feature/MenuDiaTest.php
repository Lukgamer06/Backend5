<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EstadoMenuDiaEnum;
use App\Models\Cliente;
use App\Models\MenuDia;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MenuDiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('menus.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_create_edit_and_publish_a_menu_draft(): void
    {
        $operador = Usuario::factory()->operador()->create();
        $firstProduct = Producto::factory()->create(['nombre' => 'Sopa del día']);
        $secondProduct = Producto::factory()->create(['nombre' => 'Plato principal']);
        $date = Carbon::today()->addDays(2)->toDateString();

        $this->actingAs($operador)
            ->get(route('menus.create'))
            ->assertOk()
            ->assertSee($firstProduct->nombre)
            ->assertSee($secondProduct->nombre);

        $this->post(route('menus.store'), [
            'fecha' => $date,
            'productos' => [$firstProduct->id],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $menu = MenuDia::query()->sole();
        $this->assertSame(EstadoMenuDiaEnum::BORRADOR, $menu->estado);
        $this->assertSame([$firstProduct->id], $menu->productos->modelKeys());

        $updatedDate = Carbon::today()->addDays(3)->toDateString();
        $this->put(route('menus.update', $menu), [
            'fecha' => $updatedDate,
            'productos' => [$secondProduct->id],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('menus.edit', $menu));

        $this->assertSame($updatedDate, $menu->refresh()->fecha->toDateString());
        $this->assertSame(EstadoMenuDiaEnum::BORRADOR, $menu->estado);
        $this->assertDatabaseMissing('menu_dia_productos', [
            'menu_dia_id' => $menu->id,
            'producto_id' => $firstProduct->id,
        ]);
        $this->assertDatabaseHas('menu_dia_productos', [
            'menu_dia_id' => $menu->id,
            'producto_id' => $secondProduct->id,
        ]);

        $this->patch(route('menus.publish', $menu))
            ->assertRedirect(route('menus.show', $menu));

        $this->assertDatabaseHas('menus_dia', [
            'id' => $menu->id,
            'estado' => EstadoMenuDiaEnum::PUBLICADO->value,
        ]);
        $this->get(route('menus.show', $menu))
            ->assertOk()
            ->assertSee($secondProduct->nombre)
            ->assertSee('Publicado');
        $this->get(route('menus.edit', $menu))->assertForbidden();
        $this->patch(route('menus.publish', $menu))->assertForbidden();
    }

    public function test_menu_dates_are_unique_and_only_available_products_can_be_added(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $date = Carbon::today()->addDays(4)->toDateString();
        MenuDia::factory()->create(['fecha' => $date]);
        $unavailableProduct = Producto::factory()->noDisponible()->create();

        $this->actingAs($administrador)
            ->from(route('menus.create'))
            ->post(route('menus.store'), [
                'fecha' => $date,
                'productos' => [$unavailableProduct->id],
            ])
            ->assertRedirect(route('menus.create'))
            ->assertSessionHasErrors(['fecha', 'productos.0']);

        $this->assertDatabaseCount('menus_dia', 1);
        $this->assertDatabaseCount('menu_dia_productos', 0);
    }

    public function test_empty_draft_cannot_be_published(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $menu = MenuDia::factory()->create();

        $this->actingAs($administrador)
            ->from(route('menus.edit', $menu))
            ->patch(route('menus.publish', $menu))
            ->assertRedirect(route('menus.edit', $menu))
            ->assertSessionHasErrors('productos');

        $this->assertSame(EstadoMenuDiaEnum::BORRADOR, $menu->refresh()->estado);
    }

    public function test_customers_only_see_published_menus_and_cannot_manage_them(): void
    {
        $cliente = Cliente::factory()->create();
        $publishedMenu = MenuDia::factory()->publicado()->create([
            'fecha' => Carbon::today()->addDays(1)->toDateString(),
        ]);
        $draftMenu = MenuDia::factory()->create([
            'fecha' => Carbon::today()->addDays(2)->toDateString(),
        ]);
        $product = Producto::factory()->create(['nombre' => 'Menú publicado visible']);
        $publishedMenu->productos()->attach($product);

        $this->actingAs($cliente->usuario)
            ->get(route('menus.index', ['estado' => EstadoMenuDiaEnum::BORRADOR->value]))
            ->assertOk()
            ->assertSee($product->nombre)
            ->assertDontSee($draftMenu->fecha->format('d/m/Y'))
            ->assertDontSee('Crear borrador');

        $this->get(route('menus.show', $publishedMenu))->assertOk();
        $this->get(route('menus.show', $draftMenu))->assertForbidden();
        $this->get(route('menus.create'))->assertForbidden();
        $this->patch(route('menus.publish', $publishedMenu))->assertForbidden();
    }

    public function test_an_inactive_product_already_on_a_draft_can_be_retained_or_removed(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $menu = MenuDia::factory()->create();
        $product = Producto::factory()->create();
        $menu->productos()->attach($product);
        $product->update(['disponible' => false]);

        $this->actingAs($administrador)
            ->get(route('menus.edit', $menu))
            ->assertOk()
            ->assertSee($product->nombre)
            ->assertSee('Ya no disponible');

        $this->put(route('menus.update', $menu), [
            'fecha' => $menu->fecha->format('Y-m-d'),
            'productos' => [$product->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('menu_dia_productos', [
            'menu_dia_id' => $menu->id,
            'producto_id' => $product->id,
        ]);

        $this->put(route('menus.update', $menu), [
            'fecha' => $menu->fecha->format('Y-m-d'),
            'productos' => [],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('menu_dia_productos', [
            'menu_dia_id' => $menu->id,
            'producto_id' => $product->id,
        ]);
    }
}
