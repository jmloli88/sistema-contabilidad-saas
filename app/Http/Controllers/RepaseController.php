<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRepaseRequest;
use App\Http\Requests\UpdateRepaseRequest;
use App\Models\Clinica;
use App\Models\Examen;
use App\Models\Repase;
use App\Services\RepaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controlador para gestionar los repases médicos
 * 
 * Este controlador maneja todas las operaciones CRUD de repases,
 * delegando la lógica de negocio compleja al RepaseService.
 */
class RepaseController extends Controller
{
    /**
     * Constructor del controlador
     * 
     * Inyecta el RepaseService
     * 
     * @param RepaseService $repaseService Servicio de lógica de negocio
     */
    public function __construct(private RepaseService $repaseService)
    {
    }

    /**
     * Muestra el listado de repases con filtros y paginación
     * 
     * Permite filtrar por clínica, estado y rango de fechas usando
     * los scopes definidos en el modelo Repase.
     * 
     * @param Request $request Petición HTTP con parámetros de filtrado
     * @return View Vista con el listado de repases
     */
    public function index(Request $request): View
    {
        $query = Repase::with(['clinica', 'repaseExamenes.examen', 'gastos']);

        // Aplicar filtros usando scopes
        $query->byClinica($request->input('clinica_id'))
              ->byEstado($request->input('estado'))
              ->byDateRange(
                  $request->input('fecha_desde'),
                  $request->input('fecha_hasta')
              );

        // Ordenar por fecha descendente
        $query->orderBy('fecha', 'desc');

        // Paginar resultados
        $repases = $query->paginate(15)->withQueryString();

        // Obtener clínicas para el filtro
        $clinicas = Clinica::orderBy('nombre')->get();

        return view('repases.index', compact('repases', 'clinicas'));
    }

    /**
     * Muestra el formulario para crear un nuevo repase
     * 
     * Carga las clínicas y exámenes disponibles para los selectores
     * del formulario.
     * 
     * @return View Vista con el formulario de creación
     */
    public function create(): View
    {
        $clinicas = Clinica::orderBy('nombre')->get();
        $examenes = Examen::active()
            ->select('id', 'nombre', 'precio_sin_nota', 'precio_con_nota')
            ->orderBy('nombre')
            ->get();
        
        // Cargar precios por clínica como mapa para el frontend
        $preciosPorClinica = [];
        $examenesConClinicas = Examen::active()->with('clinicas')->get();
        foreach ($examenesConClinicas as $examen) {
            foreach ($examen->clinicas as $clinica) {
                $preciosPorClinica[$examen->id][$clinica->id] = [
                    'sin_nota' => (float) $clinica->pivot->precio_sin_nota,
                    'con_nota' => (float) $clinica->pivot->precio_con_nota,
                ];
            }
        }

        return view('repases.create', compact('clinicas', 'examenes', 'preciosPorClinica'));
    }

    /**
     * Almacena un nuevo repase en la base de datos
     * 
     * Utiliza el RepaseService para crear el repase dentro de una
     * transacción, asegurando la integridad de los datos.
     * 
     * @param StoreRepaseRequest $request Petición validada
     * @return RedirectResponse Redirección con mensaje de éxito o error
     */
    public function store(StoreRepaseRequest $request): RedirectResponse
    {
        try {
            // VERIFICACIÓN ESTRICTA: No permitir duplicados de fecha + clínica
            // Buscar cualquier repase existente con la misma fecha y clínica (sin límite de tiempo)
            $repaseExistente = Repase::where('clinica_id', $request->clinica_id)
                ->where('fecha', $request->fecha)
                ->first();
            
            if ($repaseExistente) {
                Log::warning('Intento de crear repase duplicado bloqueado', [
                    'user_id' => auth()->id(),
                    'clinica_id' => $request->clinica_id,
                    'fecha' => $request->fecha,
                    'repase_existente_id' => $repaseExistente->id,
                    'repase_existente_created_at' => $repaseExistente->created_at,
                ]);
                
                return redirect()
                    ->back()
                    ->with('error', 'Ya existe un repase para esta clínica y fecha. Por favor edite el repase existente o seleccione otra fecha.')
                    ->with('repase_existente_id', $repaseExistente->id)
                    ->withInput();
            }
            
            $repase = $this->repaseService->createRepase($request->validated());

            return redirect()
                ->route('repases.show', $repase)
                ->with('success', 'Repase creado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear repase en controller: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'data' => $request->validated(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al guardar el repase. Por favor intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Muestra los detalles de un repase específico
     * 
     * Utiliza eager loading para cargar todas las relaciones y
     * evitar el problema N+1.
     * 
     * @param Repase $repase Repase a mostrar (route model binding)
     * @return View Vista con los detalles del repase
     */
    public function show(Repase $repase): View
    {
        // Cargar relaciones con eager loading
        $repase->load(['clinica', 'repaseExamenes.examen', 'gastos']);

        return view('repases.show', compact('repase'));
    }

    /**
     * Muestra el formulario para editar un repase existente
     * 
     * Carga el repase con sus relaciones y los datos necesarios
     * para los selectores del formulario.
     * 
     * @param Repase $repase Repase a editar (route model binding)
     * @return View Vista con el formulario de edición
     */
    public function edit(Repase $repase): View
    {
        // Cargar relaciones
        $repase->load(['clinica', 'repaseExamenes.examen', 'gastos']);

        // Obtener clínicas y exámenes para los selectores
        $clinicas = Clinica::orderBy('nombre')->get();
        $examenes = Examen::active()
            ->select('id', 'nombre', 'precio_sin_nota', 'precio_con_nota')
            ->orderBy('nombre')
            ->get();
        
        // Cargar precios por clínica como mapa para el frontend
        $preciosPorClinica = [];
        $examenesConClinicas = Examen::active()->with('clinicas')->get();
        foreach ($examenesConClinicas as $examen) {
            foreach ($examen->clinicas as $clinica) {
                $preciosPorClinica[$examen->id][$clinica->id] = [
                    'sin_nota' => (float) $clinica->pivot->precio_sin_nota,
                    'con_nota' => (float) $clinica->pivot->precio_con_nota,
                ];
            }
        }

        return view('repases.edit', compact('repase', 'clinicas', 'examenes', 'preciosPorClinica'));
    }

    /**
     * Actualiza un repase existente en la base de datos
     * 
     * Utiliza el RepaseService para actualizar el repase dentro de
     * una transacción, recalculando todos los totales.
     * 
     * @param UpdateRepaseRequest $request Petición validada
     * @param Repase $repase Repase a actualizar (route model binding)
     * @return RedirectResponse Redirección con mensaje de éxito o error
     */
    public function update(UpdateRepaseRequest $request, Repase $repase): RedirectResponse
    {
        try {
            $repase = $this->repaseService->updateRepase($repase, $request->validated());

            return redirect()
                ->route('repases.show', $repase)
                ->with('success', 'Repase actualizado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar repase en controller: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'repase_id' => $repase->id,
                'data' => $request->validated(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al actualizar el repase. Por favor intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * Elimina un repase de la base de datos
     * 
     * Solo permite eliminar repases con estado "pendiente".
     * Los repases "pagados" no pueden ser eliminados.
     * Utiliza soft delete para mantener el historial.
     * 
     * @param Repase $repase Repase a eliminar (route model binding)
     * @return RedirectResponse Redirección con mensaje de éxito o error
     */
    public function destroy(Repase $repase): RedirectResponse
    {
        // Validar que el estado sea pendiente
        if ($repase->estado === 'pagado') {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar un repase que ya ha sido pagado.');
        }

        try {
            $repase->delete();

            return redirect()
                ->route('repases.index')
                ->with('success', 'Repase eliminado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar repase: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'repase_id' => $repase->id,
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ocurrió un error al eliminar el repase. Por favor intente nuevamente.');
        }
    }
}
