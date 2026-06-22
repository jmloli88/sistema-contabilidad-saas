<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Empresa;
use App\Models\GoogleCalendarEvent;
use App\Models\GoogleCalendarToken;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    /** Name of the secondary calendar where agendas are synced. */
    private const CALENDAR_NAME = 'Agendas (VictCorp Repases)';

    /**
     * Build a Google API client with the standard OAuth config.
     */
    private function makeClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId(config('google-calendar.client_id'));
        $client->setClientSecret(config('google-calendar.client_secret'));
        $client->setRedirectUri(config('google-calendar.redirect_uri'));
        $client->setAccessType(config('google-calendar.access_type', 'offline'));
        $client->setPrompt(config('google-calendar.prompt', 'consent'));
        $client->setScopes(config('google-calendar.scopes', []));

        return $client;
    }

    /**
     * Generate the OAuth consent URL that the user must visit to authorize
     * the app to manage their Google Calendar.
     */
    public function getAuthUrl(?int $empresaId = null): string
    {
        $client = $this->makeClient();

        if ($empresaId !== null) {
            // Pass the empresa_id through the OAuth state so we can identify
            // which empresa authorized when Google redirects back.
            $client->setState((string) $empresaId);
        }

        return $client->createAuthUrl();
    }

    /**
     * Exchange an authorization code for access + refresh tokens, and persist
     * them for the empresa. Returns the empresa_id on success, or null.
     */
    public function handleCallback(string $code): ?int
    {
        $client = $this->makeClient();

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);
        } catch (\Throwable $e) {
            Log::error('Google Calendar OAuth code exchange failed: ' . $e->getMessage());
            return null;
        }

        if (isset($token['error'])) {
            Log::error('Google Calendar OAuth error: ' . ($token['error_description'] ?? $token['error']));
            return null;
        }

        // Resolve the empresa from the state parameter.
        $state = request()->input('state');
        $empresaId = $state ? (int) $state : null;

        if (!$empresaId || !Empresa::whereKey($empresaId)->exists()) {
            Log::error('Google Calendar OAuth: invalid or missing empresa_id in state.');
            return null;
        }

        // Fetch the user's Google email for display in the UI.
        $googleEmail = null;
        try {
            $client->setAccessToken($token);
            $oauth2 = new \Google\Service\Oauth2($client);
            $googleEmail = $oauth2->userinfo->get()->email;
        } catch (\Throwable) { /* noop */ }

        GoogleCalendarToken::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => isset($token['expires_in'])
                    ? now()->addSeconds((int) $token['expires_in'])
                    : null,
                'google_email' => $googleEmail,
                'is_active' => true,
            ],
        );

        Log::info("Google Calendar connected for empresa {$empresaId}" . ($googleEmail ? " ({$googleEmail})" : ''));

        // Ensure the secondary calendar exists for this empresa.
        $calendarId = $this->getOrCreateCalendar($empresaId);

        if ($calendarId) {
            // Bulk-sync all existing agendas so the calendar isn't empty on first connect.
            $this->syncAllForEmpresa($empresaId);
        }

        return $empresaId;
    }

    /**
     * Resolve the Google Calendar ID for an empresa. Returns the secondary
     * calendar ID if set up, or falls back to 'primary'. This lets the sync
     * still work for empresas connected before the secondary calendar feature.
     */
    private function getCalendarId(int $empresaId): string
    {
        $token = GoogleCalendarToken::where('empresa_id', $empresaId)
            ->where('is_active', true)
            ->first();

        return $token?->calendar_id ?: 'primary';
    }

    /**
     * Create (or retrieve) a secondary Google Calendar for the empresa.
     * Returns the calendar ID on success, null on failure.
     *
     * Google Calendar allows creating secondary calendars via the API.
     * A dedicated calendar lets the user toggle the entire layer on/off
     * without mixing agendas into their personal calendar.
     */
    public function getOrCreateCalendar(int $empresaId): ?string
    {
        $token = GoogleCalendarToken::where('empresa_id', $empresaId)
            ->where('is_active', true)
            ->first();

        if (!$token) {
            return null;
        }

        // Already has a calendar.
        if ($token->calendar_id) {
            return $token->calendar_id;
        }

        $calendar = $this->getCalendarService($empresaId);
        if (!$calendar) {
            return null;
        }

        try {
            $gCalendar = new \Google\Service\Calendar\Calendar;
            $gCalendar->setSummary(self::CALENDAR_NAME);
            $gCalendar->setTimeZone(config('google-calendar.timezone', 'America/Sao_Paulo'));

            $created = $calendar->calendars->insert($gCalendar);

            $token->update([
                'calendar_id' => $created->getId(),
                'calendar_name' => self::CALENDAR_NAME,
            ]);

            Log::info("Google Calendar: created secondary calendar '{$created->getSummary()}' for empresa {$empresaId}");

            return $created->getId();
        } catch (\Throwable $e) {
            Log::error('Google Calendar: failed to create secondary calendar for empresa ' . $empresaId . ': ' . $e->getMessage());

            return null;
        }
    }

    public function disconnect(int $empresaId): void
    {
        GoogleCalendarToken::where('empresa_id', $empresaId)->update(['is_active' => false]);
        // Optionally revoke the token via Google's API, but for simplicity we
        // just deactivate. The user can re-authorize at any time.
    }

    /**
     * Build an authenticated Calendar service for the given empresa.
     * Returns null if the empresa has no active token.
     */
    private function getCalendarService(int $empresaId): ?Calendar
    {
        $token = GoogleCalendarToken::where('empresa_id', $empresaId)
            ->where('is_active', true)
            ->first();

        if (!$token) {
            return null;
        }

        $client = $this->makeClient();
        $client->setAccessToken([
            'access_token' => $token->access_token,
            'refresh_token' => $token->refresh_token,
            'expires_in' => $token->expires_at?->diffInSeconds(now()) ?? 3600,
        ]);

        // Auto-refresh if the access token has expired.
        if ($token->isExpired() && $token->refresh_token) {
            try {
                $newToken = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);
                if (!isset($newToken['error'])) {
                    $token->update([
                        'access_token' => $newToken['access_token'],
                        'expires_at' => isset($newToken['expires_in'])
                            ? now()->addSeconds((int) $newToken['expires_in'])
                            : null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Google Calendar token refresh failed for empresa ' . $empresaId . ': ' . $e->getMessage());
                return null;
            }
        }

        return new Calendar($client);
    }

    /**
     * Sync an agenda to Google Calendar. Called by AgendaObserver.
     */
    public function syncAgenda(Agenda $agenda): void
    {
        $empresaId = $agenda->empresa_id;

        $calendar = $this->getCalendarService($empresaId);
        if (!$calendar) {
            return; // Google Calendar not configured for this empresa
        }

        $existing = GoogleCalendarEvent::where('agenda_id', $agenda->id)->first();

        try {
            if ($existing) {
                $this->updateEvent($calendar, $existing, $agenda);
            } else {
                $this->createEvent($calendar, $agenda);
            }
        } catch (\Throwable $e) {
            Log::error('Google Calendar sync failed for agenda ' . $agenda->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Delete the Google Calendar event for a deleted agenda.
     */
    public function deleteAgendaEvent(Agenda $agenda): void
    {
        $calendar = $this->getCalendarService($agenda->empresa_id);
        if (!$calendar) {
            return;
        }

        $existing = GoogleCalendarEvent::where('agenda_id', $agenda->id)->first();
        if (!$existing) {
            return;
        }

        try {
            $calendar->events->delete($this->getCalendarId($agenda->empresa_id), $existing->google_event_id);
            $existing->delete();
        } catch (\Throwable $e) {
            // 410 Gone means the event was already deleted from Google.
            if ($e->getCode() === 410) {
                $existing->delete();
            } else {
                Log::error('Google Calendar delete failed for agenda ' . $agenda->id . ': ' . $e->getMessage());
            }
        }
    }

    private function createEvent(Calendar $calendar, Agenda $agenda): void
    {
        $event = $this->buildEvent($agenda);

                $created = $calendar->events->insert($this->getCalendarId($empresaId), $event);

        GoogleCalendarEvent::create([
            'agenda_id' => $agenda->id,
            'empresa_id' => $agenda->empresa_id,
            'google_event_id' => $created->getId(),
            'synced_at' => now(),
        ]);
    }

    private function updateEvent(Calendar $calendar, GoogleCalendarEvent $existing, Agenda $agenda): void
    {
        $event = $this->buildEvent($agenda);

        $calendar->events->update($this->getCalendarId($agenda->empresa_id), $existing->google_event_id, $event);

        $existing->update(['synced_at' => now()]);
    }

    /**
     * Build a Google Calendar Event from an Agenda model.
     *
     * Summary is the clinic name only. Start/end are the agenda's
     * hora_inicio and hora_fin on the agenda's fecha, in the configured
     * timezone.
     */
    private function buildEvent(Agenda $agenda): Event
    {
        $timezone = config('google-calendar.timezone', 'America/Argentina/Buenos_Aires');
        $clinicaNombre = $agenda->clinica?->nombre ?? 'Agenda';

        $start = new EventDateTime;
        $start->setDateTime($agenda->fecha->format('Y-m-d') . 'T' . $agenda->hora_inicio);
        $start->setTimeZone($timezone);

        $end = new EventDateTime;
        $end->setDateTime($agenda->fecha->format('Y-m-d') . 'T' . $agenda->hora_fin);
        $end->setTimeZone($timezone);

        $event = new Event;
        $event->setSummary($clinicaNombre);
        $event->setStart($start);
        $event->setEnd($end);

        return $event;
    }

    /**
     * Push all existing agendas for an empresa to Google Calendar.
     * Called after the initial OAuth connection so the calendar isn't empty.
     */
    public function syncAllForEmpresa(int $empresaId): void
    {
        $calendar = $this->getCalendarService($empresaId);
        if (!$calendar) {
            return;
        }

        $agendas = Agenda::where('empresa_id', $empresaId)
            ->whereDoesntHave('googleCalendarEvent')
            ->get();

        if ($agendas->isEmpty()) {
            return;
        }

        $synced = 0;
        foreach ($agendas as $agenda) {
            try {
                $event = $this->buildEvent($agenda);
        $created = $calendar->events->insert($this->getCalendarId($agenda->empresa_id), $event);

                GoogleCalendarEvent::create([
                    'agenda_id' => $agenda->id,
                    'empresa_id' => $empresaId,
                    'google_event_id' => $created->getId(),
                    'synced_at' => now(),
                ]);

                $synced++;
            } catch (\Throwable $e) {
                Log::error('Google Calendar bulk sync failed for agenda ' . $agenda->id . ': ' . $e->getMessage());
            }
        }

        Log::info("Google Calendar: bulk-synced {$synced} agendas for empresa {$empresaId}");
    }
}
