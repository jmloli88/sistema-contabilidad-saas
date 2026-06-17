<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepaseExamen extends Model
{
    use HasFactory;
    /**
     * Nombre de la tabla asociada al modelo
     */
    protected $table = 'repase_examenes';

    /**
     * Atributos asignables en masa
     */
    protected $fillable = [
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
