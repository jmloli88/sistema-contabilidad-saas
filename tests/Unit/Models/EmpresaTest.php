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
        $empresa = Empresa::create(['nombre' => 'Zumed Medicina Diagnóstica']);

        $this->assertDatabaseHas('empresas', [
            'id' => $empresa->id,
            'nombre' => 'Zumed Medicina Diagnóstica',
        ]);
        $this->assertNotNull($empresa->id);
        $this->assertEquals('Zumed Medicina Diagnóstica', $empresa->nombre);
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

        $this->assertCount(3, Empresa::all());
        $this->assertEquals('Empresa Alpha', Empresa::where('nombre', 'Empresa Alpha')->first()->nombre);
        $this->assertEquals('Empresa Beta', Empresa::where('nombre', 'Empresa Beta')->first()->nombre);
        $this->assertEquals('Empresa Gamma', Empresa::where('nombre', 'Empresa Gamma')->first()->nombre);
    }

    public function test_empresa_can_be_retrieved_from_database(): void
    {
        $empresa = Empresa::create(['nombre' => 'Zumed Medicina Diagnóstica']);

        $retrieved = Empresa::find($empresa->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals('Zumed Medicina Diagnóstica', $retrieved->nombre);
        $this->assertInstanceOf(Empresa::class, $retrieved);
    }

    public function test_empresa_uses_has_factory_trait(): void
    {
        $this->assertContains(
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            class_uses(Empresa::class)
        );
    }
}
