<?php

namespace App\Models\Traits;

use App\Support\EmpresaContext;
use Illuminate\Database\Eloquent\Builder;

trait ScopedByEmpresa
{
    /**
     * Boot the trait.
     *
     * Registers the Global Scope. In Phase 1 this scope is intentionally
     * left commented out — it will be activated in Phase 2 by uncommenting
     * the addGlobalScope call.
     */
    public static function bootScopedByEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $builder) {
            if (EmpresaContext::isSet()) {
                $builder->where($builder->getModel()->getTable() . '.empresa_id', EmpresaContext::get());
            }
        });
    }

    /**
     * Define the inverse relationship to Empresa.
     */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Empresa::class);
    }

    /**
     * Scope a query to only include records belonging to the current empresa context.
     */
    public function scopeForCurrentEmpresa(Builder $query): Builder
    {
        if (EmpresaContext::isSet()) {
            return $query->where($this->getTable() . '.empresa_id', EmpresaContext::get());
        }

        return $query;
    }

    /**
     * Temporarily bypass the empresa scope for SaaS admin queries.
     */
    public function scopeWithoutEmpresaScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('empresa');
    }

    /**
     * Get a query builder without the empresa global scope applied.
     *
     * This is a static convenience wrapper for SaaS admin usage.
     */
    public static function bootWithoutEmpresaScope(): void
    {
        // Static method wrapper — used in SaaS admin controllers
        // Usage: Model::withoutEmpresaScope()->get()
    }
}
