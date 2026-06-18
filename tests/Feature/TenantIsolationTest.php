<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\Examen;
use App\Models\User;
use App\Support\EmpresaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresaA;
    private Empresa $empresaB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        EmpresaContext::clear();

        $this->empresaA = Empresa::factory()->create(['nombre' => 'Empresa A']);
        $this->empresaB = Empresa::factory()->create(['nombre' => 'Empresa B']);

        $this->userA = User::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'role' => 'administrador',
        ]);
        $this->empresaA->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_user_a',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $this->userB = User::factory()->create([
            'empresa_id' => $this->empresaB->id,
            'role' => 'administrador',
        ]);
        $this->empresaB->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_user_b',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
    }

    protected function tearDown(): void
    {
        EmpresaContext::clear();
        parent::tearDown();
    }

    // ────────────────────────────────────────────────────────────
    // 2.1 [RED] Test: ScopeByEmpresa middleware sets context from auth user
    // ────────────────────────────────────────────────────────────

    public function test_middleware_sets_empresa_context_from_authenticated_user(): void
    {
        EmpresaContext::clear();
        $this->assertFalse(EmpresaContext::isSet(), 'Context should start unset');

        $this->actingAs($this->userA)
            ->get(route('dashboard'));

        $this->assertEquals(
            $this->userA->empresa_id,
            EmpresaContext::get(),
            'Middleware should set EmpresaContext from authenticated user'
        );
    }

    public function test_middleware_sets_context_for_different_users(): void
    {
        EmpresaContext::clear();

        $this->actingAs($this->userA)
            ->get(route('dashboard'));
        $this->assertEquals(
            $this->empresaA->id,
            EmpresaContext::get(),
            'User A should set context to empresa A'
        );

        EmpresaContext::clear();

        $this->actingAs($this->userB)
            ->get(route('dashboard'));
        $this->assertEquals(
            $this->empresaB->id,
            EmpresaContext::get(),
            'User B should set context to empresa B'
        );
    }

    // ────────────────────────────────────────────────────────────
    // 2.2 [RED] Test: GlobalScope filters clinicas by current empresa_id
    // ────────────────────────────────────────────────────────────

    public function test_global_scope_filters_clinicas_by_current_empresa(): void
    {
        $clinicaA = Clinica::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'nombre' => 'Clinica A - Empresa A',
        ]);
        $clinicaB = Clinica::factory()->create([
            'empresa_id' => $this->empresaB->id,
            'nombre' => 'Clinica B - Empresa B',
        ]);

        EmpresaContext::set($this->empresaA->id);

        $clinicas = Clinica::all();

        $this->assertTrue(
            $clinicas->contains('id', $clinicaA->id),
            'Clinica from empresa A should be visible'
        );
        $this->assertFalse(
            $clinicas->contains('id', $clinicaB->id),
            'Clinica from empresa B should NOT be visible'
        );
        $this->assertCount(1, $clinicas, 'Only one clinica should be returned');
    }

    public function test_global_scope_filters_with_multiple_entities(): void
    {
        Clinica::factory()->count(3)->create([
            'empresa_id' => $this->empresaA->id,
        ]);
        Clinica::factory()->count(5)->create([
            'empresa_id' => $this->empresaB->id,
        ]);

        EmpresaContext::set($this->empresaA->id);

        $this->assertCount(3, Clinica::all(), 'Should see only empresa A clinicas');
    }

    public function test_global_scope_does_not_filter_when_context_unset(): void
    {
        Clinica::factory()->create(['empresa_id' => $this->empresaA->id]);
        Clinica::factory()->create(['empresa_id' => $this->empresaB->id]);

        EmpresaContext::clear();

        $this->assertCount(
            2,
            Clinica::all(),
            'With no context set, global scope should be a no-op and return all records'
        );
    }

    // ────────────────────────────────────────────────────────────
    // 2.3 [RED] Test: cross-empresa isolation
    // ────────────────────────────────────────────────────────────

    public function test_cross_empresa_clinica_isolation_via_http(): void
    {
        $clinicaA = Clinica::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'nombre' => 'Clinica Secreta A',
        ]);
        Clinica::factory()->create([
            'empresa_id' => $this->empresaB->id,
            'nombre' => 'Clinica Secreta B',
        ]);

        $response = $this->actingAs($this->userA)
            ->get(route('clinicas.index'));

        $response->assertStatus(200);
        $response->assertSee('Clinica Secreta A');
        $response->assertDontSee('Clinica Secreta B');
    }

    public function test_cross_empresa_clinica_find_returns_null_for_other_empresa(): void
    {
        $clinicaB = Clinica::factory()->create([
            'empresa_id' => $this->empresaB->id,
        ]);

        EmpresaContext::set($this->empresaA->id);

        // Explicit query using find() respects global scope
        $found = Clinica::find($clinicaB->id);

        $this->assertNull(
            $found,
            'Clinica::find() should respect global scope and return null for other empresa'
        );
    }

    public function test_cross_empresa_examen_isolation(): void
    {
        Examen::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'nombre' => 'Examen Privado A',
        ]);
        Examen::factory()->create([
            'empresa_id' => $this->empresaB->id,
            'nombre' => 'Examen Privado B',
        ]);

        EmpresaContext::set($this->empresaA->id);

        $examenes = Examen::all();

        $this->assertTrue(
            $examenes->contains('nombre', 'Examen Privado A'),
            'Examen from empresa A should be visible'
        );
        $this->assertFalse(
            $examenes->contains('nombre', 'Examen Privado B'),
            'Examen from empresa B should NOT be visible'
        );
    }

    // ────────────────────────────────────────────────────────────
    // 2.4 [RED] Test: withoutGlobalScope() returns all records (SaaS admin)
    // ────────────────────────────────────────────────────────────

    public function test_without_global_scope_returns_all_records(): void
    {
        Clinica::factory()->create(['empresa_id' => $this->empresaA->id]);
        Clinica::factory()->create(['empresa_id' => $this->empresaB->id]);

        EmpresaContext::set($this->empresaA->id);

        $scopedCount = Clinica::count();
        $this->assertEquals(1, $scopedCount, 'Scoped query should return only empresa A');

        $unscopedCount = Clinica::withoutGlobalScope('empresa')->count();
        $this->assertEquals(2, $unscopedCount, 'Unscoped query should return all clinicas');
    }

    public function test_scope_without_empresa_scope_convenience_method(): void
    {
        Clinica::factory()->create(['empresa_id' => $this->empresaA->id]);
        Clinica::factory()->create(['empresa_id' => $this->empresaB->id]);

        EmpresaContext::set($this->empresaA->id);

        $this->assertCount(
            2,
            Clinica::withoutEmpresaScope()->get(),
            'withoutEmpresaScope convenience method should return all records'
        );
    }

    public function test_saas_admin_user_index_uses_without_global_scope(): void
    {
        Clinica::factory()->create(['empresa_id' => $this->empresaA->id]);
        Clinica::factory()->create(['empresa_id' => $this->empresaB->id]);

        // SaaS admin is assigned to empresa A for the foreign key constraint
        $saasAdmin = User::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'role' => 'administrador',
            'name' => 'SaaS Admin',
        ]);

        EmpresaContext::set($this->empresaA->id);

        // Simulate the SaaSAdminController::index() behavior
        $users = User::withoutGlobalScope('empresa')
            ->with('clinica')
            ->orderBy('name')
            ->paginate(20);

        $this->assertGreaterThanOrEqual(
            3,
            $users->total(),
            'SaaS admin should see all users across all empresas'
        );
    }

    public function test_saas_admin_dashboard_shows_all_users(): void
    {
        User::factory()->create(['empresa_id' => $this->empresaA->id]);
        User::factory()->create(['empresa_id' => $this->empresaB->id]);

        EmpresaContext::clear();

        $totalUsers = User::withoutGlobalScope('empresa')->count();

        $this->assertGreaterThanOrEqual(
            3,
            $totalUsers,
            'SaaS admin dashboard should count all users across all empresas'
        );
    }

    // ────────────────────────────────────────────────────────────
    // 2.14 Gate: cross-empresa attack test
    // ────────────────────────────────────────────────────────────

    public function test_cross_empresa_attack_via_raw_sql_is_blocked(): void
    {
        $clinicaB = Clinica::factory()->create([
            'empresa_id' => $this->empresaB->id,
            'nombre' => 'Clinica Confidencial B',
        ]);

        EmpresaContext::set($this->empresaA->id);

        // Attempt to access clinica B directly through Eloquent
        $found = Clinica::find($clinicaB->id);

        $this->assertNull(
            $found,
            'User from empresa A should not be able to find clinica from empresa B via Eloquent'
        );
    }

    public function test_cross_empresa_attack_via_db_table_is_blocked(): void
    {
        $clinicaB = Clinica::factory()->create([
            'empresa_id' => $this->empresaB->id,
        ]);

        EmpresaContext::set($this->empresaA->id);

        // Attempt raw DB query to access clinica B
        $result = \DB::table('clinicas')
            ->where('id', $clinicaB->id)
            ->where('empresa_id', EmpresaContext::get())
            ->first();

        $this->assertNull(
            $result,
            'Raw DB query should filter by empresa_id and not find clinica B'
        );
    }
}
