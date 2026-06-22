<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds calendar_id to store the Google Calendar ID for the secondary
     * calendar where agendas are synced (instead of the user's primary calendar).
     */
    public function up(): void
    {
        Schema::table('google_calendar_tokens', function (Blueprint $table) {
            $table->string('calendar_id')->nullable()->after('google_email');
            $table->string('calendar_name')->nullable()->after('calendar_id');
        });
    }

    public function down(): void
    {
        Schema::table('google_calendar_tokens', function (Blueprint $table) {
            $table->dropColumn(['calendar_id', 'calendar_name']);
        });
    }
};
