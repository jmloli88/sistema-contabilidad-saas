# Design: AI Chat Assistant (DeepSeek V4 Flash)

## Technical Approach

Synchronous MVC pipeline: user submits Spanish question via Alpine.js chat widget → ChatController validates → ChatService sends structured prompt to DeepSeek API → SqlValidatorService validates returned SQL → query executed on `mysql_ai_readonly` connection → ResponseFormatterService formats results in natural Spanish → JSON response. Caching via `Cache::remember()` with md5(question + empresa_id) key; rate limiting at route level (`throttle:30,1`).

## Architecture Decisions

| Decision | Choice | Rejected | Rationale |
|----------|--------|----------|-----------|
| Sync vs Async | Sync HTTP (60s timeout) | Job+polling | DeepSeek V4 Flash responds in 1–3s. Queue complexity unwarranted for MVP. |
| SQL defense | App regex + read-only DB user | SQL parser, PDO prepare | Two-layer defense-in-depth. Regex rejects DML/DDL; DB user has only SELECT grants. |
| Empresa scoping | App-level WHERE injection | MySQL session vars, Eloquent scopes | Session vars break SQLite tests. AI generates raw SQL, not Eloquent. |
| Caching | `Cache::remember()`, md5 key, 300s TTL | Redis | Database cache already configured and functional. Key: `chat_query_` . md5($question) . '_' . empresa_id. |
| Rate limiting | Route `throttle:30,1` + daily token budget via Cache | External rate limiter | Follows existing `throttle:60,1` pattern. Budget tracked via `Cache::increment()`. |

## Data Flow

```
User (Spanish question)
    │  Axios POST /api/chat
    ▼
ChatController  ──cache hit?──→ return cached JSON
    │ miss
    ▼
ChatService::generateSql()  ──HTTP POST──→ DeepSeek API
    │ raw SQL
    ▼
SqlValidatorService::validate(sql, empresaId)
    │ SELECT-only regex + table whitelist + WHERE injection
    ▼ clean SQL
DB::connection('mysql_ai_readonly')->select(sql)
    │ rows
    ▼
ResponseFormatterService::format(rows, question)
    │ Spanish text
    ▼
JSON response + cache store
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `config/services.php` | Modify | Add `deepseek` config: api_key, model, base_url |
| `.env.example` | Modify | Add `DEEPSEEK_API_KEY`, `DEEPSEEK_MODEL` |
| `config/database.php` | Modify | Add `mysql_ai_readonly` connection |
| `routes/api.php` | Modify | `POST /api/chat` with `auth`, `empresa.scope`, `throttle:30,1` |
| `app/Http/Controllers/Api/ChatController.php` | Create | Validates input, orchestrates services, returns JSON |
| `app/Services/Chat/ChatService.php` | Create | DeepSeek API via `Http::post()` with structured schema prompt |
| `app/Services/Chat/SqlValidatorService.php` | Create | Whitelist tables, SELECT-only, DML-block, empresa_id injection |
| `app/Services/Chat/ResponseFormatterService.php` | Create | DB rows → natural Spanish text |
| `resources/views/layouts/app.blade.php` | Modify | Include `<x-chat-widget />` before `@stack('scripts')` |
| `resources/views/components/chat-widget.blade.php` | Create | Floating button (Material Symbols `chat`) + 380px slide-up panel |
| `resources/js/chat-widget.js` | Create | Alpine.js: `show`, `messages[]`, `loading`, `ask()` → Axios POST |
| `resources/css/app.css` | Modify | Chat widget styles: fixed bottom-right, z-50, dark mode |
| `tests/Feature/Chat/ChatControllerTest.php` | Create | Integration: auth, scoping, cache, rate limit |
| `tests/Unit/SqlValidatorServiceTest.php` | Create | Regex DML rejection, whitelist, WHERE injection, SQLite+MySQL |
| `tests/Unit/ChatServiceTest.php` | Create | Mock `Http` facade, assert prompt structure |

## Interfaces / Contracts

```php
// ChatServiceInterface
public function generateSql(string $question): string;

// SqlValidatorServiceInterface
public function validate(string $rawSql, int $empresaId): string;

// ResponseFormatterServiceInterface
public function format(array $rows, string $originalQuestion): string;
```

API response follows existing `PredictiveApiController` pattern:
```json
{ "success": true, "data": { "answer": "...", "tokens_used": 280, "cached": false }, "meta": { "duration_ms": 1234 } }
```

## Testing Strategy

| Layer | What | Approach |
|-------|------|----------|
| Unit | SqlValidatorService regex | Table whitelist enforcement, DROP/INSERT/DELETE rejection, empresa_id WHERE injection |
| Unit | ChatService prompt | Mock `Http` facade, assert schema included in prompt |
| Integration | ChatController | `actingAs()`, assert 200/422/429, cache hit/miss, empresa isolation |
| Integration | Rate limiting | Assert 429 after exceeding throttle limit |

## Migration / Rollout

No data migration. No schema changes. Self-contained feature.

- **Rollback**: Remove route + widget include. No DB changes to reverse.
- **Feature flag** (optional): Wrap widget include in `config('services.deepseek.enabled')` check.

## Open Questions

- [ ] DeepSeek API key procurement — who owns the billing account?
- [ ] `mysql_ai_readonly` user — who creates the MySQL user in production?
- [ ] Daily token budget per empresa tier — MVP hardcode 50 queries/day/user
