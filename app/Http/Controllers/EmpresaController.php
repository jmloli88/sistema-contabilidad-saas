<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    /**
     * Display a paginated list of all empresas (SaaS admin view).
     */
    public function index(): View
    {
        $empresas = Empresa::withoutGlobalScope('empresa')
            ->withCount(['users', 'clinicas'])
            ->orderBy('nombre')
            ->paginate(20);

        return view('saas-admin.empresas.index', compact('empresas'));
    }

    /**
     * Show the form for creating a new empresa.
     */
    public function create(): View
    {
        return view('saas-admin.empresas.create');
    }

    /**
     * Store a newly created empresa in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:empresas',
        ]);

        Empresa::withoutGlobalScope('empresa')->create($validated);

        return redirect()->route('saas.admin.empresas.index')
            ->with('success', 'Empresa creada exitosamente.');
    }

    /**
     * Display the specified empresa with its users, clinicas, and subscription status.
     */
    public function show(int $id): View
    {
        $empresa = Empresa::withoutGlobalScope('empresa')
            ->withCount(['users', 'clinicas', 'examenes'])
            ->findOrFail($id);

        // Load users sorted by name (no need to eager load subscriptions on users anymore)
        $empresa->load(['users' => function ($q) {
            $q->orderBy('name');
        }]);

        $activeSubscription = $empresa->hasActiveSubscription();

        return view('saas-admin.empresas.show', compact('empresa', 'activeSubscription'));
    }

    /**
     * Show the form for editing the specified empresa.
     */
    public function edit(int $id): View
    {
        $empresa = Empresa::withoutGlobalScope('empresa')->findOrFail($id);

        return view('saas-admin.empresas.edit', compact('empresa'));
    }

    /**
     * Update the specified empresa in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $empresa = Empresa::withoutGlobalScope('empresa')->findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:empresas,nombre,' . $empresa->id,
        ]);

        $empresa->update($validated);

        return redirect()->route('saas.admin.empresas.index')
            ->with('success', 'Empresa actualizada.');
    }

    /**
     * Remove the specified empresa from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $empresa = Empresa::withoutGlobalScope('empresa')->withCount(['users', 'clinicas'])->findOrFail($id);

        if ($empresa->users_count > 0 || $empresa->clinicas_count > 0) {
            return back()->with('error', 'No se puede eliminar una empresa con usuarios o clínicas asociadas.');
        }

        $empresa->delete();

        return redirect()->route('saas.admin.empresas.index')
            ->with('success', 'Empresa eliminada.');
    }
}
