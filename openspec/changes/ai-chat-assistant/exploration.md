## Exploration: AI Chat Assistant (DeepSeek V4 Flash)

### Current State

**Tech Stack:**
- Laravel 12, PHP 8.2, MySQL (prod) / SQLite (test)
- Alpine.js 3 + Flowbite + TailwindCSS 3 frontend
- Material Symbols already loaded in `layouts/app.blade.php`
- Guzzle HTTP client available via Laravel's `Illuminate\Support\Facades\Http`
- Database-driven queue (`QUEUE_CONNECTION=database`) — already used for predictive jobs
- Database-driven cache (`CACHE_STORE=database`) — supports Redis fallback
- Sanctum auth for API routes, standard session auth for web

**Existing Patterns:**
- **Services layer** — business logic lives in `app/Services/` (DashboardService, RepaseService, etc.)
- **Contracts pattern** — `app/Contracts/` with interfaces for predictive module
- **Jobs** — `app/Jobs/` for async processing (CleanPredictiveCacheJob, UpdatePredictiveModelsJob)
- **Empresa scoping** — `EmpresaContext` singleton + `ScopeByEmpresa` middleware + `ScopedByEmpresa` Eloquent trait
- **API routes** — `routes/api.php` uses `throttle:60,1` for rate limiting
- **Config** — `config/services.php` follows the standard Laravel pattern for API keys (Stripe, Postmark, etc.)
- **Middleware aliases** — defined in `bootstrap/app.php`: `admin`, `subscription`, `empresa.scope`, `prevent.duplicate.submissions`

**No API key for DeepSeek exists** in `.env` or `config/services.php`.

### Affected Areas

- `config/services.php` — Add DeepSeek API key config
- `.env` / `.env.example` — Add `DEEPSEEK_API_KEY` and `DEEPSEEK_MODEL` vars
- `routes/api.php` — Add chat endpoint (authenticated + empresa-scoped)
- `app/Http/Controllers/Api/ChatController.php` — New controller for chat endpoint
- `app/Services/Chat/ChatService.php` — DeepSeek API interaction
- `app/Services/Chat/SqlValidatorService.php` — SQL whitelist + SELECT-only validation
- `app/Services/Chat/ResponseFormatterService.php` — Natural-language Spanish formatter
- `app/Jobs/ChatQueryJob.php` — Optional async job for complex queries
- `app/Models/` — No changes needed (already scoped by empresa)
- `resources/views/layouts/app.blade.php` — Add chat widget floating button
- `resources/views/components/chat-widget.blade.php` — New chat widget component
- `resources/js/chat-widget.js` — Alpine.js component for chat widget
- `resources/css/app.css` — Chat widget styles
- `database/migrations/` — Optional: chat cache table, chat_log table
- `tests/Feature/Chat/` — New test files

### Approaches

1. **Synchronous API call via HTTP Client** — Simplest: user asks question → POST to Laravel → Laravel calls DeepSeek API → validates SQL → executes → formats response
   - Pros: Simple, fast for basic queries, no queue overhead
   - Cons: Blocks the request while AI generates response (2-5s), potential timeout issues
   - Effort: Low
   - Cost: Pay-per-token via DeepSeek API (V4 Flash is very affordable)

2. **Async job with polling** — Submit query via job → return job ID → poll for completion
   - Pros: Non-blocking, can handle long-running queries, retry logic
   - Cons: More complex UI (polling/SSE), overkill for simple questions
   - Effort: Medium

3. **Hybrid: sync for simple queries, async queue for complex** — Detect query complexity client-side, use simple AJAX for quick questions, queue for multi-table joins or aggregations
   - Pros: Best UX for both cases
   - Cons: Two code paths, complexity in deciding threshold
   - Effort: Medium-High

### Recommended Approach

**Option 1 (Synchronous) with 60-second timeout**, at least for MVP. DeepSeek V4 Flash is extremely fast (~1-3s typical response). We can add async fallback later if users report timeout issues.

#### Security Model

```
question (Spanish)
    │
    ▼
[1] Prompt template → DeepSeek API → raw SQL
    │
    ▼
[2] SQL Validator:
    ├── MUST be SELECT only (regex: /^\s*SELECT\b/i)
    ├── MUST only reference whitelisted tables
    ├── MUST NOT contain: DROP, DELETE, INSERT, UPDATE, ALTER, TRUNCATE, CREATE, EXEC, UNION
    ├── MUST be syntactically valid (EXPLAIN or PREPARE)
    └── MUST have empresa_id filter injected
    │
    ▼
[3] Execute via read-only DB connection
    │
    ▼
[4] Format results → natural Spanish response
```

**Whitelist Tables (AI-readable):**
| Table | Columns Exposed | Notes |
|-------|----------------|-------|
| `clinicas` | id, nombre, direccion, telefono, empresa_id | PII in direccion/telefono — OK for AI |
| `examenes` | id, nombre, precio_sin_nota, precio_con_nota, empresa_id, is_active | Pricing data |
| `repases` | id, clinica_id, fecha, fecha_pago, estado, tipo_precio, total_examenes, total_consultas, total_gastos, total_neto, observaciones | Core financial data |
| `repase_examenes` | id, repase_id, examen_id, cantidad, precio_unitario_usado, subtotal | Line items |
| `gastos` | id, repase_id, tipo, descripcion, monto | Expenses |
| `empresas` | id, nombre | Only the user's own empresa |
| `agendas` | id, clinica_id, fecha, hora_inicio, hora_fin, doctor | Calendar data |

**Tables STRICTLY EXCLUDED:**
- `users` — password hashes, remember_token, email
- `saas_admins` — admin credentials
- `subscriptions`, `subscription_items` — Stripe billing
- `personal_access_tokens` — API tokens
- `sessions`, `cache`, `cache_locks` — internal state
- `jobs`, `job_batches`, `failed_jobs` — queue internals
- `prediction_cache`, `prediction_accuracy_log`, `prediction_configuration*` — internal prediction data
- `migrations` — schema metadata
- `password_reset_tokens` — auth tokens

**Empresa Scoping:**
- Every whitelist table (except `repase_examenes` and `gastos`) has an `empresa_id` or links through `clinicas.empresa_id`
- The SQL validator MUST rewrite generated SQL to add `WHERE empresa_id = {current}` and/or `JOIN clinicas ON repases.clinica_id = clinicas.id AND clinicas.empresa_id = {current}`
- Alternative: Execute query within a Laravel DB::transaction that sets a session variable, or use a read-only DB user + app-level WHERE injection

**Read-Only Database User:**
- Create a MySQL user `ai_reader@'%'` with ONLY `SELECT` privileges
- Define a new database connection `mysql_ai_readonly` in `config/database.php`
- All AI-generated SQL executes on this connection — even if the AI generates an UPDATE/DELETE, the user has no write permissions

**Rate Limiting:**
- Apply `throttle:10,1` or `throttle:30,1` per user on the chat endpoint
- Consider daily token budget per empresa tier (configurable in `config/services.php`)
- Track via `Cache::remember('chat_usage_' . auth()->id(), 86400, ...)` for daily stats

**Caching Strategy:**
- Cache exact same question+empresa combo for 5 minutes
- Cache key: `chat_query_` . md5($question) . '_' . empresa_id
- TTL: 300 seconds (configurable)
- Use Laravel's Cache facade (currently database-backed, supports Redis)

**Cost Estimation (DeepSeek V4 Flash):**
- Input: ~500-1000 tokens per prompt (system prompt + question)
- Output: ~100-300 tokens per response (SQL + natural language)
- Cost: ~$0.01-0.03 per query at current DeepSeek V4 Flash pricing
- 100 queries/day = ~$1-3/day = ~$30-90/month
- Caching can reduce this by 20-40% for repeated questions

### UI Design

```
┌─────────────────────────────────────────┐
│                                         │
│           (page content)                │
│                                         │
│                                         │
│                    ┌──────────────────┐ │
│                    │  💬 Asistente AI │ │
│                    │                  │ │
│                    │ "¿Cuántos        │ │
│                    │ repases se       │ │
│                    │ hicieron en      │ │
│                    │ marzo 2026?"     │ │
│                    │                  │ │
│                    │ ─────────────── │ │
│                    │                  │ │
│                    │ "En marzo 2026   │ │
│                    │ se registraron   │ │
│                    │ 45 repases con   │ │
│                    │ un total neto    │ │
│                    │ de $320,450.00"  │ │
│                    │                  │ │
│                    │ ─────────────── │ │
│                    │ [Escribe...  ➤] │ │
│                    │                  │ │
│                    └──────────────────┘ │
│                        ┌───┐           │
│                        │ 💬│ ← floating │
│                        └───┘   button   │
└─────────────────────────────────────────┘
```

**Floating button:** Fixed position bottom-right, z-50, circular with Material Symbols `chat` icon
**Widget panel:** Slide-up or slide-in from bottom-right, 380px wide, 500px max-height
**Dark mode:** Use Tailwind `dark:` variants, Flowbite dark mode compatible
**Alpine.js component:** Uses `x-data` with `show`, `messages[]`, `loading` state, `ask()` method
**Axios:** Already loaded globally in `bootstrap.js`

### Schema for Custom Tables (Optional)

**chat_log** table for auditing (if desired):
```sql
CREATE TABLE chat_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    empresa_id BIGINT UNSIGNED NOT NULL,
    question TEXT NOT NULL,
    generated_sql TEXT NULL,
    response TEXT NULL,
    tokens_used INT UNSIGNED NULL,
    duration_ms INT UNSIGNED NULL,
    was_cached BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL
);
```

### Risks

1. **SQL Injection via AI** — Even with a SELECT-only validator, a cleverly crafted prompt could trick the AI into generating SQL that leaks data across empresas. **Mitigation**: empresa_id injection at the app level (rewrite WHERE clauses), plus read-only DB user as defense-in-depth.
2. **AI Hallucination** — The AI may generate plausible-looking but incorrect queries. **Mitigation**: Use structured prompting with schema examples; if query fails syntax validation, retry once with error message.
3. **Latency** — DeepSeek API call adds 1-5s to response time. **Mitigation**: Loading state in UI, optional async mode for complex queries.
4. **Cost** — Uncontrolled usage could rack up API costs. **Mitigation**: Per-user daily token budget, caching, rate limiting.
5. **Data Privacy** — Sending user's data schema (column names) to DeepSeek. **Mitigation**: Only send table/column names, never send actual row data in prompts. The SQL is generated, not the data.
6. **PII Exposure** — The `observaciones` and `descripcion` fields may contain patient information. **Mitigation**: Document that the AI may expose this, add a warning in the chat UI.

### Ready for Proposal

**Yes.** This feature is well-defined and feasible with the existing architecture. The codebase already has all the building blocks:

- Guzzle/HTTP client available (no extra package needed)
- Alpine.js + Material Symbols already in use for UI
- Queue and cache systems already configured
- Empresa scoping already implemented
- Service/Contract patterns already established
- Testing infrastructure (PHPUnit + RefreshDatabase) already in place

The orchestrator should tell the user:
- **Clear scope defined**: 4 whitelist tables, SELECT-only, read-only DB user, empresa-scoped
- **Security-first approach**: Double-layer validation (app-level + DB-level)
- **Low effort for MVP**: Sync HTTP call, simple chat widget, ~3-5 days of work
- **Immediate next phase**: Proposal → Spec → Design → Tasks → Apply → Verify
