<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AiChat\SqlResponseSchema;

class SqlResponseSchemaTest extends TestCase
{
    /** @test */
    public function schema_has_correct_name()
    {
        $schema = new SqlResponseSchema();

        $this->assertSame('sql_response', $schema->name());
    }

    /** @test */
    public function schema_has_type_and_content_as_required_fields()
    {
        $schema = new SqlResponseSchema();

        $this->assertContains('type', $schema->requiredFields);
        $this->assertContains('content', $schema->requiredFields);
    }

    /** @test */
    public function schema_serializes_to_valid_array()
    {
        $schema = new SqlResponseSchema();
        $array = $schema->toArray();

        $this->assertArrayHasKey('type', $array);
        $this->assertSame('object', $array['type']);

        $properties = $array['properties'];
        $this->assertArrayHasKey('type', $properties);
        $this->assertArrayHasKey('content', $properties);

        $this->assertSame('string', $properties['type']['type']);
        $this->assertStringContainsString('sql', $properties['type']['description']);

        $this->assertSame('string', $properties['content']['type']);
        $this->assertStringContainsString('SQL', $properties['content']['description']);
    }

    /** @test */
    public function schema_properties_have_correct_descriptions()
    {
        $schema = new SqlResponseSchema();
        $properties = $schema->toArray()['properties'];

        $this->assertStringContainsString('sql', $properties['type']['description']);
        $this->assertStringContainsString('conversational', $properties['type']['description']);
        $this->assertStringContainsString('SQL', $properties['content']['description']);
    }

    /** @test */
    public function schema_requires_type_and_content()
    {
        $schema = new SqlResponseSchema();
        $required = $schema->requiredFields;

        $this->assertCount(2, $required);
        $this->assertSame(['type', 'content'], $required);
    }
}
