# prism-chat-provider Specification

## Purpose

Replace the custom HTTP-based DeepSeek API integration (`DeepSeekService`) with Prism's native DeepSeek provider. The existing two-step pipeline (SQL generation → response formatting) is preserved, but the transport layer changes from `Http::post()` to `Prism::text()` / `Prism::structured()`, and the config source moves from `services.deepseek.*` to `prism.providers.deepseek`.

## Requirements

### Requirement: Prism provider replaces Http::post()

The system MUST use Prism's DeepSeek provider for ALL LLM calls that currently go through `DeepSeekService::callApi()`, instead of calling `Http::post()` directly.

#### Scenario: SQL generation via Prism structured output

- GIVEN the system needs to generate SQL from a user question
- WHEN `ChatQueryService` triggers SQL generation
- THEN the call MUST use `Prism::structured()` with the `SqlResponseSchema`
- AND MUST use the `deepseek` provider with model `deepseek-chat`
- AND MUST set temperature to `0.1`
- AND MUST set max tokens to `1000`
- AND MUST use a 60-second client timeout
- AND the API key MUST be read from `prism.providers.deepseek.api_key` (the Prism provider config)
- AND the system MUST NOT call `Http::post()` directly for this step

#### Scenario: Response formatting via Prism text

- GIVEN the system needs to format query results into conversational Spanish
- WHEN `ChatQueryService` triggers response formatting
- THEN the call MUST use `Prism::text()` (not `Prism::structured()`)
- AND MUST use the `deepseek` provider with model `deepseek-chat`
- AND MUST set temperature to `0.1`
- AND the system MUST NOT call `Http::post()` directly for this step

#### Scenario: Prism returns typed response for SQL step

- GIVEN `Prism::structured()` is called with `SqlResponseSchema`
- WHEN the DeepSeek API returns a valid response
- THEN the response MUST include a `structured` property with `{ type: string, content: string }`
- AND `type` MUST be either `"sql"` or `"conversational"`
- AND `content` MUST contain the generated SQL or conversational text
- AND the system MUST NOT parse raw JSON keys like `$response['choices'][0]['message']['content']`

### Requirement: Config migration to Prism provider

The system SHALL read DeepSeek credentials and model configuration from `config/prism.php` (`prism.providers.deepseek`) instead of `config/services.php` (`services.deepseek.*`).

#### Scenario: API key read from prism config

- GIVEN a Prism LLM call is made
- WHEN the provider is resolved
- THEN the API key MUST come from `prism.providers.deepseek.api_key`
- AND the system MUST NOT read from `services.deepseek.api_key`

#### Scenario: Model override at call site

- GIVEN Prism has a default model in its provider config
- WHEN the SQL generation call is made
- THEN the call site MUST explicitly pass `'deepseek-chat'` as the model
- AND the response formatting call MUST also explicitly pass `'deepseek-chat'`
- AND temperature MUST be explicitly set to `0.1` at both call sites

#### Scenario: services.deepseek config is deprecated

- GIVEN the migration is complete
- WHEN `config('services.deepseek')` is accessed
- THEN the system MUST NOT depend on this value for runtime functionality
- AND the key SHOULD be removed from `config/services.php` after verification

### Requirement: Client options and timeouts

The Prism call SHALL configure the same timeout and retry behavior as the current HTTP client.

#### Scenario: 60-second timeout preserved

- GIVEN a Prism call is made to DeepSeek
- WHEN the request exceeds 60 seconds
- THEN the call MUST abort with a timeout error
- AND the system MUST return a user-friendly error in Spanish

#### Scenario: HTTP-level retry via Prism

- GIVEN `withClientRetry` is available in the installed Prism version
- WHEN an HTTP-level failure occurs (5xx, connection error)
- THEN Prism SHOULD retry the request using its built-in retry mechanism
- AND the max retries MUST be set to 1 (matching current behavior)

#### Scenario: Semantic empty response retry

- GIVEN `Prism::structured()` returns an empty or null `content` in the response
- WHEN the response `type` is `"sql"` but `content` is empty
- THEN the system MUST retry exactly once with the same prompt
- AND if the retry also returns empty content, the system MUST throw a `RuntimeException`
- AND the exception MUST be caught by `ChatQueryService::processQuery()` and converted to a user-facing error

### Requirement: Existing service interface compatibility

The replacement MUST preserve the contract between `ChatQueryService` and its AI service dependency.

#### Scenario: ChatQueryService injects Prism-based service

- GIVEN `ChatQueryService` is instantiated
- WHEN its constructor is called
- THEN it MUST receive a service that provides `generateSql(string $question, array $schema, array $context): string` and `formatResponse(string $data, string $question): string`
- AND the concrete implementation MUST use Prism internally
- AND the existing tests that mock `ChatQueryService` MUST pass unchanged

#### Scenario: ChatQueryService::ask() return contract preserved

- GIVEN `ChatQueryService::ask()` is called
- WHEN processing completes
- THEN the return array MUST contain keys: `answer` (string), `tokens` (int), optional `chart_url` (string), optional `excel_url` (string)
- AND the controller returning this response MUST NOT require changes

### Requirement: Token usage tracking

The system SHOULD capture actual token usage from Prism's response metadata when available.

#### Scenario: Tokens from Prism response usage

- GIVEN `Prism::structured()` or `Prism::text()` returns successfully
- WHEN the response includes usage data
- THEN `$response->usage->promptTokens` and `$response->usage->completionTokens` SHOULD be extracted
- AND the sum SHOULD be used as the `tokens` value in the return array
- AND if usage data is unavailable, the current heuristic estimation SHOULD be used as fallback
