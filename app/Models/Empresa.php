<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    // Relationship stubs (implemented in later phases)
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clinicas(): HasMany
    {
        return $this->hasMany(Clinica::class);
    }

    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class);
    }
}
