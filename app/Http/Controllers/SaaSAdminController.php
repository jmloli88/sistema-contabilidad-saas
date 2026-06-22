<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class SaaSAdminController extends Controller
{
    /**
     * Display the SaaS admin dashboard with subscription KPIs.
     */
    public function dashboard()
    {
        // Empresa-level KPIs
        $totalEmpresas = Empresa::withoutGlobalScope('empresa')->count();
        $activeEmpresas = Empresa::withoutGlobalScope('empresa')
            ->whereHas('subscriptions', function ($q) {
                $q->where('stripe_status', 'active')->where('ends_at', '>', now());
            })->count();
        $expiredEmpresas = Empresa::withoutGlobalScope('empresa')
            ->where(function ($q) {
                $q->whereHas('subscriptions', function ($sq) {
                    $sq->where('ends_at', '<=', now());
                })->orDoesntHave('subscriptions');
            })->count();
        $expiringSoon = Empresa::withoutGlobalScope('empresa')
            ->whereHas('subscriptions', function ($q) {
                $q->where('stripe_status', 'active')
                  ->where('ends_at', '>', now())
                  ->where('ends_at', '<=', now()->addDays(7));
            })->count();

        // User counts (for info)
        $totalUsers = User::withoutGlobalScope('empresa')->count();

        $recentEmpresas = Empresa::withoutGlobalScope('empresa')
            ->with(['subscriptions', 'users'])
            ->latest()
            ->take(5)
            ->get();

        // Estimated MRR: R$50 per active empresa
        $estimatedMRR = $activeEmpresas * 50;

        return view('saas-admin.dashboard', compact(
            'totalEmpresas',
            'activeEmpresas',
            'expiredEmpresas',
            'expiringSoon',
            'totalUsers',
            'estimatedMRR',
            'recentEmpresas'
        ));
    }

    /**
     * Display paginated list of users with empresa subscription status.
     */
    public function index(Request $request)
    {
        $query = User::withoutGlobalScope('empresa')
            ->with('empresa')
            ->orderBy('name');

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $users = $query->paginate(20);
        $empresas = Empresa::withoutGlobalScope('empresa')->orderBy('nombre')->get();

        return view('saas-admin.index', compact('users', 'empresas'));
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        $empresas = Empresa::withoutGlobalScope('empresa')->orderBy('nombre')->get();
        return view('saas-admin.edit', compact('user', 'empresas'));
    }

    /**
     * Update a user's profile data (name, email, role, empresa).
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:administrador,usuario',
            'empresa_id' => 'nullable|exists:empresas,id',
        ]);

        $user->update($validated);

        return back()->with('success', "Usuario {$user->name} actualizado.");
    }

    /**
     * Store a newly created user (client) from the SaaS admin panel.
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8'],
            'role' => 'required|in:administrador,usuario',
            'empresa_id' => 'required|exists:empresas,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return back()->with('success', "Usuario {$validated['name']} creado exitosamente.");
    }

    /**
     * Extend a subscription by 30 days. Operates on the user's empresa.
     */
    public function extend(Request $request, User $user)
    {
        $empresa = $user->empresa;

        if (! $empresa) {
            return back()->with('error', 'El usuario no pertenece a ninguna empresa.');
        }

        $sub = $empresa->subscription('standard');

        if ($sub) {
            $sub->update([
                'ends_at' => ($sub->ends_at ?? now())->addDays(30),
                'stripe_status' => 'active',
            ]);
        } else {
            $empresa->subscriptions()->create([
                'type' => 'standard',
                'stripe_id' => 'manual_' . now()->timestamp,
                'stripe_status' => 'active',
                'stripe_price' => 'price_manual',
                'ends_at' => now()->addDays(30),
            ]);
        }

        \Log::info('Subscription extended for empresa', [
            'user_id' => $user->id,
            'empresa_id' => $empresa->id,
            'empresa_nombre' => $empresa->nombre,
        ]);

        return back()->with('success', "Suscripción de {$empresa->nombre} extendida 30 días.");
    }

    /**
     * Cancel a subscription immediately. Operates on the user's empresa.
     */
    public function cancel(Request $request, User $user)
    {
        $empresa = $user->empresa;

        if (! $empresa) {
            return back()->with('error', 'El usuario no pertenece a ninguna empresa.');
        }

        $sub = $empresa->subscription('standard');

        if ($sub) {
            $sub->update([
                'ends_at' => now(),
                'stripe_status' => 'canceled',
            ]);
        }

        return back()->with('success', "Suscripción de {$empresa->nombre} cancelada.");
    }

    /**
     * Set a manual expiration date for a subscription. Operates on the user's empresa.
     */
    public function setExpiry(Request $request, User $user)
    {
        $validated = $request->validate([
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        $empresa = $user->empresa;

        if (! $empresa) {
            return back()->with('error', 'El usuario no pertenece a ninguna empresa.');
        }

        $sub = $empresa->subscription('standard');

        if ($sub) {
            $sub->update([
                'ends_at' => $validated['expires_at'],
                'stripe_status' => 'active',
            ]);
        } else {
            $empresa->subscriptions()->create([
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
     * Display subscription history for a user's empresa.
     */
    public function history(User $user)
    {
        $empresa = $user->empresa;

        if (! $empresa) {
            $subscriptions = collect();
        } else {
            $subscriptions = $empresa->subscriptions()->orderByDesc('created_at')->get();
        }

        return view('saas-admin.history', compact('user', 'subscriptions'));
    }
}
