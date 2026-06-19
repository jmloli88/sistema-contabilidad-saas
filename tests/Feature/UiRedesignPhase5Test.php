<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiRedesignPhase5Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = Empresa::factory()->create(['nombre' => 'Phase5 Test Empresa']);
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'role' => 'administrador',
        ]);
        $this->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_phase5_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
    }

    // ──────────────────────────────────────────────
    // Task 5.1: Flash Messages → Toasts
    // ──────────────────────────────────────────────

    public function test_toast_shows_success_message_when_session_has_success(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['success' => 'Operación exitosa'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('check_circle');
        $response->assertSee('Operación exitosa');
        $response->assertSee('bg-green-50');
    }

    public function test_toast_shows_error_message_when_session_has_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['error' => 'Ocurrió un error'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('error');
        $response->assertSee('Ocurrió un error');
        $response->assertSee('bg-red-50');
    }

    public function test_toast_shows_warning_message_when_session_has_warning(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['warning' => 'Advertencia'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('warning');
        $response->assertSee('Advertencia');
        $response->assertSee('bg-amber-50');
    }

    public function test_toast_not_shown_when_no_flash_messages(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('check_circle');
        $response->assertDontSee('bg-green-50');
    }

    // ──────────────────────────────────────────────
    // Task 5.2: Empty State Component
    // ──────────────────────────────────────────────

    public function test_empty_state_renders_with_default_props(): void
    {
        $view = $this->blade('<x-empty-state />');

        $view->assertSee('inbox');
        $view->assertSee('No hay datos');
    }

    public function test_empty_state_renders_custom_title_and_icon(): void
    {
        $view = $this->blade(
            '<x-empty-state icon="search_off" title="Sin resultados" description="No se encontraron elementos." />'
        );

        $view->assertSee('search_off');
        $view->assertSee('Sin resultados');
        $view->assertSee('No se encontraron elementos.');
    }

    public function test_empty_state_renders_action_link_when_provided(): void
    {
        $view = $this->blade(
            '<x-empty-state icon="add" title="Vacío" description="Crea uno nuevo." action="/create" actionLabel="Crear" />'
        );

        $view->assertSee('Crear');
        $view->assertSee('/create');
        $view->assertSee('bg-indigo-600');
    }

    // ──────────────────────────────────────────────
    // Task 5.3: Confirmation Modal Component
    // ──────────────────────────────────────────────

    public function test_confirm_modal_renders_with_default_props(): void
    {
        $view = $this->blade(
            '<x-confirm-modal message="¿Eliminar este elemento?" action="/delete" />'
        );

        $view->assertSee('¿Estás seguro?');
        $view->assertSee('¿Eliminar este elemento?');
        $view->assertSee('Cancelar');
        $view->assertSee('/delete');
    }

    public function test_confirm_modal_renders_custom_title_and_method(): void
    {
        $view = $this->blade(
            '<x-confirm-modal title="Confirmar eliminación" message="Esta acción no se puede deshacer." action="/users/1" method="DELETE" />'
        );

        $view->assertSee('Confirmar eliminación');
        $view->assertSee('Esta acción no se puede deshacer.');
        $view->assertSee('DELETE');
    }

    // ──────────────────────────────────────────────
    // Task 5.4: Skeleton Loaders CSS
    // ──────────────────────────────────────────────

    public function test_skeleton_css_classes_exist_in_app_css(): void
    {
        $path = resource_path('css/app.css');
        $content = file_get_contents($path);

        $this->assertStringContainsString('@keyframes shimmer', $content,
            'app.css must contain @keyframes shimmer animation for skeleton loaders.'
        );
        $this->assertStringContainsString('.skeleton', $content,
            'app.css must contain .skeleton class for skeleton loaders.'
        );
        $this->assertStringContainsString('shimmer 1.5s ease-in-out infinite', $content,
            'app.css skeleton must use shimmer animation with 1.5s infinite.'
        );
    }

    // ──────────────────────────────────────────────
    // Task 5.5: Smooth Scroll & Back to Top
    // ──────────────────────────────────────────────

    public function test_back_to_top_button_is_present_in_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('arrow_upward');
        $response->assertSee('scrollTo');
        $response->assertSee('bg-indigo-600');
        $response->assertSee('rounded-full');
    }
}
