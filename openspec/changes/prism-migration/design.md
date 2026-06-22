# Design: Prism Migration — Replace Custom DeepSeek HTTP with Prism Native Provider

## Technical Approach

**Structured Output Migration (Approach 2 from exploration).** The existing two-step pipeline (SQL generation → response formatting) is preserved, but:
1. SQL generation moves from `Http::post()` + `extractSql()` regex → `Prism::structured()` with a typed `SqlResponseSchema`
2. Response formatting moves from `Http::post()` + raw JSON access → `Prism::text()` (free-text)
3. Config moves from `services.deepseek.*` → `prism.providers.deepseek`
4. `DeepSeekService` is replaced by a `PrismChatService` (or inlined directly into `ChatQueryService`)

The `-- NO_SQL:` convention, markdown code-block regex, and `$response['choices'][0]['message']['content']` access patterns are eliminated entirely for the SQL generation step.

## Architecture Decisions

| Decision | Choice | Rejected | Rationale |
|----------|--------|----------|-----------|
| **SQL generation transport** | `Prism::structured()` | `Prism::text()` | Structured output eliminates `-- NO_SQL:` parsing and regex extraction. Schema guarantees typed `{ type, content }`. |
| **Response formatting transport** | `Prism::text()` | `Prism::structured()` | Spanish conversational text doesn't benefit from a schema. Free-text is simpler and matches the output domain. |
| **Service class strategy** | Inline Prism calls in `ChatQueryService` | New `PrismChatService` wrapper | The existing `DeepSeekService` is thin (essentially two methods + `callApi()`). Inlining removes an unnecessary indirection layer. The constructor changes from `DeepSeekService` to Prism facade directly. |
| **Config source** | `prism.providers.deepseek` | Keep `services.deepseek.*` | Prism already has the provider configured. Dual sources create drift risk. Single source of truth. |
| **Retry strategy** | Explicit retry for empty structured responses | Prism `withClientRetry` alone | `withClientRetry` covers HTTP-level failures (5xx, connection errors), but "LLM returned empty content" is a semantic failure. The explicit retry outside Prism's retry loop handles that. |
| **Token tracking** | Prism `Response::usage` | Heuristic only | Prism returns actual token counts from the API. Use them when available; keep heuristic as fallback. |
| **Model specification** | Explicit at call site | Provider default | Explicit `'deepseek-chat'` model + `0.1` temperature at each call site prevents config-drift surprises. |

## Data Flow

### Before (current state)

```
User (Spanish question)
    │  Axios POST /api/chat/ask
    ▼
AiChatController                                  ── UNCHANGED ──
    │
    ▼
ChatQueryService::ask()
    │
    ▼
ChatQueryService::processQuery()
    │
    ├── DeepSeekService::generateSql()            ── Http::post() ──► DeepSeek API
    │       │ raw text response
    │       ▼
    │   extractSql() regex                        ◄── strips ```sql + -- NO_SQL: marker
    │
    ├── SqlValidator::validate()                  ── UNCHANGED ──
    │
    ├── injectEmpresaScope() + DB::select()       ── UNCHANGED ──
    │
    └── DeepSeekService::formatResponse()         ── Http::post() ──► DeepSeek API
            │ text response
            ▼
        return { answer, tokens }
```

### After (target state)

```
User (Spanish question)
    │  Axios POST /api/chat/ask
    ▼
AiChatController                                  ── UNCHANGED ──
    │
    ▼
ChatQueryService::ask()
    │
    ▼
ChatQueryService::processQuery()
    │
    ├── Prism::structured(SqlResponseSchema)      ── Prism DeepSeek provider ──► DeepSeek API
    │       │ typed { type: "sql"|"conversational", content: string }
    │       ▼
    │   if type === "conversational" ──► return { answer: content, tokens }
    │   if type === "sql" ──► proceed to validator
    │
    ├── SqlValidator::validate()                  ── UNCHANGED ──
    │
    ├── injectEmpresaScope() + DB::select()       ── UNCHANGED ──
    │
    └── Prism::text()                             ── Prism DeepSeek provider ──► DeepSeek API
            │ text response
            ▼
        return { answer, tokens, chart_url?, excel_url? }
```

### Sequence: SQL Question Flow

```
┌─────────┐   ┌──────────────┐   ┌──────────────┐   ┌──────────────┐   ┌──────────┐   ┌───────────────┐
│  User   │   │ ChatController│   │ChatQuerySvc  │   │   Prism      │   │SqlValid. │   │  DB (read)    │
└────┬────┘   └──────┬───────┘   └──────┬───────┘   └──────┬───────┘   └────┬─────┘   └──────┬────────┘
     │ POST /ask      │                  │                  │               │                │
     │───────────────►│                  │                  │               │                │
     │                │ ask()            │                  │               │                │
     │                │─────────────────►│                  │               │                │
     │                │                  │structured()     │               │                │
     │                │                  │ with Schema     │               │                │
     │                │                  │─────────────────►│               │                │
     │                │                  │                  │               │                │
     │                │                  │  { type:"sql",   │               │                │
     │                │                  │    content: "SELECT..." }        │                │
     │                │                  │◄─────────────────│               │                │
     │                │                  │                  │               │                │
     │                │                  │  validate(sql)   │               │                │
     │                │                  │─────────────────────────────────►│                │
     │                │                  │  { valid: true } │               │                │
     │                │                  │◄─────────────────────────────────│                │
     │                │                  │                  │               │                │
     │                │                  │  injectScope +   │               │                │
     │                │                  │  DB::select()    │               │                │
     │                │                  │─────────────────────────────────────────────────►│
     │                │                  │  results[]       │               │                │
     │                │                  │◄─────────────────────────────────────────────────│
     │                │                  │                  │               │                │
     │                │                  │  text()          │               │                │
     │                │                  │  withSystemPrompt│               │                │
     │                │                  │─────────────────►│               │                │
     │                │                  │  Spanish text    │               │                │
     │                │                  │◄─────────────────│               │                │
     │                │                  │                  │               │                │
     │                │  { answer, tokens }                 │               │                │
     │                │◄─────────────────│                  │               │                │
     │   200 JSON     │                  │                  │               │                │
     │◄───────────────│                  │                  │               │                │
┌────┴────┐   ┌──────┴───────┐   ┌──────┴───────┐   ┌──────┴───────┐   ┌────┴─────┐   ┌──────┴────────┐
│  User   │   │ ChatController│   │ChatQuerySvc  │   │   Prism      │   │SqlValid. │   │  DB (read)    │
└─────────┘   └──────────────┘   └──────────────┘   └──────────────┘   └──────────┘   └───────────────┘
```

### Sequence: Conversational (No SQL) Flow

```
┌─────────┐   ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│  User   │   │ ChatController│   │ChatQuerySvc  │   │   Prism      │
└────┬────┘   └──────┬───────┘   └──────┬───────┘   └──────┬───────┘
     │ POST /ask      │                  │                  │
     │───────────────►│                  │                  │
     │                │ ask()            │                  │
     │                │─────────────────►│                  │
     │                │                  │structured()      │
     │                │                  │ with Schema      │
     │                │                  │─────────────────►│
     │                │                  │                  │
     │                │                  │ { type:"conversational",
     │                │                  │   content:"¡Hola!..." }
     │                │                  │◄─────────────────│
     │                │                  │                  │
     │                │                  │ (skip validator,
     │                │                  │  skip SQL exec,
     │                │                  │  skip formatting)
     │                │                  │                  │
     │                │  { answer: "¡Hola!...", tokens }   │
     │                │◄─────────────────│                  │
     │   200 JSON     │                  │                  │
     │◄───────────────│                  │                  │
┌────┴────┐   ┌──────┴───────┐   ┌──────┴───────┐   ┌──────┴───────┐
│  User   │   │ ChatController│   │ChatQuerySvc  │   │   Prism      │
└─────────┘   └──────────────┘   └──────────────┘   └──────────────┘
```

## SqlResponseSchema

```php
<?php

namespace App\Services\AiChat;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class SqlResponseSchema extends ObjectSchema
{
    public function __construct()
    {
        parent::__construct(
            name: 'sql_response',
            description: 'SQL query or conversational response',
            properties: [
                new StringSchema(
                    name: 'type',
                    description: 'Either "sql" or "conversational"'
                ),
                new StringSchema(
                    name: 'content',
                    description: 'The SQL query or conversational text'
                ),
            ],
            requiredFields: ['type', 'content'],
        );
    }
}
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Services/AiChat/DeepSeekService.php` | Remove | All logic replaced by Prism calls and `SqlResponseSchema` |
| `app/Services/AiChat/ChatQueryService.php` | Modify | Constructor: `DeepSeekService $deepSeek` → Prism facade. `processQuery()`: structured response handling instead of `-- NO_SQL:` + regex. Token tracking: Prism `Response::usage` when available. |
| `app/Services/AiChat/SqlResponseSchema.php` | Create | Prism `ObjectSchema` subclass for discriminated union |
| `config/services.php` (`services.deepseek.*`) | Remove/Deprecate | No longer used at runtime |
| `config/prism.php` | Modify | Add explicit model/temperature overrides for `deepseek` provider if needed |
| `.env` / `.env.example` | Modify | Add `PRISM_REQUEST_TIMEOUT=60` if not present. Existing `DEEPSEEK_API_KEY` already maps to `prism.providers.deepseek.api_key` via the configured env var. |
| `tests/Unit/PrismChatIntegrationTest.php` | Create | Test Prism calls with schema, mock Prism facade |
| `tests/Unit/SqlResponseSchemaTest.php` | Create | Schema construction, serialization, required fields |
| `tests/Feature/AiChatEndpointTest.php` | Unchanged | Mocks `ChatQueryService` — insulated from internal refactor |

## Interfaces / Contracts

**Preserved contract — `ChatQueryService::ask()`:**
```php
public function ask(string $question, int $empresaId, int $userId): array
// Returns: ['answer' => string, 'tokens' => int, 'chart_url'?: string, 'excel_url'?: string]
```

**Prism calls (inline in ChatQueryService):**

SQL generation:
```php
use Prism\Prism\Facades\Prism;

// Structured output for SQL
$response = Prism::structured()
    ->using('deepseek', 'deepseek-chat')
    ->withSchema(new SqlResponseSchema())
    ->withSystemPrompt($systemPrompt)
    ->withPrompt($userMessage)
    ->usingTemperature(0.1)
    ->withMaxTokens(1000)
    ->withClientOptions(['timeout' => 60])
    ->asStructured();

// $response->structured is typed: SqlResponse { type: string, content: string }
```

Response formatting:
```php
$response = Prism::text()
    ->using('deepseek', 'deepseek-chat')
    ->withSystemPrompt($systemPrompt)
    ->withPrompt($userMessage)
    ->usingTemperature(0.1)
    ->asText();
```

## Prompt Adaptation

The system prompt for SQL generation (`buildSqlPrompt()`) MUST be updated:

- **REMOVE**: `-- NO_SQL:` convention instructions and markers
- **REMOVE**: Markdown code-block instructions
- **ADD**: Instruction to use the `sql_response` schema — return `{ type: "sql", content: "SELECT..." }` or `{ type: "conversational", content: "¡Hola!" }`
- **KEEP**: All security rules (SELECT-only, empresa scoping, table whitelist, JOIN paths, MySQL dialect)

The response formatting prompt (`buildFormatPrompt()`) remains unchanged — it uses `Prism::text()` and the same system prompt.

## Testing Strategy

| Layer | What | Approach |
|-------|------|----------|
| Unit | `SqlResponseSchema` | Assert schema name, required fields, property types |
| Unit | `ChatQueryService` with Prism mock | Mock `Prism::structured()` and `Prism::text()` facades. Assert `->using('deepseek', 'deepseek-chat')` called with correct params. Assert structured response handled correctly for both `"sql"` and `"conversational"` types. |
| Integration | `AiChatEndpoint` | Unchanged — mocks `ChatQueryService` at boundary, passes `--parallel` |
| Manual | DeepSeek structured output compatibility | Call `Prism::structured()` with `SqlResponseSchema` against real DeepSeek API to verify schema compliance |

## Migration / Rollout

No data migration. No schema changes. Pure code-level refactor.

**Migration steps**:
1. Create `SqlResponseSchema.php`
2. Modify `ChatQueryService.php` (constructor + processQuery)
3. Remove `DeepSeekService.php`
4. Update prompts in `ChatQueryService` (remove `-- NO_SQL:` instructions)
5. Update `config/prism.php` if needed
6. Create unit tests
7. Run `php artisan test --parallel --stop-on-failure`
8. Remove `services.deepseek.*` from `config/services.php`

**Rollback**:
1. `git checkout HEAD -- app/Services/AiChat/DeepSeekService.php app/Services/AiChat/ChatQueryService.php`
2. Remove `SqlResponseSchema.php`
3. Revert `config/prism.php` and `.env` changes
4. Delete new test files
5. Run `php artisan test --parallel --stop-on-failure`

## Open Questions

- [ ] Does DeepSeek's API reliably support structured output via the Prism `deepseek` provider? Needs a manual smoke test before implementation.
- [ ] Does the installed Prism `^0.100.1` expose `withClientOptions(['timeout' => 60])` or is it `withClientTimeout(60)`? The actual method name needs verification against the installed version.
- [ ] Does `Prism::structured()` return `$response->structured` or `$response->data`? The property name needs verification against the installed version.
- [ ] Does Prism provide `$response->usage` with `totalTokens` or needs `promptTokens + completionTokens`? Verify against installed version.
