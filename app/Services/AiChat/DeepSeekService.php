<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class DeepSeekService
{
    private const MAX_RETRIES = 1;

    public function generateSql(string $question, array $schema): string
    {
        $prompt = $this->buildSqlPrompt($question, $schema);

        $response = $this->callApi($prompt);

        $sql = $this->extractSql($response);

        // Retry once if the response was empty
        if (empty(trim($sql))) {
            $response = $this->callApi($prompt);
            $sql = $this->extractSql($response);
        }

        if (empty(trim($sql))) {
            throw new RuntimeException('DeepSeek API returned an empty response after retry.');
        }

        return $sql;
    }

    public function formatResponse(string $data, string $question): string
    {
        $prompt = $this->buildFormatPrompt($data, $question);
        $response = $this->callApi($prompt);

        $content = $response['choices'][0]['message']['content'] ?? '';

        if (empty(trim($content))) {
            throw new RuntimeException('DeepSeek API returned an empty formatting response.');
        }

        return $content;
    }

    private function buildSqlPrompt(string $question, array $schema): array
    {
        $schemaDescription = $this->buildSchemaDescription($schema);

        $systemPrompt = <<<PROMPT
Eres un asistente SQL especializado en un sistema de contabilidad médica.
Tu tarea es convertir preguntas en español a consultas SQL válidas.

Requisitos:
- Genera ÚNICAMENTE sentencias SELECT
- NO uses INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, CREATE, EXEC, UNION
- Usa solo las tablas y columnas proporcionadas en el esquema
- Incluye siempre filtro por empresa_id cuando la tabla lo tenga
- Responde ÚNICAMENTE con el código SQL, sin explicaciones adicionales
- Si la pregunta no se puede responder con los datos disponibles, responde con: -- No se puede responder con los datos disponibles

Esquema de la base de datos:
{$schemaDescription}
PROMPT;

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Pregunta: {$question}"],
        ];
    }

    private function buildFormatPrompt(string $data, string $question): array
    {
        $systemPrompt = <<<PROMPT
Eres un asistente financiero que responde en español.
Recibirás datos de una consulta SQL y la pregunta original del usuario.
Debes responder de forma natural y conversacional en español, como si fueras un analista financiero.

Reglas:
- Responde ÚNICAMENTE en español
- Sé conciso pero informativo
- Si los datos están vacíos, indica que no se encontraron resultados
- NO inventes datos que no estén en los resultados
- Formatea montos en pesos argentinos ($)
PROMPT;

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Pregunta: {$question}\n\nDatos obtenidos:\n{$data}"],
        ];
    }

    private function buildSchemaDescription(array $schema): string
    {
        $lines = [];
        foreach ($schema['tables'] as $table => $columns) {
            $lines[] = "- {$table}: " . implode(', ', $columns);
        }
        return implode("\n", $lines);
    }

    private function callApi(array $messages): array
    {
        $url = rtrim(config('services.deepseek.base_url', 'https://api.deepseek.com/v1'), '/') . '/chat/completions';

        try {
            $response = Http::withToken(config('services.deepseek.api_key'))
                ->timeout(60)
                ->post($url, [
                    'model' => config('services.deepseek.model', 'deepseek-chat'),
                    'messages' => $messages,
                    'max_tokens' => (int) config('services.deepseek.max_tokens', 1000),
                    'temperature' => 0.1,
                ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    'DeepSeek API error: ' . ($response->json('error.message') ?? 'Unknown error')
                );
            }

            return $response->json();
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'DeepSeek API connection error: ' . $e->getMessage()
            );
        }
    }

    private function extractSql(array $response): string
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        // Remove markdown code blocks if present
        $content = preg_replace('/```(?:sql)?\s*\n?(.*?)\n?```/s', '$1', $content);

        return trim($content);
    }
}
