<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AiChat\ChatQueryService;
use App\Services\AiChat\DeepSeekService;
use App\Services\AiChat\SqlValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;

class ChatQueryServiceTest extends TestCase
{
    private ChatQueryService $service;
    private DeepSeekService|Mockery\MockInterface $deepSeekMock;
    private SqlValidator|Mockery\MockInterface $sqlValidatorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deepSeekMock = Mockery::mock(DeepSeekService::class);
        $this->sqlValidatorMock = Mockery::mock(SqlValidator::class);

        $this->service = new ChatQueryService(
            $this->deepSeekMock,
            $this->sqlValidatorMock
        );
    }

    /** @test */
    public function full_pipeline_generates_validates_and_returns_response()
    {
        $question = '¿Cuántos repases hay en marzo 2026?';
        $empresaId = 3;
        $generatedSql = 'SELECT COUNT(*) as total FROM repases WHERE fecha >= "2026-03-01" AND fecha <= "2026-03-31"';
        $scopedSql = 'SELECT COUNT(*) as total FROM repases WHERE empresa_id = 3 AND fecha >= "2026-03-01" AND fecha <= "2026-03-31"';
        $queryResult = [['total' => 45]];
        $formattedAnswer = 'En marzo 2026 se registraron 45 repases.';

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->deepSeekMock->shouldReceive('generateSql')
            ->once()
            ->with($question, Mockery::type('array'))
            ->andReturn($generatedSql);

        $this->sqlValidatorMock->shouldReceive('validate')
            ->once()
            ->with($generatedSql)
            ->andReturn(['valid' => true, 'error' => null]);

        $this->sqlValidatorMock->shouldReceive('injectEmpresaScope')
            ->once()
            ->with($generatedSql, $empresaId)
            ->andReturn($scopedSql);

        DB::shouldReceive('connection')
            ->once()
            ->with('mysql_ai_readonly')
            ->andReturnSelf();
        DB::shouldReceive('select')
            ->once()
            ->with($scopedSql)
            ->andReturn($queryResult);

        $this->deepSeekMock->shouldReceive('formatResponse')
            ->once()
            ->with(json_encode($queryResult), $question)
            ->andReturn($formattedAnswer);

        $schema = ['tables' => ['repases' => ['id', 'fecha', 'total_neto', 'empresa_id']]];
        Cache::shouldReceive('get')
            ->once()
            ->with('chat_schema')
            ->andReturn($schema);

        $result = $this->service->ask($question, $empresaId);

        $this->assertArrayHasKey('answer', $result);
        $this->assertArrayHasKey('tokens', $result);
        $this->assertSame($formattedAnswer, $result['answer']);
    }

    /** @test */
    public function returns_cached_response_without_calling_api()
    {
        $question = '¿Cuántos repases hay?';
        $empresaId = 3;
        $cachedResult = ['answer' => 'Respuesta cacheada.', 'tokens' => 0];

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($cachedResult);

        $result = $this->service->ask($question, $empresaId);

        $this->assertSame($cachedResult['answer'], $result['answer']);
        $this->assertSame($cachedResult['tokens'], $result['tokens']);
    }

    /** @test */
    public function handles_validation_failure_gracefully()
    {
        $question = 'DROP TABLE repases';
        $empresaId = 3;
        $generatedSql = 'DROP TABLE repases';

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->deepSeekMock->shouldReceive('generateSql')
            ->once()
            ->with($question, Mockery::type('array'))
            ->andReturn($generatedSql);

        $this->sqlValidatorMock->shouldReceive('validate')
            ->once()
            ->with($generatedSql)
            ->andReturn(['valid' => false, 'error' => 'Only SELECT statements are allowed.']);

        $schema = ['tables' => ['repases' => ['id', 'fecha', 'total_neto', 'empresa_id']]];
        Cache::shouldReceive('get')
            ->once()
            ->with('chat_schema')
            ->andReturn($schema);

        $result = $this->service->ask($question, $empresaId);

        $this->assertArrayHasKey('answer', $result);
        $this->assertStringContainsString('validación', strtolower($result['answer']));
    }

    /** @test */
    public function handles_generate_sql_error_gracefully()
    {
        $question = '¿Cuántos repases?';
        $empresaId = 3;

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->deepSeekMock->shouldReceive('generateSql')
            ->once()
            ->with($question, Mockery::type('array'))
            ->andThrow(new \RuntimeException('DeepSeek API error'));

        $schema = ['tables' => ['repases' => ['id', 'fecha', 'total_neto', 'empresa_id']]];
        Cache::shouldReceive('get')
            ->once()
            ->with('chat_schema')
            ->andReturn($schema);

        $result = $this->service->ask($question, $empresaId);

        $this->assertArrayHasKey('answer', $result);
        $this->assertStringContainsString('error', strtolower($result['answer']));
    }
}
