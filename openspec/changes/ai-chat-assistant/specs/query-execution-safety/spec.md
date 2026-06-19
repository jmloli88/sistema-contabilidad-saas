# query-execution-safety Specification

## Purpose

Validate, scope, and safely execute AI-generated SQL. Defense-in-depth: app-level validation + read-only DB user + rate limiting.

## Requirements

### Requirement: SELECT-Only Enforcement

The system MUST reject any SQL that is not a SELECT statement.

#### Scenario: Valid SELECT accepted

- GIVEN a generated SQL query starts with `SELECT` (case-insensitive)
- WHEN the validator checks the query
- THEN the query MUST pass the statement-type check

#### Scenario: Non-SELECT rejected

- GIVEN a generated SQL contains `INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`, `CREATE`, `EXEC`, or `UNION`
- WHEN the validator checks the query
- THEN the query MUST be rejected immediately
- AND the user MUST receive a generic error (not exposing the blocked keyword)

#### Scenario: Comment-obfuscated attack rejected

- GIVEN a generated SQL attempts to hide a dangerous statement inside comments or string literals
- WHEN the validator applies regex-based keyword detection against the raw SQL
- THEN any detected dangerous keyword outside of valid string literals MUST cause rejection

### Requirement: Whitelist Table Enforcement

The system MUST reject queries referencing any table not in the approved whitelist.

#### Scenario: Whitelisted table accepted

- GIVEN a SELECT query references only `clinicas`, `examenes`, `repases`, `repase_examenes`, `gastos`, `empresas`, or `agendas`
- WHEN the validator checks table references
- THEN the query MUST pass validation

#### Scenario: Non-whitelisted table rejected

- GIVEN a SELECT query references `users`, `subscriptions`, `personal_access_tokens`, `sessions`, `cache`, or any excluded table
- WHEN the validator extracts table names from the SQL
- THEN the query MUST be rejected
- AND the response MUST NOT reveal which table was rejected

### Requirement: Empresa Scoping

The system MUST ensure every query is scoped to the authenticated user's empresa.

#### Scenario: Direct empresa_id injection

- GIVEN the user belongs to empresa_id 3
- AND the generated SQL queries a table with an `empresa_id` column (e.g., `repases`, `examenes`, `clinicas`)
- WHEN the validator processes the query
- THEN it MUST inject or rewrite the WHERE clause to include `empresa_id = 3`

#### Scenario: Indirect scoping via JOIN

- GIVEN the user belongs to empresa_id 3
- AND the generated SQL queries `repase_examenes` (which has no direct `empresa_id`)
- WHEN the validator processes the query
- THEN it MUST add or rewrite JOINs through `repases.clinica_id = clinicas.id`
- AND ensure `clinicas.empresa_id = 3` is present in the WHERE clause

#### Scenario: Scoping prevents cross-empresa data leak

- GIVEN User A (empresa 1) asks "¿Cuántos repases hay?"
- AND User B (empresa 2) asks the identical question
- WHEN each query is scoped and executed
- THEN User A MUST only see empresa 1 repases
- AND User B MUST only see empresa 2 repases

### Requirement: Read-Only Database Connection

The system MUST execute all AI-generated SQL on a dedicated read-only MySQL connection.

#### Scenario: SELECT executes on read-only connection

- GIVEN a validated and scoped SELECT query
- WHEN the system executes the query
- THEN it MUST use the `mysql_ai_readonly` database connection
- AND that connection MUST use a MySQL user with only SELECT privileges

#### Scenario: Write attempt fails at DB level

- GIVEN the app-level validator fails to catch a malicious UPDATE statement
- WHEN the query is executed on the `mysql_ai_readonly` connection
- THEN MySQL MUST reject the query due to insufficient privileges
- AND the error MUST be caught and returned as a generic error to the user

### Requirement: Rate Limiting

The system MUST enforce per-user rate limits on the chat API endpoint.

#### Scenario: Within limit

- GIVEN a user has made fewer than 10 requests in the current minute
- WHEN the user sends a new chat request
- THEN the request MUST be processed normally

#### Scenario: Exceeded limit

- GIVEN a user has made 10 or more requests in the current minute
- WHEN the user sends another chat request
- THEN the request MUST be rejected with HTTP 429
- AND the response MUST include a `Retry-After` header

### Requirement: Daily Token Budget

The system SHOULD track per-empresa daily token consumption and enforce configurable limits.

#### Scenario: Under budget

- GIVEN the empresa's daily token consumption is below the configured limit
- WHEN a chat request is processed
- THEN the request MUST proceed normally
- AND the token count MUST be added to the daily running total

#### Scenario: Budget exhausted

- GIVEN the empresa has exceeded its daily token budget
- WHEN a new chat request arrives
- THEN the system SHOULD return an error indicating the daily limit has been reached
- AND the response SHOULD indicate when the budget resets

### Requirement: Response Time Limit

The system SHALL enforce a maximum execution time for generated SQL queries.

#### Scenario: Fast query completes

- GIVEN a validated SELECT query
- WHEN the query executes in under 10 seconds
- THEN the results MUST be returned to the response formatter

#### Scenario: Slow query killed

- GIVEN a validated SELECT query
- WHEN the query execution exceeds 10 seconds
- THEN the query MUST be terminated
- AND the user MUST receive an error suggesting they simplify their question

### Requirement: PII Awareness

The system SHALL warn users in the chat UI that AI-generated responses may expose data from notes and description fields.

#### Scenario: Warning displayed in chat panel

- GIVEN the chat panel is open for the first time in a session
- WHEN the panel renders
- THEN a dismissible warning in Spanish MUST be displayed
- AND the warning MUST state that `observaciones` and `descripcion` fields may be included in responses
