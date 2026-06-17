<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;

class SaaSAdminController extends Controller
{
    /**
     * Display the SaaS admin dashboard with subscription KPIs.
     */
    public function dashboard()
    {
        $totalUsers = User::withoutGlobalScope('empresa')->count();
        $activeCount = User::withoutGlobalScope('empresa')->whereHas('subscriptions', function ($q) {
            $q->where('stripe_status', 'active')->where('ends_at', '>', now());
        })->count();
        $expiredCount = User::withoutGlobalScope('empresa')->whereHas('subscriptions', function ($q) {
            $q->where('ends_at', '<=', now());
        })->orWhereDoesntHave('subscriptions')->count();
        $expiringSoon = User::withoutGlobalScope('empresa')->whereHas('subscriptions', function ($q) {
            $q->where('ends_at', '>', now())->where('ends_at', '<=', now()->addDays(7));
        })->count();

        // Empresa-aware KPIs
        $totalEmpresas = Empresa::withoutGlobalScope('empresa')->count();
        $activeEmpresas = Empresa::withoutGlobalScope('empresa')->whereHas('users', function ($q) {
            $q->whereHas('subscriptions', fn($sq) => $sq->where('stripe_status', 'active')->where('ends_at', '>', now()));
        })->count();

        // Clinic-aware KPIs
        $activeClinics = Clinica::withoutGlobalScope('empresa')->whereHas('users', function ($q) {
            $q->whereHas('subscriptions', fn($sq) => $sq->where('stripe_status', 'active')->where('ends_at', '>', now()));
        })->count();

        $recentUsers = User::withoutGlobalScope('empresa')->with('subscriptions', 'clinica')->latest()->take(5)->get();

        // Estimated MRR: R$50 per active subscription
        $estimatedMRR = $activeCount * 50;

        return view('saas-admin.dashboard', compact(
            'totalUsers',
            'activeCount',
            'expiredCount',
            'expiringSoon',
            'totalEmpresas',
            'activeEmpresas',
            'activeClinics',
            'estimatedMRR',
            'recentUsers'
        ));
    }

    /**
     * Display paginated list of users with subscription status.
     */
    public function index(Request $request)
    {
        $query = User::withoutGlobalScope('empresa')
            ->with('subscriptions', 'clinica', 'empresa')
            ->orderBy('name');

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $users = $query->paginate(20);
        $clinicas = Clinica::withoutGlobalScope('empresa')->orderBy('nombre')->get();
        $empresas = Empresa::withoutGlobalScope('empresa')->orderBy('nombre')->get();

        return view('saas-admin.index', compact('users', 'clinicas', 'empresas'));
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        $clinicas = Clinica::withoutGlobalScope('empresa')->orderBy('nombre')->get();
        return view('saas-admin.edit', compact('user', 'clinicas'));
    }

    /**
     * Update a user's profile data (name, email, role, clinic).
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:administrador,usuario',
            'clinica_id' => 'nullable|exists:clinicas,id',
        ]);

        $user->update($validated);

        return back()->with('success', "Usuario {$user->name} actualizado.");
    }

    /**
     * Extend a user's subscription by 30 days.
     */
    public function extend(Request $request, User $user)
    {
        $sub = $user->subscription('default');

        if ($sub) {
            $sub->update([
                'ends_at' => ($sub->ends_at ?? now())->addDays(30),
                'stripe_status' => 'active',
            ]);
        } else {
            $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'manual_' . now()->timestamp,
                'stripe_status' => 'active',
                'stripe_price' => 'price_manual',
                'ends_at' => now()->addDays(30),
            ]);
        }

        \Log::info('Subscription extended for empresa', [
            'user_id' => $user->id,
            'empresa_id' => $user->empresa_id,
            'empresa_nombre' => $user->empresa?->nombre,
        ]);

        return back()->with('success', $user->empresa
            ? "Suscripción de {$user->empresa->nombre} extendida 30 días."
            : 'Suscripción extendida 30 días.');
    }

    /**
     * Cancel a user's subscription immediately.
     */
    public function cancel(Request $request, User $user)
    {
        $sub = $user->subscription('default');

        if ($sub) {
            $sub->update([
                'ends_at' => now(),
                'stripe_status' => 'canceled',
            ]);
        }

        return back()->with('success', 'Suscripción cancelada.');
    }

    /**
     * Set a manual expiration date for a user's subscription.
     */
    public function setExpiry(Request $request, User $user)
    {
        $validated = $request->validate([
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        $sub = $user->subscription('default');

        if ($sub) {
            $sub->update([
                'ends_at' => $validated['expires_at'],
                'stripe_status' => 'active',
            ]);
        } else {
            $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'manual_' . now()->timestamp,
                'stripe_status' => 'active',
                'stripe_price' => 'price_manual',
                'ends_at' => $validated['expires_at'],
            ]);
        }

        return back()->with('success', 'Fecha de vencimiento actualizada.');
    }

    /**
     * Display subscription history for a user.
     */
    public function history(User $user)
    {
        $subscriptions = $user->subscriptions()->orderByDesc('created_at')->get();

        return view('saas-admin.history', compact('user', 'subscriptions'));
    }
}
