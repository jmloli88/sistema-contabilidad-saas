<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AiChat\DeepSeekService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;

class DeepSeekServiceTest extends TestCase
{
    private DeepSeekService $service;

    private array $sampleSchema = [
        'tables' => [
            'repases' => ['id', 'clinica_id', 'fecha', 'estado', 'total_neto', 'empresa_id'],
            'clinicas' => ['id', 'nombre', 'empresa_id'],
            'examenes' => ['id', 'nombre', 'precio_sin_nota', 'empresa_id'],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeepSeekService();
    }

    /** @test */
    public function sends_correct_prompt_with_schema_context()
    {
        Http::fake([
            'api.deepseek.com/v1/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'SELECT * FROM repases']],
                ],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120],
            ]),
        ]);

        $result = $this->service->generateSql('¿Cuántos repases hay?', $this->sampleSchema);

        $this->assertSame('SELECT * FROM repases', $result);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            $this->assertArrayHasKey('messages', $body);

            $systemMessage = $body['messages'][0];
            $this->assertSame('system', $systemMessage['role']);
            $this->assertStringContainsString('SELECT', $systemMessage['content']);
            $this->assertStringContainsString('repases', $systemMessage['content']);
            $this->assertStringContainsString('clinicas', $systemMessage['content']);
            $this->assertStringContainsString('examenes', $systemMessage['content']);

            $userMessage = $body['messages'][1];
            $this->assertSame('user', $userMessage['role']);
            $this->assertStringContainsString('¿Cuántos repases hay?', $userMessage['content']);

            $this->assertSame(config('services.deepseek.model', 'deepseek-chat'), $body['model']);

            return true;
        });
    }

    /** @test */
    public function parses_sql_from_response_with_code_blocks()
    {
        Http::fake([
            'api.deepseek.com/v1/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => "```sql\nSELECT * FROM repases WHERE fecha >= '2026-01-01'\n```"]],
                ],
            ]),
        ]);

        $result = $this->service->generateSql('¿Repases de enero?', $this->sampleSchema);

        $this->assertStringContainsString('SELECT', $result);
        $this->assertStringContainsString('repases', $result);
    }

    /** @test */
    public function parses_sql_from_response_without_code_blocks()
    {
        Http::fake([
            'api.deepseek.com/v1/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => "SELECT * FROM repases WHERE estado = 'pendiente'"]],
                ],
            ]),
        ]);

        $result = $this->service->generateSql('¿Repases pendientes?', $this->sampleSchema);

        $this->assertSame("SELECT * FROM repases WHERE estado = 'pendiente'", $result);
    }

    /** @test */
    public function handles_api_authentication_error()
    {
        Http::fake([
            'api.deepseek.com/v1/*' => Http::response([
                'error' => ['message' => 'Invalid API key'],
            ], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DeepSeek API');

        $this->service->generateSql('¿Cuántos repases?', $this->sampleSchema);
    }

    /** @test */
    public function handles_api_timeout_gracefully()
    {
        Http::fake([
            'api.deepseek.com/v1/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DeepSeek API');

        $this->service->generateSql('¿Cuántos repases?', $this->sampleSchema);
    }

    /** @test */
    public function retries_on_empty_response()
    {
        $callCount = 0;

        Http::fake([
            'api.deepseek.com/v1/*' => function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    return Http::response([
                        'choices' => [
                            ['message' => ['content' => '']],
                        ],
                    ]);
                }
                return Http::response([
                    'choices' => [
                        ['message' => ['content' => 'SELECT * FROM repases']],
                    ],
                ]);
            },
        ]);

        $result = $this->service->generateSql('¿Cuántos repases?', $this->sampleSchema);

        $this->assertSame('SELECT * FROM repases', $result);
        $this->assertSame(2, $callCount, 'Should retry once on empty response');
    }

    /** @test */
    public function retry_stops_after_one_failure()
    {
        $callCount = 0;

        Http::fake([
            'api.deepseek.com/v1/*' => function () use (&$callCount) {
                $callCount++;
                return Http::response([
                    'choices' => [
                        ['message' => ['content' => '']],
                    ],
                ]);
            },
        ]);

        $this->expectException(\RuntimeException::class);

        $this->service->generateSql('¿Cuántos repases?', $this->sampleSchema);
    }

    /** @test */
    public function format_response_sends_data_to_api()
    {
        Http::fake([
            'api.deepseek.com/v1/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'En marzo 2026 se registraron 45 repases.']],
                ],
                'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 30, 'total_tokens' => 80],
            ]),
        ]);

        $data = '[{"total": 45, "mes": "2026-03"}]';
        $result = $this->service->formatResponse($data, '¿Cuántos repases en marzo 2026?');

        $this->assertStringContainsString('45 repases', $result);
        $this->assertStringContainsString('marzo', $result);
    }

    /** @test */
    public function response_includes_token_usage_in_metadata()
    {
        Http::fake([
            'api.deepseek.com/v1/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'SELECT * FROM repases']],
                ],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120],
            ]),
        ]);

        $result = $this->service->generateSql('¿Cuántos repases?', $this->sampleSchema);

        $this->assertSame('SELECT * FROM repases', $result);
    }
}
