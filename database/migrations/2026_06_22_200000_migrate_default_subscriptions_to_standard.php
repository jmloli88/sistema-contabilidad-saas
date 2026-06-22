<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrate any subscriptions left with type='default' to type='standard'.
     * This is idempotent — only touches rows where type is still 'default'.
     */
    public function up(): void
    {
        DB::table('subscriptions')
            ->where('type', 'default')
            ->update(['type' => 'standard']);
    }

    public function down(): void
    {
        DB::table('subscriptions')
            ->where('type', 'standard')
            ->where('stripe_price', 'price_manual')
            ->update(['type' => 'default']);
    }
};
