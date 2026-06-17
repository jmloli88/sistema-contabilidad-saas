<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Examen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para gestionar los exámenes y sus precios
 * 
 * Solo accesible para administradores
 */
class ExamenController extends Controller
{
    /**
     * Muestra el listado de exámenes
     * 
     * @return View
     */
    public function index(): View
    {
        $examenes = Examen::withCount(['clinicas as overrides_count' => function ($query) {
            $query->where(function ($q) {
                $q->whereNotNull('precio_sin_nota')
                  ->orWhereNotNull('precio_con_nota');
            });
        }])->orderBy('nombre')->get();

        return view('examenes.index', compact('examenes'));
    }

    /**
     * Muestra el formulario para editar un examen
     * 
     * @param Examen $examen
     * @return View
     */
    public function edit(Examen $examen): View
    {
        $clinicas = Clinica::orderBy('nombre')->get();

        return view('examenes.edit', compact('examen', 'clinicas'));
    }

    /**
     * Actualiza los precios de un examen
     * 
     * @param Request $request
     * @param Examen $examen
     * @return RedirectResponse
     */
    public function update(Request $request, Examen $examen): RedirectResponse
    {
        $validated = $request->validate([
            'precio_sin_nota' => 'required|numeric|min:0|max:999999.99',
            'precio_con_nota' => 'required|numeric|min:0|max:999999.99|gt:precio_sin_nota',
            'precios_clinicas' => 'nullable|array',
            'precios_clinicas.*.sin_nota' => 'nullable|numeric|min:0|max:999999.99',
            'precios_clinicas.*.con_nota' => 'nullable|numeric|min:0|max:999999.99',
        ], [
            'precio_sin_nota.required' => 'El precio sin nota es requerido.',
            'precio_sin_nota.numeric' => 'El precio sin nota debe ser un número.',
            'precio_sin_nota.min' => 'El precio sin nota debe ser mayor o igual a 0.',
            'precio_con_nota.required' => 'El precio con nota es requerido.',
            'precio_con_nota.numeric' => 'El precio con nota debe ser un número.',
            'precio_con_nota.min' => 'El precio con nota debe ser mayor o igual a 0.',
            'precio_con_nota.gt' => 'El precio con nota debe ser mayor que el precio sin nota.',
        ]);

        $examen->update($validated);

        if ($request->has('precios_clinicas')) {
            foreach ($request->precios_clinicas as $clinicaId => $precios) {
                $examen->clinicas()->syncWithoutDetaching([$clinicaId => [
                    'precio_sin_nota' => !empty($precios['sin_nota']) ? $precios['sin_nota'] : null,
                    'precio_con_nota' => !empty($precios['con_nota']) ? $precios['con_nota'] : null,
                ]]);
            }
        }

        return redirect()
            ->route('examenes.index')
            ->with('success', 'Precios actualizados exitosamente.');
    }
}
