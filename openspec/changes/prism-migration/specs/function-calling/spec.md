# function-calling Specification

## Purpose

Replace the current prompt-based `-- NO_SQL:` convention and markdown code-block regex extraction with Prism's structured output (`Prism::structured()`). Define a typed schema (`SqlResponseSchema`) that forces the LLM to return either a SQL query or a conversational response in a discriminated union, eliminating all post-processing regex for output parsing.

## Requirements

### Requirement: SqlResponseSchema definition

The system MUST define a Prism schema class `SqlResponseSchema` that models a discriminated union of `sql` and `conversational` response types.

#### Scenario: Schema requires type and content

- GIVEN the schema is constructed
- WHEN inspecting its properties
- THEN it MUST have a property `type` of type `string` with description 'Either "sql" or "conversational"'
- AND it MUST have a property `content` of type `string` with description 'The SQL query or conversational text'
- AND both properties MUST be required
- AND the schema name MUST be `sql_response`

#### Scenario: Schema validates at Prism level

- GIVEN the LLM returns a response that does not match the schema
- WHEN `Prism::structured()` processes it
- THEN Prism MUST either reject the response or return a `Response` with error information
- AND the system MUST NOT attempt to parse malformed JSON or free-text manually

### Requirement: SQL generation via structured output

The system MUST generate SQL by calling `Prism::structured()` with the `SqlResponseSchema`, replacing the current `-- NO_SQL:` convention and `extractSql()` regex.

#### Scenario: LLM returns SQL type

- GIVEN a user asks a question that requires database data
- WHEN the system calls `Prism::structured()` with `SqlResponseSchema`
- THEN the returned `structured` object MUST have `type` equal to `"sql"`
- AND `content` MUST contain a SQL SELECT statement
- AND the system MUST NOT strip markdown code blocks (Prism handles JSON encoding)
- AND the system MUST NOT look for `-- NO_SQL:` markers

#### Scenario: LLM returns conversational type

- GIVEN a user asks a greeting or a non-data question (e.g., "Hola", "¿Quién sos?")
- WHEN the system calls `Prism::structured()` with `SqlResponseSchema`
- THEN the returned `structured` object MUST have `type` equal to `"conversational"`
- AND `content` MUST contain the natural-language response in Spanish
- AND the system MUST NOT attempt to execute SQL on this content
- AND the system MUST NOT strip `-- NO_SQL:` prefix (it no longer exists)

#### Scenario: No SQL leak from conversational responses

- GIVEN the LLM returns `type: "conversational"`
- WHEN the structured content is extracted
- THEN the system MUST pass the content directly as the `answer` without regex sanitization
- AND the content MUST NOT contain SQL fragments that need stripping

### Requirement: Structured prompt adaptation

The system prompt for the SQL generation step MUST be updated to reflect the new structured output contract, removing the `-- NO_SQL:` convention instructions.

#### Scenario: Prompt references structured schema

- GIVEN the system prompt for SQL generation is constructed
- WHEN it is sent to Prism
- THEN it MUST instruct the LLM to return responses conforming to the `sql_response` schema
- AND it MUST NOT include `-- NO_SQL:` instructions
- AND it MUST NOT instruct the LLM to use markdown code blocks
- AND it MUST retain all security rules (SELECT-only, empresa scoping, table whitelist)
- AND it MUST retain the schema description with table and column metadata

#### Scenario: Prompt security rules preserved

- GIVEN the updated prompt
- WHEN inspected
- THEN it MUST still include:
  - The `{empresa_id}` placeholder instruction
  - The prohibition against INSERT/UPDATE/DELETE/DROP/ALTER/TRUNCATE/CREATE/EXEC/UNION
  - The `empresa_id` JOIN paths for each table
  - The MySQL/MariaDB dialect notes (LIKE, DATEDIFF, LIMIT)
- AND it MUST still exclude actual row data

### Requirement: processQuery() structured response handling

The system MUST update `ChatQueryService::processQuery()` to handle the typed `{ type, content }` response instead of the current string-based parsing pipeline.

#### Scenario: SQL type goes to validator

- GIVEN `processQuery()` receives a response with `type: "sql"`
- WHEN `content` contains a valid SQL SELECT statement
- THEN the workflow MUST proceed to `SqlValidator::validate()` and SQL execution
- AND the current regex fallback (`preg_match('/(SELECT\b.+?(?:;|$))/si', ...)`) MUST be removed
- AND the current `str_starts_with(trim($sql), '-- NO_SQL:')` check MUST be removed

#### Scenario: Conversational type returns directly

- GIVEN `processQuery()` receives a response with `type: "conversational"`
- WHEN `content` contains the Spanish response text
- THEN the workflow MUST skip `SqlValidator` and SQL execution entirely
- AND MUST return `{ answer: content, tokens: 0 }`
- AND the current `-- NO_SQL:` extraction and SQL-leak sanitization in `processQuery()` MUST be removed

#### Scenario: Empty content triggers retry

- GIVEN `processQuery()` receives a response with `type: "sql"` but empty or null `content`
- WHEN the response is processed
- THEN the system MUST retry the Prism call exactly once with the same prompt
- AND if the retry also returns empty content, MUST throw `RuntimeException`
- AND the exception MUST be caught and return a user-friendly error in Spanish

### Requirement: ChatQueryService refactoring

The `ChatQueryService` MUST be refactored to inject the Prism-based service and handle structured responses, while preserving its external contract.

#### Scenario: Constructor accepts PrismAwareService instead of DeepSeekService

- GIVEN the `ChatQueryService` constructor
- WHEN it is refactored
- THEN it MUST type-hint against a service that provides `generateSql()` and `formatResponse()` methods
- AND the concrete implementation MUST internally use Prism
- AND the existing `AiChatController` (which receives `ChatQueryService` via DI) MUST NOT require changes

#### Scenario: Token estimation replaced with actual usage

- GIVEN a Prism call completes successfully
- WHEN usage data is available on the Prism response
- THEN `$response->usage->totalTokens` (or `promptTokens + completionTokens`) MUST be used as the `tokens` value
- AND the current `estimateTokens()` heuristic SHOULD be kept as a fallback when usage data is unavailable
