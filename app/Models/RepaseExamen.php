<?php

namespace App\Models;

use App\Models\Traits\ScopedByEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepaseExamen extends Model
{
    use HasFactory, ScopedByEmpresa;
    /**
     * Nombre de la tabla asociada al modelo
     */
    protected $table = 'repase_examenes';

    /**
     * Atributos asignables en masa
     */
    protected $fillable = [
        'empresa_id',
        'repase_id',
        'examen_id',
        'cantidad',
        'precio_unitario_usado',
        'subtotal',
    ];

    /**
     * Conversión de tipos de atributos
     */
    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario_usado' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Auto-derive empresa_id from the parent repase when not set explicitly.
     */
    protected static function booted(): void
    {
        static::saving(function (self $repaseExamen) {
            if ($repaseExamen->empresa_id === null && $repaseExamen->repase_id !== null) {
                $repaseExamen->empresa_id = \App\Models\Repase::withoutGlobalScope('empresa')
                    ->whereKey($repaseExamen->repase_id)
                    ->value('empresa_id');
            }
        });
    }

    /**
     * Relación: RepaseExamen pertenece a un Repase
     */
    public function repase(): BelongsTo
    {
        return $this->belongsTo(Repase::class);
    }

    /**
     * Relación: RepaseExamen pertenece a un Examen
     */
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }
}
