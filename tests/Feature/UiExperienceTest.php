<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class UiExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_page_shows_a_friendly_404(): void
    {
        $this->get('/pagina-inexistente')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('No encontramos esa página')
            ->assertDontSee('Exception');
    }

    public function test_server_error_page_does_not_expose_exception_details(): void
    {
        config(['app.debug' => false]);

        Route::get('/__tests/server-error', static function (): never {
            throw new RuntimeException('internal-secret-detail');
        });

        $this->get('/__tests/server-error')
            ->assertStatus(500)
            ->assertSee('500')
            ->assertSee('Ocurrió un error inesperado')
            ->assertDontSee('internal-secret-detail');
    }

    public function test_flash_feedback_and_shared_form_ux_are_available_on_guest_layout(): void
    {
        $this->withSession([
            'status' => 'Acción completada.',
            'info' => 'Información de ayuda.',
        ])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Acción completada.')
            ->assertSee('Información de ayuda.')
            ->assertSee('document.addEventListener', false)
            ->assertSee('Procesando...', false);
    }

    public function test_empty_payment_history_has_a_clear_empty_state(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('No hay pagos registrados')
            ->assertSee('No hay pagos que coincidan con los filtros');
    }
}
