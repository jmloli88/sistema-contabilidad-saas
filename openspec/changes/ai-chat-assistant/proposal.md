# Proposal: AI Chat Assistant (DeepSeek V4 Flash)

## Intent

Enable Spanish-speaking users to ask natural-language questions about their financial data and receive answers in Spanish. The assistant converts questions → validated SQL → executes on a read-only connection → formats results conversationally.

## Scope

### In Scope
- DeepSeek V4 Flash API integration with structured prompting
- SQL validator: SELECT-only regex, table whitelist, empresa_id injection
- Read-only DB user + `mysql_ai_readonly` connection
- Alpine.js chat widget (floating button, slide-in panel, loading state)
- `POST /api/chat` endpoint (Sanctum-auth, empresa-scoped, throttled)
- Response caching (5-min TTL per question+empresa combo)
- Daily token budget tracking per user
- TDD: Feature + Unit tests

### Out of Scope
- Async queue processing for complex queries (v2)
- Multi-turn conversation (single Q&A per request)
- Chat log persistence (optional schema deferred)

## Capabilities

### New Capabilities
- `ai-chat-query`: Natural-language question → validated SQL → formatted Spanish response via DeepSeek API
- `ai-chat-ui`: Floating chat widget (Alpine.js + TailwindCSS + Material Symbols)
- `sql-validator`: SELECT-only SQL validation with table whitelist and empresa scoping

### Modified Capabilities
None — all net-new functionality.

## Approach

**Synchronous HTTP via Guzzle** (Option 1 from exploration). User question → `ChatService` builds prompt with schema → DeepSeek API → `SqlValidatorService` validates → execute on read-only connection → `ResponseFormatterService` renders Spanish. 60s timeout. Rejected SQL retries once with error. Cached via Laravel Cache (database store, 5-min TTL).

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `config/services.php` | Modified | Add `deepseek` key + budget config |
| `.env` / `.env.example` | Modified | `DEEPSEEK_API_KEY`, `DEEPSEEK_MODEL` |
| `config/database.php` | Modified | Add `mysql_ai_readonly` connection |
| `routes/api.php` | Modified | `POST /api/chat`, `throttle:10,1` |
| `app/Http/Controllers/Api/ChatController.php` | New | Endpoint handler |
| `app/Services/Chat/ChatService.php` | New | DeepSeek API interaction |
| `app/Services/Chat/SqlValidatorService.php` | New | Whitelist + empresa_id injection |
| `app/Services/Chat/ResponseFormatterService.php` | New | Natural Spanish formatting |
| `resources/views/components/chat-widget.blade.php` | New | Chat widget component |
| `resources/js/chat-widget.js` | New | Alpine.js component |
| `resources/css/app.css` | Modified | Widget styles (dark-mode) |
| `resources/views/layouts/app.blade.php` | Modified | Include widget |
| `tests/Feature/Chat/` | New | Feature tests |
| `tests/Unit/Services/Chat/` | New | Unit tests |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| SQL injection via AI | Low | SELECT-only regex + whitelist + empresa_id injection + read-only DB user |
| AI hallucination (wrong SQL) | Medium | Structured prompts with schema examples; retry once on syntax fail |
| Latency (1-5s) | High | Loading state UI; caching; async fallback planned v2 |
| Cost overrun | Medium | Daily token budget; `throttle:10,1`; caching |
| PII in free-text fields | Medium | UI warning; future: field-level exclusions |

## Rollback Plan

- Remove `DEEPSEEK_API_KEY` from `.env`
- Comment out route in `routes/api.php`
- Remove `@include('components.chat-widget')` from layout
- Revert `config/database.php` read-only connection
- No DB schema changes to revert (MVP)

## Dependencies

- DeepSeek API key (platform.deepseek.com)
- Guzzle already available via Laravel HTTP facade — no new packages

## Success Criteria

- [ ] Spanish question → correct Spanish answer with empresa-scoped data only
- [ ] Validator rejects non-SELECT, non-whitelisted, or cross-empresa queries
- [ ] Read-only DB user cannot execute writes even if validator bypassed
- [ ] Widget renders correctly in light and dark modes
- [ ] Rate limiting prevents abuse (10 req/min per user)
- [ ] `php artisan test --parallel --stop-on-failure` passes all tests
