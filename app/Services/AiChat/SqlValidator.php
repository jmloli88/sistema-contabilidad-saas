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
        $condition = "empresa_id = {$empresaId}";

        // If there's already a WHERE clause, prepend our condition
        if (preg_match('/\bWHERE\b/i', $sql)) {
            return preg_replace(
                '/\bWHERE\b/i',
                "WHERE {$condition} AND",
                $sql,
                1
            );
        }

        // Find the end of the FROM/JOIN clause block by locating the last JOIN ... ON pattern
        // or fall back to the first table after FROM
        $joinPattern = '/\bJOIN\s+\S+\s+(?:AS\s+\S+\s+)?ON\s+/i';
        $fromPattern = '/\bFROM\s+(\S+)/i';

        if (preg_match_all($joinPattern, $sql, $joinMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            // Insert after the last JOIN ... ON clause
            $lastJoin = end($joinMatches);
            $insertPos = $lastJoin[0][1] + strlen($lastJoin[0][0]);
            return substr($sql, 0, $insertPos) . " WHERE {$condition} " . substr($sql, $insertPos);
        }

        // No JOIN — insert after FROM and its table reference
        if (preg_match($fromPattern, $sql, $fromMatch, PREG_OFFSET_CAPTURE)) {
            $tableEnd = $fromMatch[1][1] + strlen($fromMatch[1][0]);
            return substr($sql, 0, $tableEnd) . " WHERE {$condition} " . substr($sql, $tableEnd);
        }

        return $sql;
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
