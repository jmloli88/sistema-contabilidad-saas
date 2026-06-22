# Proposal: Prism Migration — Replace Custom DeepSeek HTTP with Prism Native Provider

## Intent

Replace the current custom HTTP calls to the DeepSeek API (`DeepSeekService`, which uses `Http::post()` directly) with Prism's native DeepSeek provider and structured output (`Prism::structured()`), eliminating fragile regex-based SQL extraction and the `-- NO_SQL:` convention while keeping the existing two-step pipeline (SQL generation → response formatting).

## Scope

### In Scope

- Replace `DeepSeekService` with Prism provider calls (either a new `PrismDeepSeekService` or inlined Prism calls in `ChatQueryService`)
- Define a `SqlResponseSchema` (Prism structured schema) for typed SQL / conversational output
- Refactor `ChatQueryService::processQuery()` to handle structured responses instead of `-- NO_SQL:` parsing
- Remove `services.deepseek` config; rely on `prism.providers.deepseek` (already present in `config/prism.php`)
- Verify `config/prism.php` DeepSeek provider config and add model/temperature overrides if needed
- Keep `SqlValidator` and `AiChatController` untouched
- Preserve the `ChatQueryService::ask()` return contract (`{ answer, tokens, chart_url?, excel_url? }`)
- Prism `::text()` retains the `formatResponse` step (free-text Spanish formatting)
- Token estimation heuristic (`estimateTokens()`) replaced with actual Prism response `usage` data where available

### Out of Scope

- **Function calling** (Approach 3 from exploration): Single-call tool execution pattern. Deferred — requires DeepSeek function-calling maturity validation and changes the fundamental pipeline.
- **Prism::text() → Prism::structured() for the formatting step**: The response formatting stays free-text for natural conversational output.
- **UI or frontend changes**: The Alpine.js chat widget, routes, and controller remain identical.
- **Prism provider for other AI features**: Only the chat pipeline is migrated.
- **Caching strategy changes**: Current caching at `ChatQueryService` level remains unchanged.
- **New model/LLM provider**: Same DeepSeek model, same temperature. Only the transport layer changes.

## Capabilities

### Modified Capabilities

- `ai-chat-query` (SQL generation): Transport changes from `Http::post()` → `Prism::structured()`. Output format changes from raw text + regex → typed schema. Config source changes from `services.deepseek.*` → `prism.providers.deepseek`.

### Removed Capabilities

- `direct-http-deepseek` (custom HTTP integration): The hand-rolled `callApi()`, `extractSql()`, and `buildSqlPrompt()` methods are removed or replaced by Prism's abstractions.

## Approach

**Structured Output with Prism (Approach 2 from exploration):**

1. **Prism::structured() for SQL generation**: Define a discriminated union schema (`SqlResponseSchema`) with `type: "sql" | "conversational"` and `content: string`. The LLM returns typed output; no more regex parsing of `-- NO_SQL:` markers.
2. **Prism::text() for response formatting**: Keep free-text for the formatting step (Spanish conversation doesn't need a schema).
3. **Config migration**: `services.deepseek.*` is deprecated; Prism already has `prism.providers.deepseek` configured. Add model/temperature overrides if needed.
4. **Interface preservation**: `ChatQueryService::ask()` outward contract unchanged.

Rationale (from exploration):
- Approach 1 (Minimal Swap) doesn't eliminate regex/`-- NO_SQL:` brittleness
- Approach 3 (Function Calling) adds too much risk for an initial migration
- Approach 2 eliminates the two most fragile parts while meaningfully leveraging Prism's value

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Services/AiChat/DeepSeekService.php` | Replace | Custom HTTP calls → use Prism::structured() for SQL, Prism::text() for formatting |
| `app/Services/AiChat/ChatQueryService.php` | Modify | Inject Prism dependency instead of DeepSeekService; handle typed responses |
| `app/Services/AiChat/SqlValidator.php` | Unchanged | No AI dependency — only SQL parsing/validation |
| `app/Http/Controllers/AiChatController.php` | Unchanged | Only depends on `ChatQueryService` — interface stays |
| `app/Services/AiChat/SqlResponseSchema.php` | New | Prism structured schema definition |
| `config/services.php` (`services.deepseek`) | Remove/Deprecate | Config moves to `config/prism.php` (already has `prism.providers.deepseek`) |
| `config/prism.php` | Verify/Modify | May need model/temperature overrides for DeepSeek provider |
| `.env` / `.env.example` | Modify | May need `PRISM_*` vars if not already present |
| `tests/Feature/AiChatEndpointTest.php` | Unchanged | Mocks `ChatQueryService` at the boundary — insulated from internal changes |
| `tests/Unit/PrismDeepSeekServiceTest.php` | New | Unit tests for Prism integration |
| `tests/Unit/SqlResponseSchemaTest.php` | New | Schema construction and validation |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| **Prism::structured() behavior with DeepSeek**: DeepSeek's structured output support may not be as mature as OpenAI's | Medium | Verify with a smoke test; fallback to Approach 1 (Prism::text() + manual JSON parse) if structured output fails |
| **Backward compatibility break**: `ChatQueryService::ask()` return signature must not change | Low | The controller tests (`AiChatEndpointTest`) mock at the boundary — they'll catch signature drift |
| **Token estimation**: Current `estimateTokens()` is a rough heuristic; Prism's `usage` data from the API may have different shape | Low | Use Prism `Response::usage` where available; keep heuristic as fallback |
| **Retry logic**: Current `DeepSeekService` retries once on empty SQL. Prism's `withClientRetry` covers HTTP failures, not semantic empty responses | Medium | Keep explicit retry logic for the "empty structured response" case outside Prism's retry |
| **DeepSeek provider config drift**: `prism.providers.deepseek` exists but may lack model/temperature overrides | Low | Inspect and set explicit model/temperature in the provider config or at the call site |
| **Prism contract compatibility**: Prism ^0.100.1 API may differ from documented examples | Medium | Pin to the installed version; use actual `Prism` facade methods observed in the codebase |

## Rollback Plan

1. Restore `DeepSeekService.php` from git (`git checkout HEAD -- app/Services/AiChat/DeepSeekService.php`)
2. Restore `ChatQueryService.php` from git
3. Remove `SqlResponseSchema.php`
4. Revert `config/prism.php` changes (if any)
5. Revert `.env` / `.env.example` changes (if any)
6. Run `php artisan test --parallel` — all existing tests must pass
7. Delete `tests/Unit/PrismDeepSeekServiceTest.php` and `tests/Unit/SqlResponseSchemaTest.php`

No database migration. No schema changes. Pure code-level refactor.

## Dependencies

- `echolabsdev/prism: ^0.100.1` — already installed (`composer.json`)
- DeepSeek provider configured in `config/prism.php` — already present
- No new Composer packages required

## Success Criteria

- [ ] `Prism::structured()` with `SqlResponseSchema` returns typed `{ type, content }` — no regex parsing
- [ ] Conversational responses (no SQL needed) go through the same schema with `type: "conversational"`
- [ ] SQL generation retry-on-empty works (one retry if LLM returns empty content)
- [ ] Response formatting uses `Prism::text()` and returns natural Spanish
- [ ] `ChatQueryService::ask()` return contract unchanged: `{ answer, tokens, chart_url?, excel_url? }`
- [ ] `services.deepseek` config is deprecated (can be removed safely)
- [ ] `php artisan test --parallel --stop-on-failure` passes all existing tests
- [ ] New unit tests cover: schema construction, Prism call structure, structured response handling
