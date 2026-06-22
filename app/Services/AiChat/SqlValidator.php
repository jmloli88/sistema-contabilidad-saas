<?php

namespace App\Services\AiChat;

class SqlValidator
{
    private const BLOCKED_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER',
        'TRUNCATE', 'CREATE', 'EXEC',
    ];

    private const ALLOWED_TABLES = [
        'repases', 'clinicas', 'examenes', 'gastos',
        'repase_examenes', 'agendas',
    ];

    private const BLOCKED_COLUMNS = [
        'password', 'remember_token', 'stripe_id',
    ];

    /**
     * Expose the whitelisted tables so other services (e.g. ChatQueryService
     * schema loader) can iterate them without duplicating the list.
     *
     * @return list<string>
     */
    public function allowedTables(): array
    {
        return self::ALLOWED_TABLES;
    }

    public function validate(string $sql): array
    {
        $cleaned = $this->stripComments($sql);

        // Block multiple statements (semicolons not at the very end)
        $trimmed = trim($cleaned);
        $withoutTrailingSemicolon = rtrim($trimmed, ';');
        if (str_contains($withoutTrailingSemicolon, ';')) {
            return [
                'valid' => false,
                'error' => 'Multiple SQL statements are not allowed.',
            ];
        }

        // Must start with SELECT
        if (!preg_match('/^\s*SELECT\b/i', $trimmed)) {
            return [
                'valid' => false,
                'error' => 'Only SELECT statements are allowed.',
            ];
        }

        // Block UNION (prevents injection via stacked queries)
        if (preg_match('/\bUNION\b/i', $trimmed)) {
            return [
                'valid' => false,
                'error' => 'UNION statements are not allowed.',
            ];
        }

        // Check for blocked keywords anywhere in the statement
        foreach (self::BLOCKED_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $trimmed)) {
                return [
                    'valid' => false,
                    'error' => 'Only SELECT statements are allowed.',
                ];
            }
        }

        // Check table references against whitelist
        $tables = $this->extractTableNames($trimmed);
        foreach ($tables as $table) {
            if (!in_array($table, self::ALLOWED_TABLES)) {
                return [
                    'valid' => false,
                    'error' => 'Query references a non-whitelisted table.',
                ];
            }
        }

        // Check for blocked columns
        foreach (self::BLOCKED_COLUMNS as $column) {
            if (preg_match('/\b' . preg_quote($column, '/') . '\b/i', $trimmed)) {
                return [
                    'valid' => false,
                    'error' => 'Query references a blocked column.',
                ];
            }
        }

        return [
            'valid' => true,
            'error' => null,
        ];
    }

    public function injectEmpresaScope(string $sql, int $empresaId): string
    {
        $empresaId = (int) $empresaId;

        // Resolve the FROM table alias to avoid "ambiguous column" on JOINs.
        $alias = $this->resolveFromAlias($sql);
        $qualifiedCol = ($alias ? $alias . '.' : '') . 'empresa_id';
        $scopedCondition = "{$qualifiedCol} = {$empresaId}";

        // SECURITY: strip/replace any empresa_id condition the LLM may have added.
        // We never trust the LLM's empresa_id value — always enforce the
        // authenticated user's empresa. Replace any existing empresa_id = X
        // (with any value, quoted or not, with or without table prefix) with
        // the correct one.
        $sql = preg_replace(
            '/\b\w*\.?empresa_id\s*=\s*[\'"]?\d+[\'"]?/i',
            $scopedCondition,
            $sql
        );

        // After replacement, check if empresa_id is now present.
        if (preg_match('/\bempresa_id\s*=\s*' . $empresaId . '/i', $sql)) {
            return $sql; // Replacement handled it — done.
        }

        // No empresa_id in the SQL at all — inject one.
        // If there's already a WHERE clause, AND our condition in.
        if (preg_match('/\bWHERE\b/i', $sql)) {
            return preg_replace(
                '/\bWHERE\b/i',
                "WHERE {$scopedCondition} AND",
                $sql,
                1
            );
        }

        // No WHERE: insert one before the first GROUP BY / HAVING / ORDER BY / LIMIT
        // clause, or at the end of the statement if none of those are present.
        $clausePattern = '/\b(GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT)\b/i';
        if (preg_match($clausePattern, $sql, $match, PREG_OFFSET_CAPTURE)) {
            $insertPos = $match[0][1];
            return substr($sql, 0, $insertPos) . "WHERE {$scopedCondition} " . substr($sql, $insertPos);
        }

        // No WHERE, no GROUP BY/HAVING/ORDER BY/LIMIT — append at the end.
        return rtrim($sql) . " WHERE {$scopedCondition}";
    }

    /**
     * Extract the alias (or table name) of the first FROM clause, so the
     * injected empresa_id condition is qualified and avoids "ambiguous column"
     * errors when multiple joined tables share the empresa_id column.
     */
    private function resolveFromAlias(string $sql): ?string
    {
        // FROM table OR FROM table alias OR FROM table AS alias.
        // The second word after FROM could be an alias OR a SQL keyword
        // (ORDER, GROUP, WHERE, LIMIT, JOIN, ...) — must distinguish them.
        $sqlKeywords = [
            'ORDER', 'GROUP', 'WHERE', 'HAVING', 'LIMIT', 'OFFSET',
            'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'FULL', 'CROSS',
            'UNION', 'EXCEPT', 'INTERSECT', 'WINDOW', 'FETCH', 'FOR',
        ];

        if (preg_match('/\bFROM\s+(\w+)(?:\s+(?:AS\s+)?(\w+))?/i', $sql, $m)) {
            $table = $m[1] ?? null;
            $alias = $m[2] ?? null;

            if ($alias !== null && in_array(strtoupper($alias), $sqlKeywords, true)) {
                // The "alias" is actually a SQL keyword — no alias present.
                return $table;
            }

            return $alias ?? $table;
        }

        return null;
    }

    private function stripComments(string $sql): string
    {
        // Remove multi-line comments /* ... */
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        // Remove single-line comments -- ...
        $sql = preg_replace('/--.*$/m', '', $sql);

        return $sql;
    }

    private function extractTableNames(string $sql): array
    {
        $tables = [];

        // Match FROM clause: FROM table or FROM table AS alias or JOIN table
        if (preg_match_all('/\b(?:FROM|JOIN)\s+(`?\w+`?)(?:\s+(?:AS\s+)?\w+)?/i', $sql, $matches)) {
            foreach ($matches[1] as $match) {
                $table = trim($match, '`');
                if (!in_array($table, $tables)) {
                    $tables[] = $table;
                }
            }
        }

        return $tables;
    }
}
