# Tasks: AI Chat Assistant (DeepSeek V4 Flash)

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 650-850 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (backend core) → PR 2 (endpoint) → PR 3 (frontend) |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Config + services + unit tests | PR 1 | base=main; SqlValidator, ChatService, ResponseFormatter |
| 2 | Controller + route + feature tests | PR 2 | base=main; depends on PR 1 |
| 3 | Frontend Alpine.js widget | PR 3 | base=main; independent of backend |

## Phase 1: Foundation

- [x] 1.1 Add `deepseek` config to `config/services.php` (api_key, model, base_url, max_tokens)
- [x] 1.2 Create `config/ai-chat.php` with AI chat settings (rate_limit, query_timeout, cache_ttl, daily_token_budget, allowed_tables, blocked_columns)
- [x] 1.3 Add `DEEPSEEK_API_KEY`, `DEEPSEEK_BASE_URL`, `DEEPSEEK_MODEL`, `AI_CHAT_RATE_LIMIT` to `.env.example`

## Phase 2: Core Services (TDD)

- [x] 2.1 RED: Write `tests/Unit/Services/SqlValidatorTest.php` — SELECT-only, DML/DROP rejection, whitelist, blocked columns, multi-statement, comment stripping, empresa scoping
- [x] 2.2 GREEN: Implement `app/Services/AiChat/SqlValidator.php` — validate() + injectEmpresaScope() with 22 passing tests
- [x] 2.3 RED: Write `tests/Unit/Services/DeepSeekServiceTest.php` — mock Http facade, assert prompt includes schema + empresa context, retry logic
- [x] 2.4 GREEN: Implement `app/Services/AiChat/DeepSeekService.php` — structured prompt, DeepSeek HTTP call, retry-on-fail
- [x] 2.5 RED: Write `tests/Unit/Services/ChatQueryServiceTest.php` — mocked dependencies, full pipeline
- [x] 2.6 GREEN: Implement `app/Services/AiChat/ChatQueryService.php` — full pipeline: generate SQL → validate → scope → execute → format → cache

## Phase 3: API Endpoint

- [x] 3.1 RED: Write `tests/Feature/AiChatEndpointTest.php` — auth 200, unauth 401, rate limit 429, empty question 422
- [x] 3.2 GREEN: Create `app/Http/Controllers/AiChatController.php` — validate, orchestrate ChatQueryService, return JSON
- [x] 3.3 GREEN: Register `POST /api/chat/ask` in `routes/web.php` — inside auth group with throttle:30,1

## Phase 4: Frontend Widget

- [x] 4.1 Create `resources/views/components/ai-chat-widget.blade.php` — floating button + chat panel with inline Alpine.js
- [x] 4.2 Add Alpine.js `aiChat` data with `sendMessage()`, CSRF handling, error states, and auto-scroll
- [x] 4.3 Include `<x-ai-chat-widget />` before `@stack('scripts')` in `layouts/app.blade.php`
- [x] 4.4 Add PII disclosure warning in Spanish at the bottom of the chat panel
- [x] 4.5 Auto-scroll messages container on new messages via `$refs.messages.scrollTop` in `$nextTick`
