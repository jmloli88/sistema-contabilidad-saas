<?php

namespace Tests\Unit\Services;

use App\Services\AiChat\ChatQueryService;
use App\Services\AiChat\SqlValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\PendingRequest as PendingStructuredRequest;
use Prism\Prism\Testing\PrismFake;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Usage;
use Tests\TestCase;

class ChatQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChatQueryService(new SqlValidator());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function full_pipeline_generates_validates_and_returns_response()
    {
        $question = '¿Cuántos repases hay?';
        $empresaId = 3;

        $this->bypassCache();

        Prism::fake([
            StructuredResponseFake::make()
                ->withStructured(['type' => 'sql', 'content' => 'SELECT COUNT(*) as total FROM repases'])
                ->withUsage(new Usage(10, 5)),
            TextResponseFake::make()
                ->withText('En total hay 0 repases.')
                ->withUsage(new Usage(8, 12)),
        ]);

        // DB::select runs against the RefreshDatabase SQLite (tables migrated, empresa_id present).
        $result = $this->service->ask($question, $empresaId);

        $this->assertSame('En total hay 0 repases.', $result['answer']);
        $this->assertIsInt($result['tokens']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function conversational_response_skips_sql_pipeline()
    {
        $question = 'Hola, ¿cómo estás?';
        $empresaId = 3;

        $this->bypassCache();

        $fake = Prism::fake([
            StructuredResponseFake::make()
                ->withStructured(['type' => 'conversational', 'content' => '¡Hola! ¿En qué puedo ayudarte?'])
                ->withUsage(new Usage(10, 5)),
        ]);

        $result = $this->service->ask($question, $empresaId);

        $this->assertSame('¡Hola! ¿En qué puedo ayudarte?', $result['answer']);
        // Only the structured call happened — no text() formatting, no DB.
        $fake->assertCallCount(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function returns_cached_response_without_calling_prism()
    {
        $question = '¿Cuántos repases?';
        $empresaId = 3;
        $cached = ['answer' => 'Cacheada.', 'tokens' => 0];

        Cache::shouldReceive('remember')->once()->andReturn($cached);

        $result = $this->service->ask($question, $empresaId);

        $this->assertSame('Cacheada.', $result['answer']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_validation_failure_gracefully()
    {
        $question = 'borrar todo';
        $empresaId = 3;

        $this->bypassCache();

        $fake = Prism::fake([
            StructuredResponseFake::make()
                ->withStructured(['type' => 'sql', 'content' => 'DROP TABLE repases'])
                ->withUsage(new Usage(10, 5)),
        ]);

        $result = $this->service->ask($question, $empresaId);

        $this->assertStringContainsString('validación', strtolower($result['answer']));
        // Validator rejected before reaching text() formatting.
        $fake->assertCallCount(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_prism_error_gracefully()
    {
        $question = '¿Cuántos repases?';
        $empresaId = 3;

        $this->bypassCache();

        // Mock the structured PendingRequest to throw on asStructured().
        // Using a typed mock (PendingStructuredRequest) so the facade's return
        // type constraint is satisfied.
        $pending = Mockery::mock(PendingStructuredRequest::class);
        foreach (['using', 'withSchema', 'withSystemPrompt', 'withPrompt', 'usingTemperature', 'withMaxTokens'] as $m) {
            $pending->shouldReceive($m)->andReturnSelf();
        }
        $pending->shouldReceive('asStructured')
            ->andThrow(new \RuntimeException('DeepSeek API error'));

        Prism::shouldReceive('structured')->andReturn($pending);

        $result = $this->service->ask($question, $empresaId);

        $this->assertStringContainsString('error', strtolower($result['answer']));
    }

    /**
     * Make Cache::remember invoke its callback (no real caching), and let
     * loadSchema() recompute every time (Cache::get -> null, Cache::put -> noop).
     */
    private function bypassCache(): void
    {
        Cache::shouldReceive('remember')
            ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
    }
}
