<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->decimal('precio_sin_nota', 10, 2);
            $table->decimal('precio_con_nota', 10, 2);
            $table->timestamps();
            
            // Índice en nombre
            $table->index('nombre');
        });
        
        // Agregar constraint CHECK: precio_sin_nota < precio_con_nota
        // Solo para MySQL/PostgreSQL, SQLite no soporta ALTER TABLE ADD CONSTRAINT CHECK
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE examenes ADD CONSTRAINT chk_precio_sin_nota_menor CHECK (precio_sin_nota < precio_con_nota)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examenes');
    }
};
