<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SaasLoginController extends Controller
{
    /**
     * Display the SaaS admin login view.
     */
    public function create(): View
    {
        return view('auth.saas-login');
    }

    /**
     * Handle an incoming SaaS admin authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('saas')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Credenciales inválidas.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->intended(route('saas.admin.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated SaaS admin session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('saas')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('saas.login');
    }
}
