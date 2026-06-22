<?php

namespace App\Observers;

use App\Models\Agenda;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

class AgendaObserver
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendar,
    ) {}

    public function created(Agenda $agenda): void
    {
        Log::info('Google Calendar: agenda created, syncing...', ['agenda_id' => $agenda->id]);
        $this->googleCalendar->syncAgenda($agenda);
    }

    public function updated(Agenda $agenda): void
    {
        Log::info('Google Calendar: agenda updated, syncing...', ['agenda_id' => $agenda->id]);
        $this->googleCalendar->syncAgenda($agenda);
    }

    public function deleted(Agenda $agenda): void
    {
        Log::info('Google Calendar: agenda deleted, removing...', ['agenda_id' => $agenda->id]);
        $this->googleCalendar->deleteAgendaEvent($agenda);
    }
}
