<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\EstadoMenuDiaEnum;
use App\Models\MenuDia;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CatalogAndMenuApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_only_available_products_with_pagination(): void
    {
        $customer = Usuario::factory()->cliente()->create();
        Producto::factory()->create(['nombre' => 'Producto disponible A']);
        Producto::factory()->create(['nombre' => 'Producto disponible B']);
        Producto::factory()->noDisponible()->create(['nombre' => 'Producto inactivo']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/productos?search=disponible&per_page=1')
            ->assertOk()
            ->assertJsonPath('message', 'Productos consultados correctamente.')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonStructure(['data', 'links', 'meta', 'message'])
            ->assertJsonMissing([
                'nombre' => 'Producto inactivo',
            ]);
    }

    public function test_only_administrators_can_filter_or_view_unavailable_products(): void
    {
        $customer = Usuario::factory()->cliente()->create();
        $inactiveProduct = Producto::factory()->noDisponible()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/productos?disponible=0')
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.disponible.0', 'The disponible field is prohibited.');

        $this->getJson("/api/v1/productos/{$inactiveProduct->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');

        $administrator = Usuario::factory()->administrador()->create();

        $this->actingAs($administrator, 'sanctum')
            ->getJson('/api/v1/productos?disponible=0')
            ->assertOk()
            ->assertJsonPath('data.0.disponible', false);

        $this->getJson("/api/v1/productos/{$inactiveProduct->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $inactiveProduct->id)
            ->assertJsonPath('data.disponible', false);
    }

    public function test_authenticated_user_can_read_an_available_product(): void
    {
        $customer = Usuario::factory()->cliente()->create();
        $product = Producto::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/productos/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonStructure([
                'data' => ['id', 'nombre', 'descripcion', 'precio', 'disponible'],
                'message',
                'meta',
            ]);
    }

    public function test_actual_menu_returns_only_a_published_menu_for_today(): void
    {
        $customer = Usuario::factory()->cliente()->create();
        $menu = MenuDia::factory()->publicado()->create([
            'fecha' => Carbon::today()->toDateString(),
        ]);
        $product = Producto::factory()->create();
        $menu->productos()->attach($product);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/menus-dia/actual')
            ->assertOk()
            ->assertJsonPath('message', 'Menú del día consultado correctamente.')
            ->assertJsonPath('data.id', $menu->id)
            ->assertJsonPath('data.estado', EstadoMenuDiaEnum::PUBLICADO->value)
            ->assertJsonPath('data.productos.0.id', $product->id);
    }

    public function test_actual_menu_returns_404_when_no_menu_is_published_for_today(): void
    {
        $customer = Usuario::factory()->cliente()->create();
        MenuDia::factory()->create(['fecha' => Carbon::today()->toDateString()]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/menus-dia/actual')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }

    public function test_staff_can_create_a_menu_draft_and_customers_cannot_manage_menus(): void
    {
        $operator = Usuario::factory()->operador()->create();
        $customer = Usuario::factory()->cliente()->create();
        $product = Producto::factory()->create();
        $date = Carbon::today()->addDays(3)->toDateString();

        $this->actingAs($operator, 'sanctum')
            ->postJson('/api/v1/menus-dia', [
                'fecha' => $date,
                'productos' => [$product->id],
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Menú guardado como borrador.')
            ->assertJsonPath('data.estado', EstadoMenuDiaEnum::BORRADOR->value)
            ->assertJsonPath('data.productos.0.id', $product->id)
            ->assertJsonStructure(['data' => ['id', 'fecha', 'estado', 'productos'], 'message', 'meta']);

        $menu = MenuDia::query()->whereDate('fecha', $date)->firstOrFail();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/menus-dia', [
                'fecha' => Carbon::today()->addDays(4)->toDateString(),
                'productos' => [$product->id],
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Acceso denegado.');

        $this->patchJson("/api/v1/menus-dia/{$menu->id}", [
            'fecha' => Carbon::today()->addDays(5)->toDateString(),
        ])->assertForbidden();
    }

    public function test_menu_patch_can_preserve_inactive_products_already_in_the_draft(): void
    {
        $operator = Usuario::factory()->operador()->create();
        $menu = MenuDia::factory()->create([
            'fecha' => Carbon::today()->addDays(6)->toDateString(),
        ]);
        $inactiveProduct = Producto::factory()->create();
        $menu->productos()->attach($inactiveProduct);
        $inactiveProduct->update(['disponible' => false]);
        $newDate = Carbon::today()->addDays(7)->toDateString();

        $this->actingAs($operator, 'sanctum')
            ->patchJson("/api/v1/menus-dia/{$menu->id}", [
                'fecha' => $newDate,
            ])
            ->assertOk()
            ->assertJsonPath('data.fecha', $newDate)
            ->assertJsonPath('data.productos.0.id', $inactiveProduct->id)
            ->assertJsonPath('message', 'Borrador del menú actualizado correctamente.');

        $this->assertDatabaseHas('menu_dia_productos', [
            'menu_dia_id' => $menu->id,
            'producto_id' => $inactiveProduct->id,
        ]);
    }

    public function test_menu_patch_allows_its_own_date_but_rejects_another_menus_date(): void
    {
        $operator = Usuario::factory()->operador()->create();
        $menu = MenuDia::factory()->create([
            'fecha' => Carbon::today()->addDays(8)->toDateString(),
        ]);
        $anotherMenu = MenuDia::factory()->create([
            'fecha' => Carbon::today()->addDays(9)->toDateString(),
        ]);

        $this->actingAs($operator, 'sanctum')
            ->patchJson("/api/v1/menus-dia/{$menu->id}", [
                'fecha' => $menu->fecha->toDateString(),
            ])
            ->assertOk();

        $this->patchJson("/api/v1/menus-dia/{$menu->id}", [
            'fecha' => $anotherMenu->fecha->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.fecha.0', 'Ya existe un menú para la fecha seleccionada.');
    }

    public function test_menu_patch_rejects_an_inactive_product_not_already_in_the_draft(): void
    {
        $operator = Usuario::factory()->operador()->create();
        $menu = MenuDia::factory()->create();
        $inactiveProduct = Producto::factory()->noDisponible()->create();

        $this->actingAs($operator, 'sanctum')
            ->patchJson("/api/v1/menus-dia/{$menu->id}", [
                'productos' => [$inactiveProduct->id],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Los datos enviados no son válidos.')
            ->assertJsonPath(
                'meta.errors.productos.0',
                'Selecciona productos disponibles o que ya pertenezcan a este menú.',
            );
    }

    public function test_staff_can_publish_a_non_empty_draft_and_empty_draft_is_rejected(): void
    {
        $administrator = Usuario::factory()->administrador()->create();
        $emptyDraft = MenuDia::factory()->create();
        $draft = MenuDia::factory()->create();
        $draft->productos()->attach(Producto::factory()->create());

        $this->actingAs($administrator, 'sanctum')
            ->postJson("/api/v1/menus-dia/{$emptyDraft->id}/publicacion")
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.productos.0', 'Agrega al menos un producto antes de publicar el menú.');

        $this->postJson("/api/v1/menus-dia/{$draft->id}/publicacion")
            ->assertOk()
            ->assertJsonPath('message', 'Menú publicado correctamente.')
            ->assertJsonPath('data.estado', EstadoMenuDiaEnum::PUBLICADO->value);
    }

    public function test_menu_creation_rejects_duplicate_dates_and_invalid_products(): void
    {
        $administrator = Usuario::factory()->administrador()->create();
        $date = Carbon::today()->addDays(10)->toDateString();
        MenuDia::factory()->create(['fecha' => $date]);
        $product = Producto::factory()->create();

        $this->actingAs($administrator, 'sanctum')
            ->postJson('/api/v1/menus-dia', [
                'fecha' => $date,
                'productos' => [$product->id],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.fecha.0', 'Ya existe un menú para la fecha seleccionada.');
    }

    public function test_catalog_and_menu_api_require_authentication(): void
    {
        $this->getJson('/api/v1/productos')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');

        $this->getJson('/api/v1/menus-dia/actual')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');
    }
}
