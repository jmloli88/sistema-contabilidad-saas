<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prediction_configuration_audit', function (Blueprint $table) {
            $table->id();
            $table->string('config_key');
            $table->text('old_value')->nullable();
            $table->text('new_value');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at');
            
            // Indexes for performance
            $table->index(['config_key', 'created_at']);
            $table->index('user_id');
            $table->index('created_at');
            
            // Foreign key constraint (optional, depends on user table structure)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_configuration_audit');
    }
};