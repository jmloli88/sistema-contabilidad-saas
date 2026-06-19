# sql-generation Specification

## Purpose

Translate natural-language Spanish questions about financial/operational data into safe, scoped SQL queries via the DeepSeek V4 Flash API.

## Requirements

### Requirement: Natural Language to SQL

The system MUST accept a Spanish-language question from an authenticated user and return a generated SQL query.

#### Scenario: Basic aggregation question

- GIVEN a user asks "¿Cuántos repases se hicieron en marzo 2026?"
- WHEN the system sends a structured prompt to the DeepSeek API
- THEN a syntactically valid SQL query MUST be returned
- AND the query MUST reference only whitelisted tables and columns

#### Scenario: Multi-table question

- GIVEN a user asks "¿Cuáles fueron los exámenes más frecuentes en la Clínica López?"
- WHEN the system sends the structured prompt with schema context
- THEN the generated SQL MUST include appropriate JOINs between `repase_examenes`, `repases`, `examenes`, and `clinicas`

#### Scenario: Empty question rejected

- GIVEN a user submits an empty or whitespace-only question
- WHEN the chat endpoint receives the request
- THEN the system MUST NOT call the DeepSeek API
- AND a validation error MUST be returned

### Requirement: Structured Prompt Template

The system MUST construct prompts that include database schema context without exposing actual data.

#### Scenario: Prompt includes schema metadata

- GIVEN any user question requiring SQL generation
- WHEN the system constructs the DeepSeek API prompt
- THEN the prompt MUST include whitelisted table names and column names
- AND the prompt MUST include relationship hints (foreign keys, join paths)
- AND the prompt MUST instruct the AI to generate SELECT-only queries
- AND the prompt MUST NOT include any actual row data from the database

#### Scenario: Prompt includes empresa context

- GIVEN the authenticated user belongs to empresa_id 5
- WHEN the prompt is constructed
- THEN the prompt MUST include the instruction to filter results by the current empresa
- AND the prompt MUST reference the appropriate `empresa_id` column or JOIN path

### Requirement: API Integration

The system SHALL call the DeepSeek V4 Flash API using Laravel's HTTP client with configurable model, temperature, and timeout.

#### Scenario: Successful API call

- GIVEN a valid prompt has been constructed
- WHEN the system sends a POST request to the DeepSeek chat completions endpoint
- THEN the request MUST include the API key from `config('services.deepseek.api_key')`
- AND the model MUST be `config('services.deepseek.model', 'deepseek-chat')`
- AND the timeout MUST be 60 seconds

#### Scenario: API authentication failure

- GIVEN the DeepSeek API key is invalid or missing
- WHEN the API request is sent
- THEN the system MUST log the error without exposing the key
- AND a user-facing error message in Spanish MUST be returned

#### Scenario: API timeout

- GIVEN a request is sent to DeepSeek API
- WHEN the API does not respond within 60 seconds
- THEN the system MUST return an error indicating the service took too long
- AND the user MUST be prompted to try a simpler question or try again later

### Requirement: Retry on Failure

The system SHALL retry exactly once when the generated SQL fails syntax validation, including the error message in the retry prompt.

#### Scenario: SQL syntax error triggers retry

- GIVEN DeepSeek returns SQL that fails syntax validation
- WHEN the validation error message is available
- THEN the system MUST construct a new prompt that includes the original question AND the error details
- AND the system MUST call the API exactly one more time

#### Scenario: Retry succeeds

- GIVEN a retry prompt has been sent
- WHEN DeepSeek returns valid SQL
- THEN the validated SQL MUST be used for execution

#### Scenario: Retry also fails

- GIVEN a retry prompt has been sent
- WHEN DeepSeek returns SQL that again fails validation
- THEN the system MUST NOT retry further
- AND a user-facing error MUST be returned suggesting the question be rephrased

### Requirement: Response Caching

The system SHOULD cache generated SQL responses for identical question-empresa combinations to reduce API costs.

#### Scenario: Cache hit

- GIVEN a user asks a question identical to one asked within the last 5 minutes
- AND the user belongs to the same empresa
- WHEN the chat endpoint processes the request
- THEN the cached response MUST be returned without calling the DeepSeek API

#### Scenario: Cache miss

- GIVEN a user asks a new question or the cache has expired
- WHEN the chat endpoint processes the request
- THEN the DeepSeek API MUST be called
- AND the response MUST be stored in cache with a 300-second TTL
- AND the cache key MUST be derived from `md5(question)` concatenated with `empresa_id`

#### Scenario: Different empresa, same question

- GIVEN User A (empresa 1) and User B (empresa 2) ask the identical question
- WHEN each request is processed
- THEN each MUST generate a separate cache entry
- AND neither user MUST see the other empresa's cached result

### Requirement: Token Usage Tracking

The system SHOULD record token usage per request for cost monitoring.

#### Scenario: Successful request logs tokens

- GIVEN the DeepSeek API returns a successful response with `usage` data
- WHEN the response is processed
- THEN the system SHOULD log `prompt_tokens`, `completion_tokens`, and `total_tokens`
- AND the log MUST include the authenticated user ID and empresa ID
