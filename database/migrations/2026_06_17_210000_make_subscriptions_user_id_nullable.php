<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make user_id nullable on subscriptions table since billing is now per-empresa.
     * Cashier uses empresa_id as the billable foreign key.
     */
    public function up(): void
    {
        // Drop foreign key constraint if it exists (MySQL)
        // SQLite ignores foreign keys in tests, but we handle both
        try {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Exception $e) {
            // Foreign key may not exist in SQLite
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            // Recreate as nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
