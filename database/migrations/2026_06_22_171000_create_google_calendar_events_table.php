<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maps local agenda IDs to their Google Calendar event IDs so we can
     * update or delete the remote event when the local agenda changes.
     */
    public function up(): void
    {
        Schema::create('google_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->unique()->constrained('agendas')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('google_event_id');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('google_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_events');
    }
};
