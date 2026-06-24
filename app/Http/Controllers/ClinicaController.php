<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClinicaRequest;
use App\Http\Requests\UpdateClinicaRequest;
use App\Models\Clinica;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador para gestionar las operaciones CRUD de Clínicas.
 * 
 * Este controlador maneja la creación, lectura, actualización y eliminación
 * de clínicas donde se realizan los repases médicos.
 */
class ClinicaController extends Controller
{
    /**
     * Constructor del controlador.
     * Aplica middleware de autenticación a todas las rutas.
     */

    /**
     * Muestra un listado de todas las clínicas con paginación.
     * 
     * @return View
     */
    public function index(): View
    {
        $clinicas = Clinica::orderBy('nombre')
            ->paginate(15);

        return view('clinicas.index', compact('clinicas'));
    }

    /**
     * Muestra el formulario para crear una nueva clínica.
     * 
     * @return View
     */
    public function create(): View
    {
        return view('clinicas.create');
    }

    /**
     * Almacena una nueva clínica en la base de datos.
     * 
     * @param StoreClinicaRequest $request
     * @return RedirectResponse
     */
    public function store(StoreClinicaRequest $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $clinica = Clinica::create($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Clínica creada exitosamente.', 'clinica' => $clinica]);
        }

        return redirect()
            ->route('clinicas.show', $clinica)
            ->with('success', 'Clínica creada exitosamente.');
    }

    /**
     * Muestra los detalles de una clínica específica.
     * 
     * @param Clinica $clinica
     * @return View
     */
    public function show(Clinica $clinica): View
    {
        return view('clinicas.show', compact('clinica'));
    }

    /**
     * Muestra el formulario para editar una clínica existente.
     * 
     * @param Clinica $clinica
     * @return View
     */
    public function edit(Clinica $clinica): View
    {
        return view('clinicas.edit', compact('clinica'));
    }

    /**
     * Actualiza una clínica existente en la base de datos.
     * 
     * @param UpdateClinicaRequest $request
     * @param Clinica $clinica
     * @return RedirectResponse
     */
    public function update(UpdateClinicaRequest $request, Clinica $clinica): RedirectResponse
    {
        $clinica->update($request->validated());

        return redirect()
            ->route('clinicas.show', $clinica)
            ->with('success', 'Clínica actualizada correctamente.');
    }

    /**
     * Elimina una clínica de la base de datos.
     * 
     * Verifica que la clínica no tenga repases asociados antes de eliminarla.
     * Si tiene repases asociados, retorna un error.
     * 
     * @param Clinica $clinica
     * @return RedirectResponse
     */
    public function destroy(Clinica $clinica): RedirectResponse
    {
        // Verificar si la clínica tiene repases asociados
        if ($clinica->repases()->count() > 0) {
            return redirect()
                ->route('clinicas.index')
                ->with('error', 'No se puede eliminar la clínica porque tiene repases asociados.');
        }

        $clinica->delete();

        return redirect()
            ->route('clinicas.index')
            ->with('success', 'Clínica eliminada exitosamente.');
    }
}
