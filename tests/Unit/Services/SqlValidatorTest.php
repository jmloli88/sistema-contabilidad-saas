<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AiChat\SqlValidator;

class SqlValidatorTest extends TestCase
{
    private SqlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SqlValidator();
    }

    /** @test */
    public function allows_simple_select_with_allowed_tables()
    {
        $result = $this->validator->validate('SELECT * FROM repases');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function allows_select_with_multiple_allowed_tables_and_joins()
    {
        $sql = 'SELECT repases.id, clinicas.nombre FROM repases JOIN clinicas ON repases.clinica_id = clinicas.id';
        $result = $this->validator->validate($sql);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function allows_select_with_where_clause()
    {
        $result = $this->validator->validate("SELECT * FROM examenes WHERE nombre LIKE '%rayos%'");

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function blocks_insert_statement()
    {
        $result = $this->validator->validate('INSERT INTO repases (clinica_id) VALUES (1)');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_update_statement()
    {
        $result = $this->validator->validate("UPDATE repases SET estado = 'pagado' WHERE id = 1");

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_delete_statement()
    {
        $result = $this->validator->validate('DELETE FROM repases WHERE id = 1');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_drop_statement()
    {
        $result = $this->validator->validate('DROP TABLE repases');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_alter_statement()
    {
        $result = $this->validator->validate('ALTER TABLE repases ADD COLUMN test VARCHAR(255)');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_truncate_statement()
    {
        $result = $this->validator->validate('TRUNCATE TABLE repases');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_create_statement()
    {
        $result = $this->validator->validate('CREATE TABLE test (id INT)');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_exec_statement()
    {
        $result = $this->validator->validate("EXEC sp_help 'repases'");

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_access_to_blocked_columns_in_select()
    {
        $result = $this->validator->validate('SELECT password FROM users');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_access_to_remember_token_column()
    {
        $result = $this->validator->validate('SELECT remember_token FROM users');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_queries_with_multiple_statements()
    {
        $result = $this->validator->validate("SELECT * FROM repases; DELETE FROM repases");

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function blocks_queries_with_semicolon_in_middle()
    {
        $result = $this->validator->validate("SELECT * FROM repases; SELECT * FROM clinicas");

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function strips_single_line_comments_before_validation()
    {
        $result = $this->validator->validate("SELECT * FROM repases -- WHERE id = 1");

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function strips_multi_line_comments_before_validation()
    {
        $result = $this->validator->validate("SELECT * FROM repases /* comentario */ WHERE id = 1");

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function rejects_non_whitelisted_table()
    {
        $result = $this->validator->validate('SELECT * FROM users');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function rejects_union_select()
    {
        $result = $this->validator->validate("SELECT * FROM repases UNION SELECT * FROM users");

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function injects_empresa_id_where_clause()
    {
        $sql = 'SELECT * FROM repases';
        $result = $this->validator->injectEmpresaScope($sql, 3);

        $this->assertStringContainsString('WHERE', $result);
        $this->assertStringContainsString('empresa_id = 3', $result);
    }

    /** @test */
    public function injects_empresa_id_with_existing_where()
    {
        $sql = "SELECT * FROM repases WHERE estado = 'pendiente'";
        $result = $this->validator->injectEmpresaScope($sql, 5);

        $this->assertStringContainsString('AND', $result);
        $this->assertStringContainsString('empresa_id = 5', $result);
        $this->assertStringContainsString("estado = 'pendiente'", $result);
    }

    /** @test */
    public function injects_empresa_id_via_join_for_indirect_tables()
    {
        $sql = 'SELECT * FROM repase_examenes JOIN repases ON repase_examenes.repase_id = repases.id';
        $result = $this->validator->injectEmpresaScope($sql, 3);

        $this->assertStringContainsString('empresa_id = 3', $result);
    }
}
