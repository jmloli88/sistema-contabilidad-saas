<?php

namespace App\Support;

/**
 * EmpresaContext — thread-safe singleton for the current empresa ID.
 *
 * Carries the current empresa context across the request lifecycle,
 * including queue jobs and console commands. Set by the ScopeByEmpresa
 * middleware (activated in Phase 2) or manually in tests.
 *
 * When unset (null), Eloquent Global Scopes become no-ops, allowing
 * SaaS admin queries to bypass tenant isolation.
 */
class EmpresaContext
{
    private static ?int $empresaId = null;

    /**
     * Set the current empresa context.
     */
    public static function set(?int $empresaId): void
    {
        self::$empresaId = $empresaId;
    }

    /**
     * Get the current empresa context.
     *
     * Returns null when unset (SaaS admin routes bypass the scope).
     */
    public static function get(): ?int
    {
        return self::$empresaId;
    }

    /**
     * Check if an empresa context is currently set.
     */
    public static function isSet(): bool
    {
        return self::$empresaId !== null;
    }

    /**
     * Clear the current empresa context.
     */
    public static function clear(): void
    {
        self::$empresaId = null;
    }
}
