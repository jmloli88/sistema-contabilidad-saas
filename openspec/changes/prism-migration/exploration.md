## Exploration: Prism Migration — Replace Custom DeepSeek HTTP with Prism Native Provider

### Current State

The AI chat pipeline makes two separate LLM calls to DeepSeek via raw HTTP:

1. **SQL Generation** (`DeepSeekService::generateSql()`): Sends question + DB schema + conversation context → receives raw SQL string. The response is parsed with regex for markdown code blocks and the `-- NO_SQL:` conversational marker.

2. **Response Formatting** (`DeepSeekService::formatResponse()`): Sends JSON query results + original question → receives conversational Spanish text.

Both use Laravel's `Http::post()` directly to `https://api.deepseek.com/v1/chat/completions`, reading credentials from `config/services.php` (`services.deepseek.*`). **Prism is already installed** (`echolabsdev/prism: ^0.100.1`) with a DeepSeek provider configured in `config/prism.php`, but the app bypasses it entirely.

### Affected Areas

| File | Status | Role |
|------|--------|------|
| `app/Services/AiChat/DeepSeekService.php` | **REPLACE** | Custom HTTP calls → remove or refactor to use Prism |
| `app/Services/AiChat/ChatQueryService.php` | **MODIFY** | Depends on `DeepSeekService` — inject Prism dependency instead |
| `app/Services/AiChat/SqlValidator.php` | **UNCHANGED** | No AI dependency, only SQL parsing/validation |
| `app/Http/Controllers/AiChatController.php` | **UNCHANGED** | Only depends on `ChatQueryService` — interface stays |
| `config/services.php` (`services.deepseek`) | **REMOVE or DEPRECATE** | Config moves to `config/prism.php` (already has `prism.providers.deepseek`) |
| `config/prism.php` | **VERIFY** | Already has DeepSeek provider config — may need model/temperature overrides |
| `tests/Feature/AiChatEndpointTest.php` | **UNCHANGED** | Mocks `ChatQueryService` at the boundary — insulated from internal changes |
| `.env` / `.env.example` | **MODIFY** | May need `PRISM_*` vars if not already present |

### Approaches

#### 1. Minimal Swap — Prism::text() replaces Http::post()

Keep the same two-step pipeline (generate SQL → format response). Keep `DeepSeekService` class but rewrite its internals to use `Prism::text()`. The prompts stay identical, the flow stays identical.

```php
$response = Prism::text()
    ->using('deepseek', 'deepseek-chat')
    ->withSystemPrompt($systemPrompt)
    ->withPrompt($userMessage)
    ->usingTemperature(0.1)
    ->withMaxTokens(1000)
    ->withClientOptions(['timeout' => 60])
    ->asText();
```

**What changes**: `DeepSeekService::callApi()` body, dependency injection (no more `Http` facade), config source shifts from `services.deepseek.*` to Prism's provider config.

| Pros | Cons | Effort |
|------|------|--------|
| Lowest risk, minimal surface area | Still manually parsing `-- NO_SQL:` with regex | **Low** |
| Quick to implement and test | Still two separate LLM calls | |
| Prism handles retries, timeout, error mapping | Doesn't leverage structured output | |
| Uses Prism's metrics/telemetry pipeline | The `extractSql()` regex stays in place | |
| Existing tests fully cover the flow | | |

---

#### 2. Structured Output — Prism::structured() for SQL Generation

Replace the free-text SQL generation with a typed schema. Define a discriminated union schema:

```php
class SqlResponseSchema extends ObjectSchema
{
    public function __construct()
    {
        parent::__construct(
            name: 'sql_response',
            description: 'SQL query or conversational response',
            properties: [
                new StringSchema('type', 'Either "sql" or "conversational"'),
                new StringSchema('content', 'The SQL query or conversational text'),
            ],
            requiredFields: ['type', 'content'],
        );
    }
}

// Usage:
$response = Prism::structured()
    ->using('deepseek', 'deepseek-chat')
    ->withSchema(new SqlResponseSchema())
    ->withSystemPrompt($systemPrompt)
    ->withPrompt($userMessage)
    ->usingTemperature(0.1)
    ->asStructured();

// $response->structured is now typed: { type: string, content: string }
```

The prompt can drop the `-- NO_SQL:` convention entirely — the schema enforces the structure. For the formatting step, keep `Prism::text()` (free-text Spanish response is fine without a schema).

| Pros | Cons | Effort |
|------|------|--------|
| Eliminates `-- NO_SQL:` parsing and regex extraction | Requires creating schema classes | **Medium** |
| Type-safe output — no more `$response['choices'][0]['message']['content']` | `Prism::structured()` may have different behavior than `::text()` for some providers | |
| Clearer boundary between SQL / conversational responses | Formatting step still uses `::text()` — mixed modes | |
| Prism handles JSON decoding and validation | More code to change in `ChatQueryService::processQuery()` | |
| Schema serves as living documentation | | |

---

#### 3. Function Calling — Single Prism Call with `query_database` Tool

One Prism call with a tool that the LLM can invoke. The tool validates + executes the SQL and returns results, then the LLM formats the final response in one multi-step conversation.

```php
$queryTool = (new Tool)
    ->as('query_database')
    ->for('Execute a SELECT query against the medical accounting database')
    ->withParameter(
        new StringSchema('sql', 'The SELECT SQL query to execute')
    )
    ->using(function (string $sql, array $args) use $empresaId {
        // This runs inside Prism's tool handling
        $sql = $this->sqlValidator->validate($sql);
        $scopedSql = $this->sqlValidator->injectEmpresaScope($sql, $empresaId);
        $results = DB::select($scopedSql);
        return json_encode($results);
    });

$response = Prism::text()
    ->using('deepseek', 'deepseek-chat')
    ->withSystemPrompt($systemPrompt)
    ->withPrompt($question)
    ->withTools([$queryTool])
    ->withMaxSteps(2)
    ->usingTemperature(0.1)
    ->asText();
```

**Flow**: LLM generates SQL → calls `query_database` → Prism executes tool (validate + run) → returns results to LLM → LLM formats final conversational response. All in one Prism request.

| Pros | Cons | Effort |
|------|------|--------|
| Single LLM interaction instead of two | Requires DeepSeek to reliably support function calling | **High** |
| Architecturally clean — LLM controls flow | `maxSteps` adds latency and token cost | |
| Prism handles the tool call lifecycle | Tool callback needs DI for validator/DB — requires container resolution or manual wiring | |
| No manual `-- NO_SQL:` parsing | Error handling is more complex (tool failure → LLM confusion) | |
| | Changes the fundamental flow — more testing surface | |
| | The `$empresaId` and `$sqlValidator` must be injected into a closure that Prism serializes | |

### Recommendation

**Approach 2 — Structured Output** for the SQL generation step, combined with `Prism::text()` for response formatting.

Rationale:
- Approach 1 (Minimal Swap) doesn't eliminate the brittleness of `-- NO_SQL:` parsing and `$response['choices'][0]...` access patterns. It's barely more than a rename of the HTTP client.
- Approach 3 (Function Calling) is the most elegant architecture but introduces too much risk for an initial migration: tool execution callbacks that need DI wiring, DeepSeek function calling reliability, and the multi-step flow adds complexity without proven benefit for this specific use case.
- Approach 2 hits the sweet spot: it eliminates the two most fragile parts of the current code (SQL extraction regex and the `-- NO_SQL:` convention) while keeping the flow simple and testable. It meaningfully leverages Prism's value proposition (structured output) rather than just swapping an HTTP client.

**Specific changes implied**:
1. Replace `DeepSeekService` with a `PrismDeepSeekService` (or inline Prism calls into `ChatQueryService`)
2. Define a `SqlResponseSchema` for structured SQL output
3. Refactor `ChatQueryService::processQuery()` to handle structured responses
4. Remove `services.deepseek` config in favor of `prism.providers.deepseek`
5. Keep `SqlValidator` and `AiChatController` untouched

### Risks

- **Prism::structured() behavior**: DeepSeek's structured output support may not be as mature as OpenAI's. Need to verify that `deepseek-chat` reliably returns structured JSON following the schema. If not, fall back to Approach 1 with manual response parsing.
- **Backward compatibility**: The `ChatQueryService::ask()` return signature must not change (the frontend expects `{ answer, tokens, chart_url?, excel_url? }`). The internal refactor must preserve this contract.
- **Token estimation**: Current `estimateTokens()` is a rough heuristic. Prism's `Response::usage` provides actual token counts from the API — this should replace the estimation, but needs careful testing to ensure the frontend handles the new values.
- **Retry logic**: Current `DeepSeekService` retries once on empty SQL. Prism's built-in retry (`withClientRetry`) covers HTTP-level failures but not "empty LLM response" semantic failures. Need explicit handling for the structured approach.

### Ready for Proposal

Yes. The exploration is complete and the analysis is grounded in actual code reading. The orchestrator should tell the user: "Three approaches identified. Recommended: Structured Output (Approach 2) — replaces `DeepSeekService` with Prism's native provider + structured schema, eliminating fragile regex parsing while keeping the pipeline simple. Open risk: DeepSeek structured output maturity needs verification."
