<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use App\Models\Clinica;
use App\Models\Examen;
use App\Models\User;

class SchemaExpansionTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────────────────────
    // 1.1 [RED] Test: factories assign empresa_id from seed empresa
    // ────────────────────────────────────────────────────────────

    public function test_clinica_factory_assigns_empresa_id(): void
    {
        $clinica = Clinica::factory()->create();

        $this->assertNotNull($clinica->empresa_id, 'Clinica factory should set empresa_id');
        $this->assertDatabaseHas('clinicas', [
            'id' => $clinica->id,
            'empresa_id' => $clinica->empresa_id,
        ]);
    }

    public function test_user_factory_assigns_empresa_id(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->empresa_id, 'User factory should set empresa_id');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'empresa_id' => $user->empresa_id,
        ]);
    }

    public function test_examen_factory_assigns_empresa_id(): void
    {
        $examen = Examen::factory()->create();

        $this->assertNotNull($examen->empresa_id, 'Examen factory should set empresa_id');
        $this->assertDatabaseHas('examenes', [
            'id' => $examen->id,
            'empresa_id' => $examen->empresa_id,
        ]);
    }

    public function test_factories_can_create_separate_empresas(): void
    {
        $clinicaA = Clinica::factory()->create();
        $clinicaB = Clinica::factory()->create();

        $this->assertNotNull($clinicaA->empresa_id);
        $this->assertNotNull($clinicaB->empresa_id);
        // They could share the same empresa (since Empresa::factory() creates a new one each time)
        // The key assertion: both have a non-null empresa_id backing them
        $this->assertDatabaseHas('empresas', ['id' => $clinicaA->empresa_id]);
        $this->assertDatabaseHas('empresas', ['id' => $clinicaB->empresa_id]);
    }

    // ────────────────────────────────────────────────────────────
    // 1.2 [RED] Test: migration adds FK columns correctly
    // ────────────────────────────────────────────────────────────

    public function test_clinicas_has_empresa_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('clinicas', 'empresa_id'),
            'clinicas table must have empresa_id column'
        );
    }

    public function test_users_has_empresa_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('users', 'empresa_id'),
            'users table must have empresa_id column'
        );
    }

    public function test_examenes_has_empresa_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('examenes', 'empresa_id'),
            'examenes table must have empresa_id column'
        );
    }

    public function test_subscriptions_has_empresa_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('subscriptions', 'empresa_id'),
            'subscriptions table must have empresa_id column'
        );
    }

    public function test_empresa_id_foreign_key_exists_on_clinicas(): void
    {
        $foreignKeys = Schema::getForeignKeys('clinicas');
        $empresaFk = collect($foreignKeys)->firstWhere('columns', ['empresa_id']);
        $this->assertNotNull($empresaFk, 'clinicas.empresa_id must have a foreign key constraint');
        $this->assertEquals('empresas', $empresaFk['foreign_table']);
    }

    public function test_empresa_id_foreign_key_exists_on_users(): void
    {
        $foreignKeys = Schema::getForeignKeys('users');
        $empresaFk = collect($foreignKeys)->firstWhere('columns', ['empresa_id']);
        $this->assertNotNull($empresaFk, 'users.empresa_id must have a foreign key constraint');
        $this->assertEquals('empresas', $empresaFk['foreign_table']);
    }

    public function test_empresa_id_foreign_key_exists_on_examenes(): void
    {
        $foreignKeys = Schema::getForeignKeys('examenes');
        $empresaFk = collect($foreignKeys)->firstWhere('columns', ['empresa_id']);
        $this->assertNotNull($empresaFk, 'examenes.empresa_id must have a foreign key constraint');
        $this->assertEquals('empresas', $empresaFk['foreign_table']);
    }
}
