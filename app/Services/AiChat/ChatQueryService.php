<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Prism\Prism\Enums\StreamEventType;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class ChatQueryService
{
    private const CACHE_PREFIX = 'chat_query_';
    private const SCHEMA_CACHE_KEY = 'chat_schema';

    private const SQL_MODEL = 'deepseek-chat';
    private const SQL_TEMPERATURE = 0.1;
    private const SQL_MAX_TOKENS = 1000;
    private const MAX_HISTORY_MESSAGES = 10;

    private int $lastFormatTokens = 0;

    public function __construct(
        private readonly SqlValidator $sqlValidator,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history  Previous conversation messages (oldest first).
     */
    public function ask(string $question, int $empresaId, array $history = []): array
    {
        // Cache only when there's no history (history-aware queries are contextual).
        if (empty($history)) {
            $cacheKey = self::CACHE_PREFIX . md5($question) . '_' . $empresaId;
            $cacheTtl = (int) config('ai-chat.cache_ttl', 300);

            return Cache::remember($cacheKey, $cacheTtl, fn () => $this->processQuery($question, $empresaId, $history));
        }

        return $this->processQuery($question, $empresaId, $history);
    }

    /**
     * Streaming variant with conversation history support.
     *
     * Yields progress-status chunks ({delta: '', done: false, status: 'thinking'})
     * during the non-streamable pipeline phases (SQL gen, validation, DB execution),
     * then streams the formatted response token-by-token. Conversational (no-SQL)
     * responses are yielded character-by-character to simulate typing.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function askStreaming(string $question, int $empresaId, array $history = []): \Generator
    {
        $schema = $this->loadSchema();

        // Signal the front-end to show a "thinking" indicator.
        yield ['delta' => '', 'done' => false, 'status' => 'thinking'];

        $sqlResponse = $this->generateSql($question, $schema, $empresaId, $history);

        if ($sqlResponse['type'] === 'conversational') {
            // Stream the conversational response character by character
            // (the text is already complete from structured output, but we
            // display it progressively so it doesn't appear all at once).
            $content = $sqlResponse['content'];
            $len = mb_strlen($content);
            for ($i = 0; $i < $len; $i++) {
                yield ['delta' => mb_substr($content, $i, 1), 'done' => false];
            }
            yield ['delta' => '', 'done' => true];
            return;
        }

        $sql = $sqlResponse['content'];

        $validation = $this->sqlValidator->validate($sql);
        if (!$validation['valid']) {
            yield ['delta' => '⚠️ La consulta generada no pasó la validación de seguridad. Reformulá tu pregunta, por favor.', 'done' => true];
            return;
        }

        $scopedSql = $this->sqlValidator->injectEmpresaScope($sql, $empresaId);

        try {
            // Signal the front-end that we're now querying the database.
            yield ['delta' => '', 'done' => false, 'status' => 'querying'];

            $results = DB::select($scopedSql);
        } catch (\Throwable $e) {
            \Log::error('ChatQueryService DB error: ' . $e->getMessage(), ['sql' => $scopedSql]);
            yield ['delta' => '❌ Ocurrió un error al consultar la base de datos. Intentá de nuevo más tarde.', 'done' => true];
            return;
        }

        // Clear the status so the front-end removes the indicator.
        yield ['delta' => '', 'done' => false, 'status' => 'responding'];

        foreach ($this->streamFormattedResponse(json_encode($results), $question, $history) as $chunk) {
            yield $chunk;
        }
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function processQuery(string $question, int $empresaId, array $history = []): array
    {
        try {
            $schema = $this->loadSchema();
            $sqlResponse = $this->generateSql($question, $schema, $empresaId, $history);

            if ($sqlResponse['type'] === 'conversational') {
                return [
                    'answer' => $sqlResponse['content'],
                    'tokens' => $sqlResponse['tokens'],
                ];
            }

            $sql = $sqlResponse['content'];

            $validation = $this->sqlValidator->validate($sql);
            if (!$validation['valid']) {
                return [
                    'answer' => '⚠️ La consulta generada no pasó la validación de seguridad. Reformulá tu pregunta, por favor.',
                    'tokens' => 0,
                ];
            }

            $scopedSql = $this->sqlValidator->injectEmpresaScope($sql, $empresaId);
            $results = DB::select($scopedSql);

            $answer = $this->formatResponse(json_encode($results), $question, $history);

            return [
                'answer' => $answer,
                'tokens' => $sqlResponse['tokens'] + ($this->lastFormatTokens ?? 0),
            ];
        } catch (\Exception $e) {
            \Log::error('ChatQueryService error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'answer' => '❌ Ocurrió un error al procesar tu consulta. Intentá de nuevo más tarde.',
                'tokens' => 0,
            ];
        }
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function generateSql(string $question, array $schema, int $empresaId, array $history = []): array
    {
        $systemPrompt = $this->buildSqlSystemPrompt($schema);
        $messages = $this->buildPrismMessages($history, $this->buildSqlUserPrompt($question, $empresaId));

        $response = Prism::structured()
            ->using('deepseek', self::SQL_MODEL)
            ->withSchema(new SqlResponseSchema())
            ->withSystemPrompt($systemPrompt)
            ->withMessages($messages)
            ->usingTemperature(self::SQL_TEMPERATURE)
            ->withMaxTokens(self::SQL_MAX_TOKENS)
            ->asStructured();

        $structured = $response->structured ?? [];
        $type = $structured['type'] ?? 'conversational';
        $content = $structured['content'] ?? '';

        // Retry once on empty content (semantic empty, not HTTP failure)
        if (trim($content) === '') {
            $response = Prism::structured()
                ->using('deepseek', self::SQL_MODEL)
                ->withSchema(new SqlResponseSchema())
                ->withSystemPrompt($systemPrompt)
                ->withMessages($messages)
                ->usingTemperature(self::SQL_TEMPERATURE)
                ->withMaxTokens(self::SQL_MAX_TOKENS)
                ->asStructured();

            $structured = $response->structured ?? [];
            $type = $structured['type'] ?? 'conversational';
            $content = $structured['content'] ?? '';
        }

        $tokens = ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0);

        return [
            'type' => $type,
            'content' => $content,
            'tokens' => $tokens,
        ];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function formatResponse(string $data, string $question, array $history = []): string
    {
        $messages = $this->buildPrismMessages($history, $this->buildFormatUserPrompt($data, $question));

        $response = Prism::text()
            ->using('deepseek', self::SQL_MODEL)
            ->withSystemPrompt($this->buildFormatSystemPrompt())
            ->withMessages($messages)
            ->usingTemperature(self::SQL_TEMPERATURE)
            ->asText();

        $this->lastFormatTokens = ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0);

        return $response->text;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function streamFormattedResponse(string $data, string $question, array $history = []): \Generator
    {
        $messages = $this->buildPrismMessages($history, $this->buildFormatUserPrompt($data, $question));

        $stream = Prism::text()
            ->using('deepseek', self::SQL_MODEL)
            ->withSystemPrompt($this->buildFormatSystemPrompt())
            ->withMessages($messages)
            ->usingTemperature(self::SQL_TEMPERATURE)
            ->withClientOptions(['stream' => true])
            ->asStream();

        foreach ($stream as $event) {
            if ($event->type() === StreamEventType::TextDelta) {
                yield ['delta' => $event->delta, 'done' => false];
            }
        }
        yield ['delta' => '', 'done' => true];
    }

    /**
     * Convert conversation history + current prompt into Prism Message objects.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, UserMessage|AssistantMessage>
     */
    private function buildPrismMessages(array $history, string $currentPrompt): array
    {
        $messages = [];

        // Trim to the last N messages to avoid blowing up the context window.
        $trimmed = array_slice($history, -self::MAX_HISTORY_MESSAGES);

        foreach ($trimmed as $msg) {
            if ($msg['role'] === 'user') {
                $messages[] = new UserMessage($msg['content']);
            } elseif ($msg['role'] === 'assistant') {
                $messages[] = new AssistantMessage($msg['content']);
            }
        }

        // The current question/prompt as the final user message.
        $messages[] = new UserMessage($currentPrompt);

        return $messages;
    }

    private function buildSqlSystemPrompt(array $schema): string
    {
        $schemaDescription = $this->buildSchemaDescription($schema);

        return <<<PROMPT
Eres un asistente SQL especializado en un sistema de contabilidad médica (ContaMed).
Tu tarea es convertir preguntas en español a consultas SQL MySQL válidas, o responder conversacionalmente cuando no se necesite SQL.

Reglas para SQL:
- Genera ÚNICAMENTE sentencias SELECT
- NO uses INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, CREATE, EXEC, UNION
- Usa solo las tablas y columnas proporcionadas en el esquema
- Usa el dialecto MySQL
- Responde con el esquema sql_response: type="sql" con el SQL en content, o type="conversational" con la respuesta en content
- Si la pregunta es un saludo o conversacional (no requiere datos), responde type="conversational"
- Si la pregunta requiere datos, responde type="sql" con la consulta SELECT en content
- NO incluyas el filtro empresa_id en el SQL; el sistema lo inyecta automáticamente. Solo generá el SELECT con las condiciones de negocio.
- Cuando respondas type="conversational", usá emojis para hacer la respuesta más visual: 👋 saludos, ❓ preguntas, 👍 confirmaciones

Relaciones entre tablas (USA JOINs cuando la pregunta necesite datos de tablas relacionadas):
- repases.clinica_id → clinicas.id  (para obtener el nombre/dirección/teléfono de la clínica)
- repase_examenes.repase_id → repases.id  (detalle de exámenes de un repase)
- repase_examenes.examen_id → examenes.id  (nombre del examen)
- gastos.repase_id → repases.id  (gastos de un repase)
- agendas.clinica_id → clinicas.id  (agendas de una clínica)

IMPORTANTE: Cuando el usuario pregunte por un repase, INCLUYE siempre un JOIN con clinicas para traer el nombre de la clínica. Ejemplo:
  SELECT r.*, c.nombre as clinica_nombre FROM repases r JOIN clinicas c ON r.clinica_id = c.id ORDER BY r.id DESC LIMIT 1

Cuando el usuario pregunte por exámenes de un repase, incluye JOIN con examenes para traer el nombre:
  SELECT re.*, e.nombre as examen_nombre FROM repase_examenes re JOIN examenes e ON re.examen_id = e.id WHERE re.repase_id = ?

Esquema de la base de datos:
{$schemaDescription}

Valores permitidos en columnas ENUM:
- gastos.tipo: 'doctor' (honorarios médicos), 'tecnico' (honorarios técnicos), 'laudos' (interpretación de estudios), 'gasolina' (combustible y transporte), 'extra' (gastos varios)
- repases.estado: 'pendiente', 'pagado'
- repases.tipo_precio: 'sin_nota' (precio sin factura), 'con_nota' (precio con factura)

Vocabulario del negocio — cuando el usuario mencione estos términos, traducilos al valor de BD correspondiente:
- "honorarios médicos" / "médicos" / "doctor" / "médico" → gastos.tipo = 'doctor'
- "honorarios técnicos" / "técnico" / "técnicos" → gastos.tipo = 'tecnico'
- "laudos" / "interpretación" / "laudo" → gastos.tipo = 'laudos'
- "gasolina" / "combustible" / "transporte" / "movilidad" → gastos.tipo = 'gasolina'
- "extras" / "extra" / "varios" / "otros gastos" → gastos.tipo = 'extra'
- "pagado" / "pagados" / "cobrado" → repases.estado = 'pagado'
- "pendiente" / "pendientes" / "sin cobrar" / "impago" → repases.estado = 'pendiente'
- "sin nota" / "sin factura" / "particular" → repases.tipo_precio = 'sin_nota'
- "con nota" / "con factura" / "facturado" → repases.tipo_precio = 'con_nota'

EJEMPLO: si el usuario pregunta "gasto más alto de honorarios médicos", generá:
  SELECT g.*, r.fecha, c.nombre AS clinica_nombre
  FROM gastos g
  JOIN repases r ON g.repase_id = r.id
  JOIN clinicas c ON r.clinica_id = c.id
  WHERE g.tipo = 'doctor'
  ORDER BY g.monto DESC LIMIT 1
PROMPT;
    }

    private function buildSqlUserPrompt(string $question, int $empresaId): string
    {
        return "Pregunta del usuario: \"{$question}\"\n\nEl usuario pertenece a la empresa_id={$empresaId}. El sistema inyectará el filtro empresa_id automáticamente; no lo agregues al SQL.";
    }

    private function buildFormatSystemPrompt(): string
    {
        return <<<PROMPT
Eres un asistente financiero que responde en español (rioplatense, natural).
Recibirás datos de una consulta SQL en JSON y la pregunta original del usuario.
Debes responder de forma natural y conversacional, como un analista financiero.

Reglas:
- Responde ÚNICAMENTE en español
- Sé conciso pero informativo
- Si los datos están vacíos, indica que no se encontraron resultados
- NO inventes datos que no estén en los resultados
- Formatea montos en pesos argentinos ($)
- Menciona fechas en formato dd/mm/yyyy
- Si los resultados incluyen clinica_nombre, menciona el nombre de la clínica
- Si los resultados incluyen examen_nombre, menciona los nombres de los exámenes
- Considera el historial de la conversación para responder coherentemente
- Usá emojis para ilustrar los datos clave:
  📅 para fechas, 💰 para montos, ✅ para "pagado", ⏳ para "pendiente",
  🏥 para clínicas, 👨‍⚕️ para doctores, 🔬 para exámenes,
  💸 para gastos, 📊 para totales, 📈 para tendencias,
  🆔 para IDs, 📝 para observaciones
PROMPT;
    }

    private function buildFormatUserPrompt(string $data, string $question): string
    {
        return "Pregunta del usuario: {$question}\n\nDatos obtenidos de la consulta SQL (JSON):\n{$data}";
    }

    /**
     * Load the real schema from the database (dynamic, not hardcoded).
     * Cached for 1 hour. Only whitelisted tables are exposed.
     */
    private function loadSchema(): array
    {
        $schema = Cache::get(self::SCHEMA_CACHE_KEY);

        if ($schema !== null) {
            return $schema;
        }

        $allowedTables = $this->sqlValidator->allowedTables();
        $schema = ['tables' => []];

        foreach ($allowedTables as $table) {
            if (Schema::hasTable($table)) {
                $columns = Schema::getColumnListing($table);
                if (!empty($columns)) {
                    $schema['tables'][$table] = $columns;
                }
            }
        }

        Cache::put(self::SCHEMA_CACHE_KEY, $schema, 3600);

        return $schema;
    }

    private function buildSchemaDescription(array $schema): string
    {
        $lines = [];
        foreach ($schema['tables'] as $table => $columns) {
            $lines[] = "- {$table}: " . implode(', ', $columns);
        }
        return implode("\n", $lines);
    }
}
