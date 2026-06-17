<?php

namespace Tests\Unit\Models;

use App\Models\Empresa;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpresaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_empresa_with_nombre(): void
    {
        // Use a unique name that doesn't conflict with the seed empresa ("Zumed Medicina Diagnóstica")
        $empresa = Empresa::create(['nombre' => 'Clínica Nuevo Sol']);

        $this->assertDatabaseHas('empresas', [
            'id' => $empresa->id,
            'nombre' => 'Clínica Nuevo Sol',
        ]);
        $this->assertNotNull($empresa->id);
        $this->assertEquals('Clínica Nuevo Sol', $empresa->nombre);
    }

    public function test_empresa_factory_creates_valid_record(): void
    {
        $empresa = Empresa::factory()->create();

        $this->assertDatabaseHas('empresas', [
            'id' => $empresa->id,
            'nombre' => $empresa->nombre,
        ]);
        $this->assertNotNull($empresa->nombre);
        $this->assertNotEmpty($empresa->nombre);
    }

    public function test_empresa_nombre_is_unique(): void
    {
        Empresa::create(['nombre' => 'Clínica Única']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/UNIQUE|unique|duplicate/i');

        Empresa::create(['nombre' => 'Clínica Única']);
    }

    public function test_multiple_empresas_with_different_nombres_can_coexist(): void
    {
        Empresa::create(['nombre' => 'Empresa Alpha']);
        Empresa::create(['nombre' => 'Empresa Beta']);
        Empresa::create(['nombre' => 'Empresa Gamma']);

        // 4 including the seed empresa ("Zumed Medicina Diagnóstica") from the data migration
        $this->assertCount(4, Empresa::all());
        $this->assertEquals('Empresa Alpha', Empresa::where('nombre', 'Empresa Alpha')->first()->nombre);
        $this->assertEquals('Empresa Beta', Empresa::where('nombre', 'Empresa Beta')->first()->nombre);
        $this->assertEquals('Empresa Gamma', Empresa::where('nombre', 'Empresa Gamma')->first()->nombre);
    }

    public function test_empresa_can_be_retrieved_from_database(): void
    {
        // Use a unique name that doesn't conflict with the seed empresa
        $empresa = Empresa::create(['nombre' => 'Clínica Nuevo Sol']);

        $retrieved = Empresa::find($empresa->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals('Clínica Nuevo Sol', $retrieved->nombre);
        $this->assertInstanceOf(Empresa::class, $retrieved);
    }

    public function test_empresa_uses_has_factory_trait(): void
    {
        $this->assertContains(
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            class_uses(Empresa::class)
        );
    }

    // === hasActiveSubscription ===

    public function test_empresa_with_active_subscription_returns_true(): void
    {
        $empresa = Empresa::factory()->create();
        $user = \App\Models\User::factory()->create(['empresa_id' => $empresa->id]);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_active_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $this->assertTrue($empresa->hasActiveSubscription());
    }

    public function test_empresa_with_expired_subscription_returns_false(): void
    {
        $empresa = Empresa::factory()->create();
        $user = \App\Models\User::factory()->create(['empresa_id' => $empresa->id]);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_expired_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->subDays(1),
        ]);

        $this->assertFalse($empresa->hasActiveSubscription());
    }

    public function test_empresa_with_no_users_returns_false(): void
    {
        $empresa = Empresa::factory()->create();

        $this->assertFalse($empresa->hasActiveSubscription());
    }

    public function test_empresa_with_canceled_subscription_returns_false(): void
    {
        $empresa = Empresa::factory()->create();
        $user = \App\Models\User::factory()->create(['empresa_id' => $empresa->id]);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_canceled_test',
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $this->assertFalse($empresa->hasActiveSubscription());
    }

    public function test_empresa_with_multiple_users_one_active_returns_true(): void
    {
        $empresa = Empresa::factory()->create();
        $userWithSub = \App\Models\User::factory()->create(['empresa_id' => $empresa->id]);
        $userWithSub->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_active_multi',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        \App\Models\User::factory()->count(2)->create(['empresa_id' => $empresa->id]);

        $this->assertTrue($empresa->hasActiveSubscription());
    }

    public function test_empresa_with_users_but_no_subscriptions_returns_false(): void
    {
        $empresa = Empresa::factory()->create();
        \App\Models\User::factory()->count(3)->create(['empresa_id' => $empresa->id]);

        $this->assertFalse($empresa->hasActiveSubscription());
    }
}
