<?php

namespace App\Http\Controllers;

use App\Models\GoogleCalendarToken;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendar,
    ) {}

    /**
     * Redirect the admin to Google's OAuth consent screen.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $empresaId = (int) ($request->user()->empresa_id ?? 0);

        if ($empresaId === 0) {
            return redirect()->back()->with('error', 'No se pudo identificar tu empresa.');
        }

        $url = $this->googleCalendar->getAuthUrl($empresaId);

        return redirect()->away($url);
    }

    /**
     * Handle the OAuth callback from Google. Exchange the authorization
     * code for tokens and store them for the empresa.
     */
    public function callback(Request $request): RedirectResponse
    {
        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('dashboard')
                ->with('error', 'Autorización cancelada o código no recibido.');
        }

        $empresaId = $this->googleCalendar->handleCallback($code);

        if (!$empresaId) {
            return redirect()->route('dashboard')
                ->with('error', 'No se pudo conectar con Google Calendar. Intentá de nuevo.');
        }

        return redirect()->route('dashboard')
            ->with('success', '¡Google Calendar conectado exitosamente!');
    }

    /**
     * Disconnect Google Calendar for the admin's empresa.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $empresaId = (int) ($request->user()->empresa_id ?? 0);

        if ($empresaId === 0) {
            return redirect()->back()->with('error', 'No se pudo identificar tu empresa.');
        }

        $this->googleCalendar->disconnect($empresaId);

        return redirect()->back()->with('success', 'Google Calendar desconectado.');
    }

    /**
     * Return the current connection status for the admin's empresa
     * so the front-end can show the appropriate button.
     */
    public function status(Request $request): \Illuminate\Http\JsonResponse
    {
        $empresaId = (int) ($request->user()->empresa_id ?? 0);

        $token = GoogleCalendarToken::where('empresa_id', $empresaId)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'connected' => $token !== null,
            'google_email' => $token?->google_email,
        ]);
    }
}
