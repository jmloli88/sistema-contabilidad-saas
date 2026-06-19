<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChatQueryService
{
    private const CACHE_PREFIX = 'chat_query_';
    private const SCHEMA_CACHE_KEY = 'chat_schema';

    public function __construct(
        private readonly DeepSeekService $deepSeek,
        private readonly SqlValidator $sqlValidator,
    ) {}

    public function ask(string $question, int $empresaId): array
    {
        $cacheKey = self::CACHE_PREFIX . md5($question) . '_' . $empresaId;
        $cacheTtl = (int) config('ai-chat.cache_ttl', 300);

        $result = Cache::remember($cacheKey, $cacheTtl, function () use ($question, $empresaId) {
            return $this->processQuery($question, $empresaId);
        });

        return $result;
    }

    private function processQuery(string $question, int $empresaId): array
    {
        try {
            // 1. Load schema context
            $schema = $this->loadSchema();

            // 2. Generate SQL via DeepSeek
            $sql = $this->deepSeek->generateSql($question, $schema);

            // 3. Validate SQL
            $validation = $this->sqlValidator->validate($sql);
            if (!$validation['valid']) {
                return [
                    'answer' => 'La consulta generada no pasó la validación de seguridad. Por favor, reformula tu pregunta.',
                    'tokens' => 0,
                ];
            }

            // 4. Inject empresa scope
            $scopedSql = $this->sqlValidator->injectEmpresaScope($sql, $empresaId);

            // 5. Execute on read-only connection
            $results = DB::connection('mysql_ai_readonly')
                ->select($scopedSql);

            // 6. Format response
            $answer = $this->deepSeek->formatResponse(json_encode($results), $question);

            return [
                'answer' => $answer,
                'tokens' => $this->estimateTokens($question, $sql, $answer),
            ];
        } catch (RuntimeException $e) {
            return [
                'answer' => 'Ocurrió un error al procesar tu consulta. Por favor, intentá de nuevo más tarde.',
                'tokens' => 0,
            ];
        }
    }

    private function loadSchema(): array
    {
        $schema = Cache::get(self::SCHEMA_CACHE_KEY);

        if ($schema !== null) {
            return $schema;
        }

        // Build schema from configuration
        $allowedTables = config('ai-chat.allowed_tables', []);
        $schema = [
            'tables' => [],
        ];

        // Default schema with known table columns
        $tableColumns = [
            'repases' => ['id', 'clinica_id', 'fecha', 'fecha_pago', 'estado', 'tipo_precio', 'total_examenes', 'total_consultas', 'total_gastos', 'total_neto', 'observaciones', 'empresa_id'],
            'clinicas' => ['id', 'nombre', 'direccion', 'telefono', 'empresa_id'],
            'examenes' => ['id', 'nombre', 'precio_sin_nota', 'precio_con_nota', 'empresa_id', 'is_active'],
            'gastos' => ['id', 'repase_id', 'tipo', 'descripcion', 'monto'],
            'repase_examenes' => ['id', 'repase_id', 'examen_id', 'cantidad', 'precio_unitario_usado', 'subtotal'],
            'agendas' => ['id', 'clinica_id', 'fecha', 'hora_inicio', 'hora_fin', 'doctor'],
        ];

        foreach ($allowedTables as $table) {
            if (isset($tableColumns[$table])) {
                $schema['tables'][$table] = $tableColumns[$table];
            }
        }

        // Cache schema for 1 hour
        Cache::put(self::SCHEMA_CACHE_KEY, $schema, 3600);

        return $schema;
    }

    private function estimateTokens(string $question, string $sql, string $answer): int
    {
        // Rough estimation: ~4 chars per token
        return (int) ceil((strlen($question) + strlen($sql) + strlen($answer)) / 4);
    }
}
