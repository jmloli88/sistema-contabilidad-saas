<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores OAuth 2.0 tokens per empresa for the Google Calendar integration.
     * Each empresa with Google Calendar sync enabled will have one row here.
     */
    public function up(): void
    {
        Schema::create('google_calendar_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('google_email')->nullable(); // Google account email shown in the UI
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_tokens');
    }
};
