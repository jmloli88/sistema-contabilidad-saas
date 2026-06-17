<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Examen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario: Creating a Clinica without empresa_id throws a DB exception.
     *
     * Given the column is nullable WITHOUT the NOT NULL constraint,
     * Then creating a Clinica without empresa_id should succeed (nullable).
     * Once the migration applies, it MUST throw QueryException.
     *
     * This test is written BEFORE the migration. During the RED phase it
     * passes because the column is still nullable. In the GREEN phase,
     * the migration makes it NOT NULL and the test expectation validates
     * the constraint.
     */
    public function test_creating_clinica_without_empresa_id_throws_exception(): void
    {
        // Attempt to create a Clinica without empresa_id — must fail after NOT NULL
        $this->expectException(QueryException::class);

        Clinica::create([
            'nombre' => 'Clinica Sin Empresa',
            'direccion' => 'Sin direccion',
            'telefono' => '000-0000',
            // empresa_id intentionally omitted
        ]);
    }

    /**
     * Scenario: Creating a User without empresa_id throws a DB exception.
     *
     * Same as above — the NOT NULL constraint at DB level must reject
     * records without empresa_id. Using the factory with empresa_id => null
     * because the factory default provides one.
     */
    public function test_creating_user_without_empresa_id_throws_exception(): void
    {
        $this->expectException(QueryException::class);

        // Override the factory default to omit empresa_id
        User::factory()->create([
            'empresa_id' => null,
        ]);
    }

    /**
     * Scenario: Creating an Examen without empresa_id throws a DB exception.
     */
    public function test_creating_examen_without_empresa_id_throws_exception(): void
    {
        $this->expectException(QueryException::class);

        Examen::factory()->create([
            'empresa_id' => null,
        ]);
    }
}
