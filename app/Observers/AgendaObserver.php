<?php

namespace App\Observers;

use App\Models\Agenda;
use App\Services\GoogleCalendarService;

class AgendaObserver
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendar,
    ) {}

    /**
     * When an agenda is created, push it to Google Calendar.
     */
    public function created(Agenda $agenda): void
    {
        $this->googleCalendar->syncAgenda($agenda);
    }

    /**
     * When an agenda is updated, sync the changes to Google Calendar.
     */
    public function updated(Agenda $agenda): void
    {
        $this->googleCalendar->syncAgenda($agenda);
    }

    /**
     * When an agenda is deleted, remove the matching Google Calendar event.
     */
    public function deleted(Agenda $agenda): void
    {
        $this->googleCalendar->deleteAgendaEvent($agenda);
    }
}
