# Tasks: Prism Migration

## Phase 1: Schema and Test Infrastructure
- [x] 1.1 Create `SqlResponseSchema` (Prism ObjectSchema subclass)
- [x] 1.2 Write `SqlResponseSchemaTest` (schema construction, serialization)

## Phase 2: Rewrite ChatQueryService
- [x] 2.1 Update constructor: remove `DeepSeekService`, inline Prism facade calls
- [x] 2.2 Update `processQuery()` for structured `{ type, content }` response handling
- [x] 2.3 Update system prompt (remove `-- NO_SQL:`, add schema instructions)
- [x] 2.4 Add retry-on-empty for structured responses

## Phase 3: Test Updates
- [x] 3.1 Rewrite `ChatQueryServiceTest` to mock Prism facade
- [x] 3.2 Remove `DeepSeekServiceTest` (service removed)

## Phase 4: Cleanup
- [x] 4.1 Remove `app/Services/AiChat/DeepSeekService.php`
- [x] 4.2 Update `config/services.php` (deprecate `services.deepseek`)
