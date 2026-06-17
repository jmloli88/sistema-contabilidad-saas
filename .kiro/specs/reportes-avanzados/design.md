# Design Document: Módulo de Reportes Avanzados

## Overview

El Módulo de Reportes Avanzados es una extensión del Sistema de Contabilidad Médica que proporciona capacidades analíticas profundas para evaluar la rentabilidad, productividad y tendencias del negocio médico. Este módulo permite a los administradores generar reportes detallados con visualizaciones avanzadas y exportarlos en múltiples formatos.

### Objetivos del Diseño

- Proporcionar análisis financiero detallado por clínica y tipo de examen
- Permitir comparaciones temporales para identificar tendencias
- Ofrecer visualizaciones gráficas interactivas usando Chart.js
- Implementar exportación a Excel y PDF con datos completos
- Garantizar seguridad mediante control de acceso basado en roles
- Optimizar rendimiento para grandes volúmenes de datos
- Mantener consistencia con la arquitectura MVC existente

### Alcance

El módulo incluye:
- 4 tipos de reportes principales (Rentabilidad por Clínica, Rentabilidad por Examen, Productividad, Comparativo)
- Sistema de filtros flexible (rango de fechas, clínica, examen)
- Visualizaciones con Chart.js (gráficos de barras, líneas, pie)
- Exportación a Excel usando Laravel Excel
- Exportación a PDF usando DomPDF
- Middleware de autorización para administradores
- Interfaz responsiva con Tailwind CSS

El módulo NO incluye:
- Modificación de datos existentes (solo lectura)
- Reportes en tiempo real (usa caché de 5 minutos)
- Exportación a otros formatos (CSV, JSON, etc.)
- Programación de reportes automáticos

## Architecture

### Patrón Arquitectónico

El módulo sigue el patrón MVC (Model-View-Controller) existente en Laravel, con una capa adicional de servicios para encapsular la lógica de negocio compleja.

```
┌─────────────────────────────────────────────────────────────┐
│                         Presentation Layer                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Blade Views  │  │  Chart.js    │  │  Alpine.js   │      │
│  │  (Reportes)  │  │ (Gráficos)   │  │  (Filtros)   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      Controller Layer                        │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           ReporteController                          │   │
│  │  - index()                                           │   │
│  │  - rentabilidadClinica()                            │   │
│  │  - rentabilidadExamen()                             │   │
│  │  - productividad()                                  │   │
│  │  - comparativo()                                    │   │
│  │  - exportExcel()                                    │   │
│  │  - exportPdf()                                      │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                       Service Layer                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           ReporteService                             │   │
│  │  - calcularRentabilidadClinica()                    │   │
│  │  - calcularRentabilidadExamen()                     │   │
│  │  - calcularProductividad()                          │   │
│  │  - calcularComparativo()                            │   │
│  │  - aplicarFiltros()                                 │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           ExportService                              │   │
│  │  - exportarExcel()                                  │   │
│  │  - exportarPdf()                                    │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                        Model Layer                           │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │  Repase  │  │ Clinica  │  │  Examen  │  │  Gasto   │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
│  ┌──────────────────┐                                       │
│  │  RepaseExamen    │                                       │
│  └──────────────────┘                                       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      Database Layer (SQLite)                 │
└─────────────────────────────────────────────────────────────┘
```

### Flujo de Datos

1. **Solicitud de Reporte**: Usuario administrador accede a una vista de reporte
2. **Middleware de Autorización**: Verifica que el usuario tenga rol "administrador"
3. **Controller**: Recibe la solicitud y valida parámetros de filtro
4. **Service Layer**: Ejecuta consultas optimizadas con agregaciones SQL
5. **Cache**: Verifica si existe resultado en caché (5 minutos)
6. **Database**: Ejecuta queries con eager loading y agregaciones
7. **Transformación**: Service procesa datos y calcula métricas derivadas
8. **Response**: Controller retorna vista con datos o archivo de exportación

### Seguridad

- **Autenticación**: Usa el sistema de autenticación existente de Laravel
- **Autorización**: Middleware `EnsureUserIsAdmin` en todas las rutas
- **Validación**: Request validation para todos los parámetros de entrada
- **SQL Injection**: Protección mediante Eloquent ORM y prepared statements
- **XSS**: Blade templates con escape automático



## Components and Interfaces

### 1. ReporteController

**Responsabilidad**: Manejar las solicitudes HTTP para reportes y coordinar la generación de vistas y exportaciones.

**Métodos Públicos**:

```php
class ReporteController extends Controller
{
    /**
     * Muestra el dashboard principal de reportes
     * 
     * @return \Illuminate\View\View
     */
    public function index(): View;

    /**
     * Genera reporte de rentabilidad por clínica
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function rentabilidadClinica(Request $request): View;

    /**
     * Genera reporte de rentabilidad por tipo de examen
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function rentabilidadExamen(Request $request): View;

    /**
     * Genera reporte de productividad
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function productividad(Request $request): View;

    /**
     * Genera reporte comparativo de períodos
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function comparativo(Request $request): View;

    /**
     * Exporta reporte a Excel
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request): BinaryFileResponse;

    /**
     * Exporta reporte a PDF
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPdf(Request $request): BinaryFileResponse;
}
```

**Validaciones**:
- `fecha_inicio`: required|date|date_format:Y-m-d
- `fecha_fin`: required|date|date_format:Y-m-d|after_or_equal:fecha_inicio
- `clinica_id`: nullable|exists:clinicas,id
- `examen_id`: nullable|exists:examenes,id
- `formato`: required|in:excel,pdf (para exportaciones)

### 2. ReporteService

**Responsabilidad**: Encapsular la lógica de negocio para cálculos de reportes y aplicación de filtros.

**Métodos Públicos**:

```php
class ReporteService
{
    /**
     * Calcula rentabilidad por clínica
     * 
     * @param array $filtros ['fecha_inicio', 'fecha_fin', 'clinica_id']
     * @return \Illuminate\Support\Collection
     */
    public function calcularRentabilidadClinica(array $filtros): Collection;

    /**
     * Calcula rentabilidad por tipo de examen
     * 
     * @param array $filtros ['fecha_inicio', 'fecha_fin', 'clinica_id', 'examen_id']
     * @return \Illuminate\Support\Collection
     */
    public function calcularRentabilidadExamen(array $filtros): Collection;

    /**
     * Calcula métricas de productividad
     * 
     * @param array $filtros ['fecha_inicio', 'fecha_fin', 'clinica_id']
     * @return array
     */
    public function calcularProductividad(array $filtros): array;

    /**
     * Calcula comparativo entre dos períodos
     * 
     * @param array $periodoActual ['fecha_inicio', 'fecha_fin']
     * @param array $periodoAnterior ['fecha_inicio', 'fecha_fin']
     * @param array $filtros ['clinica_id']
     * @return array
     */
    public function calcularComparativo(
        array $periodoActual, 
        array $periodoAnterior, 
        array $filtros = []
    ): array;

    /**
     * Calcula margen de ganancia
     * 
     * @param float $ingresos
     * @param float $gastos
     * @return float|null Retorna null si ingresos es 0
     */
    public function calcularMargenGanancia(float $ingresos, float $gastos): ?float;

    /**
     * Calcula variación porcentual entre dos valores
     * 
     * @param float $valorActual
     * @param float $valorAnterior
     * @return float|null Retorna null si valorAnterior es 0
     */
    public function calcularVariacionPorcentual(
        float $valorActual, 
        float $valorAnterior
    ): ?float;
}
```

**Optimizaciones**:
- Usa agregaciones SQL (SUM, COUNT, AVG) en lugar de cálculos en PHP
- Implementa eager loading para evitar N+1 queries
- Aplica caché de 5 minutos para resultados idénticos
- Usa índices en columnas fecha, clinica_id, examen_id

### 3. ExportService

**Responsabilidad**: Manejar la exportación de reportes a diferentes formatos.

**Métodos Públicos**:

```php
class ExportService
{
    /**
     * Exporta datos a Excel
     * 
     * @param string $tipoReporte
     * @param \Illuminate\Support\Collection $datos
     * @param array $filtros
     * @return string Ruta del archivo generado
     */
    public function exportarExcel(
        string $tipoReporte, 
        Collection $datos, 
        array $filtros
    ): string;

    /**
     * Exporta datos a PDF
     * 
     * @param string $tipoReporte
     * @param \Illuminate\Support\Collection $datos
     * @param array $filtros
     * @param array $graficos URLs de imágenes de gráficos
     * @return string Ruta del archivo generado
     */
    public function exportarPdf(
        string $tipoReporte, 
        Collection $datos, 
        array $filtros,
        array $graficos = []
    ): string;
}
```

**Dependencias**:
- Laravel Excel (maatwebsite/excel) para exportación Excel
- DomPDF (barryvdh/laravel-dompdf) para exportación PDF

### 4. EnsureUserIsAdmin Middleware

**Responsabilidad**: Verificar que el usuario autenticado tenga rol de administrador.

```php
class EnsureUserIsAdmin
{
    /**
     * Maneja una solicitud entrante
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
```

### 5. Vistas Blade

**Estructura de Vistas**:

```
resources/views/reportes/
├── index.blade.php              # Dashboard principal
├── rentabilidad-clinica.blade.php
├── rentabilidad-examen.blade.php
├── productividad.blade.php
├── comparativo.blade.php
├── partials/
│   ├── filtros.blade.php        # Componente de filtros reutilizable
│   ├── tabla-rentabilidad.blade.php
│   └── botones-exportacion.blade.php
└── exports/
    └── pdf-template.blade.php   # Template para PDFs
```

**Componentes Reutilizables**:
- Filtros de fecha y clínica
- Botones de exportación
- Tablas con ordenamiento
- Contenedores de gráficos Chart.js

### 6. Componentes JavaScript

**Chart.js Integration**:

```javascript
// resources/js/reportes/charts.js
export class ReporteCharts {
    /**
     * Crea gráfico de barras para rentabilidad por clínica
     */
    crearGraficoBarras(elementId, datos, opciones);

    /**
     * Crea gráfico de pie para distribución de ingresos
     */
    crearGraficoPie(elementId, datos, opciones);

    /**
     * Crea gráfico de líneas para tendencias temporales
     */
    crearGraficoLineas(elementId, datos, opciones);

    /**
     * Actualiza gráfico existente con nuevos datos
     */
    actualizarGrafico(chartInstance, nuevosDatos);
}
```

**Alpine.js para Filtros**:

```javascript
// Componente Alpine.js para manejo de filtros
Alpine.data('filtrosReporte', () => ({
    fechaInicio: '',
    fechaFin: '',
    clinicaId: '',
    examenId: '',
    
    aplicarFiltros() {
        // Lógica para aplicar filtros vía AJAX
    },
    
    limpiarFiltros() {
        // Resetear todos los filtros
    }
}));
```

### 7. Rutas

```php
// routes/web.php
Route::middleware(['auth', 'admin'])->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/', [ReporteController::class, 'index'])->name('index');
    Route::get('/rentabilidad-clinica', [ReporteController::class, 'rentabilidadClinica'])->name('rentabilidad-clinica');
    Route::get('/rentabilidad-examen', [ReporteController::class, 'rentabilidadExamen'])->name('rentabilidad-examen');
    Route::get('/productividad', [ReporteController::class, 'productividad'])->name('productividad');
    Route::get('/comparativo', [ReporteController::class, 'comparativo'])->name('comparativo');
    Route::post('/export/excel', [ReporteController::class, 'exportExcel'])->name('export.excel');
    Route::post('/export/pdf', [ReporteController::class, 'exportPdf'])->name('export.pdf');
});
```



## Data Models

### Modelos Existentes (Reutilizados)

El módulo utiliza los modelos Eloquent existentes sin modificaciones:

#### 1. Repase

```php
class Repase extends Model
{
    protected $fillable = [
        'clinica_id',
        'fecha',
        'fecha_pago',
        'estado',
        'tipo_precio',
        'total_examenes',
        'total_consultas',
        'total_gastos',
        'total_neto',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_pago' => 'date',
        'total_examenes' => 'decimal:2',
        'total_consultas' => 'decimal:2',
        'total_gastos' => 'decimal:2',
        'total_neto' => 'decimal:2',
    ];

    // Relaciones
    public function clinica(): BelongsTo;
    public function repaseExamenes(): HasMany;
    public function gastos(): HasMany;

    // Scopes útiles para reportes
    public function scopeByClinica($query, ?int $clinicaId);
    public function scopeByEstado($query, ?string $estado);
    public function scopeByDateRange($query, ?string $from, ?string $to);
}
```

**Uso en Reportes**:
- `total_examenes + total_consultas` = ingresos totales del repase
- `total_gastos` = gastos totales del repase
- `total_neto` = ganancia neta del repase
- `fecha` = usado para filtros temporales

#### 2. Clinica

```php
class Clinica extends Model
{
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
    ];

    public function repases(): HasMany;
}
```

**Uso en Reportes**:
- Agrupación de métricas por establecimiento
- Filtro de reportes por clínica específica

#### 3. Examen

```php
class Examen extends Model
{
    protected $table = 'examenes';

    protected $fillable = [
        'nombre',
        'precio_sin_nota',
        'precio_con_nota',
    ];

    protected $casts = [
        'precio_sin_nota' => 'decimal:2',
        'precio_con_nota' => 'decimal:2',
    ];

    public function repaseExamenes(): HasMany;
}
```

**Uso en Reportes**:
- Análisis de rentabilidad por tipo de procedimiento
- Cálculo de ingresos por examen

#### 4. RepaseExamen

```php
class RepaseExamen extends Model
{
    protected $table = 'repase_examenes';

    protected $fillable = [
        'repase_id',
        'examen_id',
        'cantidad',
        'precio_unitario_usado',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario_usado' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function repase(): BelongsTo;
    public function examen(): BelongsTo;
}
```

**Uso en Reportes**:
- Cálculo de ingresos por tipo de examen: `SUM(cantidad * precio_unitario_usado)`
- Conteo de exámenes realizados: `SUM(cantidad)`
- Análisis de productividad

#### 5. Gasto

```php
class Gasto extends Model
{
    protected $fillable = [
        'repase_id',
        'tipo',
        'descripcion',
        'monto',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function repase(): BelongsTo;
}
```

**Uso en Reportes**:
- Desglose de gastos por tipo
- Cálculo de gastos totales

### Estructuras de Datos para Reportes

#### RentabilidadClinicaDTO

```php
[
    'clinica_id' => int,
    'nombre_clinica' => string,
    'total_ingresos' => float,      // total_examenes + total_consultas
    'total_gastos' => float,
    'ganancia_neta' => float,       // total_ingresos - total_gastos
    'margen_ganancia' => float|null, // ((ingresos - gastos) / ingresos) * 100
    'cantidad_repases' => int,
]
```

#### RentabilidadExamenDTO

```php
[
    'examen_id' => int,
    'nombre_examen' => string,
    'cantidad_total' => int,        // SUM(cantidad)
    'total_ingresos' => float,      // SUM(cantidad * precio_unitario_usado)
    'ingreso_promedio' => float,    // total_ingresos / cantidad_total
]
```

#### ProductividadDTO

```php
[
    'total_examenes_realizados' => int,
    'examenes_por_dia' => float,
    'total_repases' => int,
    'examenes_por_repase' => float,
    'por_examen' => [               // Desglose por tipo
        [
            'examen_id' => int,
            'nombre_examen' => string,
            'cantidad_total' => int,
        ],
        // ...
    ],
    'por_clinica' => [              // Desglose por clínica
        [
            'clinica_id' => int,
            'nombre_clinica' => string,
            'cantidad_total' => int,
        ],
        // ...
    ],
]
```

#### ComparativoDTO

```php
[
    'periodo_actual' => [
        'fecha_inicio' => string,
        'fecha_fin' => string,
        'total_ingresos' => float,
        'total_gastos' => float,
        'ganancia_neta' => float,
    ],
    'periodo_anterior' => [
        'fecha_inicio' => string,
        'fecha_fin' => string,
        'total_ingresos' => float,
        'total_gastos' => float,
        'ganancia_neta' => float,
    ],
    'variaciones' => [
        'ingresos_variacion' => float|null,  // Porcentaje
        'gastos_variacion' => float|null,
        'ganancia_variacion' => float|null,
    ],
]
```

### Consultas SQL Optimizadas

#### Rentabilidad por Clínica

```sql
SELECT 
    c.id as clinica_id,
    c.nombre as nombre_clinica,
    SUM(r.total_examenes + r.total_consultas) as total_ingresos,
    SUM(r.total_gastos) as total_gastos,
    SUM(r.total_neto) as ganancia_neta,
    COUNT(r.id) as cantidad_repases
FROM clinicas c
LEFT JOIN repases r ON c.id = r.clinica_id
WHERE r.fecha BETWEEN ? AND ?
GROUP BY c.id, c.nombre
ORDER BY ganancia_neta DESC
```

#### Rentabilidad por Examen

```sql
SELECT 
    e.id as examen_id,
    e.nombre as nombre_examen,
    SUM(re.cantidad) as cantidad_total,
    SUM(re.subtotal) as total_ingresos
FROM examenes e
LEFT JOIN repase_examenes re ON e.id = re.examen_id
LEFT JOIN repases r ON re.repase_id = r.id
WHERE r.fecha BETWEEN ? AND ?
GROUP BY e.id, e.nombre
ORDER BY total_ingresos DESC
```

### Índices de Base de Datos

Para optimizar el rendimiento de los reportes, se requieren los siguientes índices:

```sql
-- Índice compuesto para filtros de fecha y clínica
CREATE INDEX idx_repases_fecha_clinica ON repases(fecha, clinica_id);

-- Índice para relación repase_examenes
CREATE INDEX idx_repase_examenes_repase_examen ON repase_examenes(repase_id, examen_id);

-- Índice para gastos por repase
CREATE INDEX idx_gastos_repase ON gastos(repase_id);
```



## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Categorías de Propiedades

Las propiedades de correctitud para el módulo de reportes se organizan en las siguientes categorías:

1. **Propiedades de Cálculo**: Verifican que los cálculos matemáticos sean correctos
2. **Propiedades de Invariantes**: Verifican que las relaciones matemáticas se mantengan
3. **Propiedades de Filtrado**: Verifican que los filtros funcionen correctamente
4. **Propiedades de Validación**: Verifican que las entradas sean validadas correctamente
5. **Propiedades de Exportación**: Verifican que los datos exportados sean consistentes
6. **Propiedades de Autorización**: Verifican que el control de acceso funcione correctamente

### Property 1: Autorización de Acceso a Reportes

*For any* usuario del sistema, el acceso a las rutas del módulo de reportes debe ser permitido si y solo si el usuario tiene rol "administrador".

**Validates: Requirements 1.1, 1.4**

### Property 2: Cálculo de Ingresos Totales por Clínica

*For any* clínica y rango de fechas, el total_ingresos calculado debe ser igual a la suma de (total_examenes + total_consultas) de todos los repases de esa clínica en ese período.

**Validates: Requirements 3.1**

### Property 3: Cálculo de Gastos Totales por Clínica

*For any* clínica y rango de fechas, el total_gastos calculado debe ser igual a la suma de total_gastos de todos los repases de esa clínica en ese período.

**Validates: Requirements 3.2**

### Property 4: Invariante de Ganancia Neta

*For any* registro en el reporte de rentabilidad (ya sea por clínica o por examen), la ganancia_neta debe ser igual a total_ingresos menos total_gastos con una precisión de 0.01.

**Validates: Requirements 3.3, 3.10**

### Property 5: Cálculo de Margen de Ganancia

*For any* registro con ingresos mayores a cero, el margen_ganancia debe ser igual a ((total_ingresos - total_gastos) / total_ingresos) * 100 con una precisión de 0.01. Cuando ingresos es cero, debe retornar null o "N/A".

**Validates: Requirements 3.4, 11.1, 11.2, 6.9**

### Property 6: Conteo de Repases por Clínica

*For any* clínica y rango de fechas, la cantidad_repases debe ser igual al conteo de registros Repase de esa clínica en ese período.

**Validates: Requirements 3.5**

### Property 7: Formato de Valores Monetarios

*For any* valor monetario mostrado o exportado en el sistema, debe estar formateado con exactamente dos decimales y debe incluir el símbolo de moneda apropiado.

**Validates: Requirements 3.7, 4.5**

### Property 8: Formato de Porcentajes

*For any* valor de porcentaje (margen_ganancia, variacion_porcentual) mostrado o exportado, debe estar formateado con exactamente dos decimales seguido del símbolo "%".

**Validates: Requirements 3.8, 11.3**

### Property 9: Filtrado por Rango de Fechas

*For any* reporte con filtro de rango de fechas aplicado, todos los registros incluidos deben tener su campo fecha dentro del rango [fecha_inicio, fecha_fin] inclusive.

**Validates: Requirements 3.9, 4.6**

### Property 10: Cálculo de Ingresos por Tipo de Examen

*For any* tipo de examen y rango de fechas, el total_ingresos debe ser igual a la suma de (cantidad * precio_unitario_usado) de todos los repase_examenes de ese tipo en ese período.

**Validates: Requirements 4.1**

### Property 11: Conteo de Exámenes por Tipo

*For any* tipo de examen y rango de fechas, la cantidad_total debe ser igual a la suma de cantidad de todos los repase_examenes de ese tipo en ese período.

**Validates: Requirements 4.2**

### Property 12: Cálculo de Ingreso Promedio por Examen

*For any* tipo de examen con cantidad_total mayor a cero, el ingreso_promedio debe ser igual a total_ingresos dividido por cantidad_total con una precisión de 0.01. Cuando cantidad_total es cero, debe manejar el caso apropiadamente.

**Validates: Requirements 4.3, 4.9**

### Property 13: Filtrado por Clínica

*For any* reporte con filtro de clinica_id aplicado, todos los registros incluidos deben pertenecer exclusivamente a esa clínica.

**Validates: Requirements 4.7, 5.7**

### Property 14: Ordenamiento por Ingresos

*For any* reporte de rentabilidad por examen sin ordenamiento explícito, los resultados deben estar ordenados por total_ingresos en orden descendente.

**Validates: Requirements 4.8**

### Property 15: Cálculo de Total de Exámenes Realizados

*For any* período, el total_examenes_realizados debe ser igual a la suma de cantidad de todos los repase_examenes en ese período.

**Validates: Requirements 5.1**

### Property 16: Cálculo de Exámenes por Día

*For any* período con al menos un día, examenes_por_dia debe ser igual a total_examenes_realizados dividido por el número de días en el período.

**Validates: Requirements 5.2**

### Property 17: Conteo de Repases en Período

*For any* período, el total_repases debe ser igual al conteo de registros Repase con fecha dentro de ese período.

**Validates: Requirements 5.3**

### Property 18: Cálculo de Exámenes por Repase

*For any* período con total_repases mayor a cero, examenes_por_repase debe ser igual a total_examenes_realizados dividido por total_repases.

**Validates: Requirements 5.4**

### Property 19: Invariante de Suma de Productividad por Clínica

*For any* reporte de productividad, la suma de cantidad_total en el desglose por clínica debe ser igual a total_examenes_realizados del sistema.

**Validates: Requirements 5.9, 20.3**

### Property 20: Invariante de Suma de Productividad por Examen

*For any* reporte de productividad, la suma de cantidad_total en el desglose por tipo de examen debe ser igual a total_examenes_realizados del sistema.

**Validates: Requirements 5.5**

### Property 21: Cálculo de Métricas para Ambos Períodos

*For any* reporte comparativo con periodo_actual y periodo_anterior, las métricas (total_ingresos, total_gastos, ganancia_neta) deben calcularse correctamente para ambos períodos usando las mismas reglas de cálculo que los reportes individuales.

**Validates: Requirements 6.2, 6.3, 6.4**

### Property 22: Cálculo de Variación Porcentual

*For any* par de valores (valor_actual, valor_anterior) donde valor_anterior es mayor a cero, la variacion_porcentual debe ser igual a ((valor_actual - valor_anterior) / valor_anterior) * 100 con precisión de 0.01.

**Validates: Requirements 6.5**

### Property 23: Validación de Orden de Fechas

*For any* par de fechas (fecha_inicio, fecha_fin) proporcionado como filtro, el sistema debe rechazar la entrada si fecha_inicio es posterior a fecha_fin.

**Validates: Requirements 7.4, 14.3**

### Property 24: Lógica AND de Filtros Múltiples

*For any* conjunto de filtros aplicados simultáneamente, el conjunto de resultados debe satisfacer todos los filtros (intersección), no la unión.

**Validates: Requirements 7.5**

### Property 25: Completitud de Datos Exportados

*For any* exportación (Excel o PDF), todos los registros visibles en la interfaz web deben estar presentes en el archivo exportado.

**Validates: Requirements 9.3, 10.4**

### Property 26: Formato de Celdas en Excel

*For any* exportación a Excel, las celdas con valores monetarios deben tener formato de moneda, y las celdas con porcentajes deben tener formato de porcentaje.

**Validates: Requirements 9.5**

### Property 27: Patrón de Nombre de Archivo

*For any* archivo exportado (Excel o PDF), el nombre debe seguir el patrón "reporte_{tipo}_{fecha_generacion}.{extension}" donde tipo es el tipo de reporte, fecha_generacion es la fecha en formato Y-m-d, y extension es "xlsx" o "pdf".

**Validates: Requirements 9.6, 10.7**

### Property 28: Consistencia de Encabezados

*For any* exportación a Excel, los encabezados de columna en el archivo deben coincidir exactamente con los encabezados de la tabla en la interfaz web.

**Validates: Requirements 9.8**

### Property 29: Round-Trip de Datos Exportados

*For any* reporte exportado, si se recalculan los totales y agregaciones a partir de los datos exportados, los valores deben ser idénticos a los mostrados en la interfaz web con precisión de 0.01.

**Validates: Requirements 9.9, 20.4**

### Property 30: Contenido de Encabezado PDF

*For any* PDF generado, el encabezado debe contener el título del reporte, la fecha de generación, y la descripción de los filtros aplicados.

**Validates: Requirements 10.3**

### Property 31: Validación de Formato de Fecha

*For any* fecha proporcionada como parámetro (fecha_inicio o fecha_fin), debe estar en formato Y-m-d (YYYY-MM-DD) o ser rechazada con mensaje de error.

**Validates: Requirements 14.1, 14.2**

### Property 32: Validación de Integridad Referencial

*For any* ID proporcionado como filtro (clinica_id o examen_id), debe referenciar un registro existente en la tabla correspondiente o ser rechazado con mensaje de error.

**Validates: Requirements 14.5, 14.6**

### Property 33: Validación de Formato de Exportación

*For any* solicitud de exportación, el parámetro formato debe ser exactamente "excel" o "pdf", cualquier otro valor debe ser rechazado.

**Validates: Requirements 14.7**

### Property 34: Mensajes de Error en Español

*For any* error de validación, el mensaje de error retornado debe estar en idioma español y debe describir claramente qué parámetro falló y por qué.

**Validates: Requirements 14.8**

### Property 35: Invariante de Suma Total de Ganancia por Clínica

*For any* reporte de rentabilidad por clínica sin filtros de clínica específica, la suma de ganancia_neta de todas las clínicas debe ser igual a la ganancia_neta total del sistema para ese período.

**Validates: Requirements 20.1**

### Property 36: Invariante de Suma Total de Ingresos por Examen

*For any* reporte de rentabilidad por examen sin filtros de examen específico, la suma de total_ingresos de todos los tipos de examen debe ser igual al total de ingresos por exámenes del sistema para ese período.

**Validates: Requirements 20.2**

### Property 37: Precisión de Cálculos Monetarios

*For any* cálculo monetario en el sistema (ingresos, gastos, ganancia, margen), el resultado debe tener una precisión de exactamente 0.01 (dos decimales) antes de ser mostrado o exportado.

**Validates: Requirements 20.5**



## Error Handling

### Estrategia General

El módulo implementa un manejo de errores en capas que proporciona mensajes claros al usuario mientras registra información detallada para debugging.

### Categorías de Errores

#### 1. Errores de Autorización

**Escenario**: Usuario sin permisos intenta acceder a reportes

**Manejo**:
```php
// Middleware EnsureUserIsAdmin
if (!auth()->check() || !auth()->user()->isAdmin()) {
    abort(403, 'No tienes permiso para acceder a esta sección.');
}
```

**Respuesta**:
- HTTP 403 Forbidden
- Mensaje: "No tienes permiso para acceder a esta sección."
- Redirección a dashboard con flash message

#### 2. Errores de Validación

**Escenarios**:
- Fechas en formato incorrecto
- fecha_inicio posterior a fecha_fin
- IDs que no existen en la base de datos
- Formato de exportación inválido

**Manejo**:
```php
$validated = $request->validate([
    'fecha_inicio' => 'required|date|date_format:Y-m-d',
    'fecha_fin' => 'required|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
    'clinica_id' => 'nullable|exists:clinicas,id',
    'examen_id' => 'nullable|exists:examenes,id',
]);
```

**Respuesta**:
- HTTP 422 Unprocessable Entity
- Mensajes específicos en español:
  - "La fecha de inicio debe ser anterior o igual a la fecha de fin."
  - "La clínica seleccionada no existe."
  - "El formato de fecha debe ser YYYY-MM-DD."

#### 3. Errores de División por Cero

**Escenarios**:
- Cálculo de margen de ganancia con ingresos = 0
- Cálculo de variación porcentual con valor anterior = 0
- Cálculo de ingreso promedio con cantidad = 0

**Manejo**:
```php
public function calcularMargenGanancia(float $ingresos, float $gastos): ?float
{
    if ($ingresos == 0) {
        return null; // Se mostrará como "N/A" en la vista
    }
    
    return (($ingresos - $gastos) / $ingresos) * 100;
}
```

**Respuesta**:
- No se lanza excepción
- Se retorna null
- Vista muestra "N/A" en lugar del valor

#### 4. Errores de Datos Vacíos

**Escenario**: No hay datos para los filtros seleccionados

**Manejo**:
```php
$datos = $this->reporteService->calcularRentabilidadClinica($filtros);

if ($datos->isEmpty()) {
    return back()->with('warning', 'No se encontraron datos para los filtros seleccionados.');
}
```

**Respuesta**:
- HTTP 200 OK (no es un error del sistema)
- Flash message amarillo: "No se encontraron datos para los filtros seleccionados."
- Vista muestra mensaje informativo

#### 5. Errores de Exportación

**Escenarios**:
- Fallo al generar archivo Excel
- Fallo al generar archivo PDF
- Fallo al escribir en disco
- Memoria insuficiente para grandes volúmenes

**Manejo**:
```php
try {
    $rutaArchivo = $this->exportService->exportarExcel($tipo, $datos, $filtros);
    return response()->download($rutaArchivo)->deleteFileAfterSend();
} catch (\Exception $e) {
    Log::error('Error al exportar a Excel', [
        'tipo' => $tipo,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    return back()->with('error', 'Ocurrió un error al generar el archivo. Por favor, intenta nuevamente.');
}
```

**Respuesta**:
- HTTP 500 Internal Server Error
- Flash message rojo: "Ocurrió un error al generar el archivo. Por favor, intenta nuevamente."
- Error registrado en logs con detalles completos

#### 6. Errores de Base de Datos

**Escenarios**:
- Timeout de query
- Conexión perdida
- Constraint violations (no debería ocurrir en reportes de solo lectura)

**Manejo**:
```php
try {
    $datos = Repase::byDateRange($fechaInicio, $fechaFin)
        ->byClinica($clinicaId)
        ->with(['clinica', 'repaseExamenes.examen'])
        ->get();
} catch (\Illuminate\Database\QueryException $e) {
    Log::error('Error de base de datos en reportes', [
        'filtros' => $filtros,
        'error' => $e->getMessage(),
    ]);
    
    return back()->with('error', 'Error al consultar la base de datos. Por favor, contacta al administrador.');
}
```

**Respuesta**:
- HTTP 500 Internal Server Error
- Flash message rojo: "Error al consultar la base de datos. Por favor, contacta al administrador."
- Error registrado en logs

### Logging

**Niveles de Log**:
- `ERROR`: Excepciones no manejadas, errores de BD, fallos de exportación
- `WARNING`: Datos vacíos, parámetros sospechosos
- `INFO`: Generación exitosa de reportes, exportaciones completadas

**Formato de Log**:
```php
Log::info('Reporte generado', [
    'tipo' => 'rentabilidad-clinica',
    'usuario_id' => auth()->id(),
    'filtros' => $filtros,
    'registros' => $datos->count(),
    'tiempo_ejecucion' => $tiempoMs,
]);
```

### Mensajes de Usuario

**Principios**:
- Siempre en español
- Claros y accionables
- No exponen detalles técnicos sensibles
- Incluyen sugerencias cuando es posible

**Ejemplos**:
- ✅ "La fecha de inicio debe ser anterior a la fecha de fin."
- ❌ "ValidationException: fecha_inicio > fecha_fin"
- ✅ "No se encontraron datos para el período seleccionado. Intenta con un rango de fechas diferente."
- ❌ "Empty result set"



## Testing Strategy

### Enfoque Dual de Testing

El módulo de reportes requiere tanto pruebas unitarias como pruebas basadas en propiedades para garantizar la correctitud completa:

- **Unit Tests**: Verifican ejemplos específicos, casos edge, y condiciones de error
- **Property-Based Tests**: Verifican propiedades universales a través de múltiples entradas generadas aleatoriamente

Ambos tipos de pruebas son complementarios y necesarios para cobertura completa.

### Property-Based Testing

**Librería**: [Pest PHP](https://pestphp.com/) con [pest-plugin-faker](https://github.com/pestphp/pest-plugin-faker) para generación de datos

**Configuración**:
- Mínimo 100 iteraciones por prueba de propiedad
- Cada prueba debe referenciar su propiedad del documento de diseño
- Formato de tag: `Feature: reportes-avanzados, Property {número}: {texto}`

**Ejemplo de Property Test**:

```php
<?php

use App\Models\Repase;
use App\Models\Clinica;
use App\Services\ReporteService;

/**
 * Feature: reportes-avanzados, Property 4: Invariante de Ganancia Neta
 * 
 * For any registro en el reporte de rentabilidad, la ganancia_neta debe ser 
 * igual a total_ingresos menos total_gastos con una precisión de 0.01.
 */
test('ganancia neta es siempre ingresos menos gastos', function () {
    $reporteService = new ReporteService();
    
    // Generar datos aleatorios
    $clinica = Clinica::factory()->create();
    $cantidadRepases = fake()->numberBetween(1, 20);
    
    Repase::factory()
        ->count($cantidadRepases)
        ->for($clinica)
        ->create();
    
    // Calcular reporte
    $resultado = $reporteService->calcularRentabilidadClinica([
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->format('Y-m-d'),
    ]);
    
    // Verificar propiedad para cada registro
    foreach ($resultado as $registro) {
        $gananciaCalculada = $registro->total_ingresos - $registro->total_gastos;
        expect($registro->ganancia_neta)
            ->toBeNumeric()
            ->toEqualWithDelta($gananciaCalculada, 0.01);
    }
})->repeat(100);

/**
 * Feature: reportes-avanzados, Property 5: Cálculo de Margen de Ganancia
 * 
 * For any registro con ingresos mayores a cero, el margen_ganancia debe ser 
 * igual a ((total_ingresos - total_gastos) / total_ingresos) * 100.
 */
test('margen de ganancia se calcula correctamente', function () {
    $reporteService = new ReporteService();
    
    // Generar valores aleatorios
    $ingresos = fake()->randomFloat(2, 100, 10000);
    $gastos = fake()->randomFloat(2, 0, $ingresos);
    
    $margen = $reporteService->calcularMargenGanancia($ingresos, $gastos);
    
    $margenEsperado = (($ingresos - $gastos) / $ingresos) * 100;
    
    expect($margen)
        ->toBeNumeric()
        ->toEqualWithDelta($margenEsperado, 0.01);
})->repeat(100);

/**
 * Feature: reportes-avanzados, Property 5: División por Cero en Margen
 * 
 * Cuando ingresos es cero, debe retornar null.
 */
test('margen de ganancia retorna null cuando ingresos es cero', function () {
    $reporteService = new ReporteService();
    
    $gastos = fake()->randomFloat(2, 0, 1000);
    
    $margen = $reporteService->calcularMargenGanancia(0, $gastos);
    
    expect($margen)->toBeNull();
})->repeat(100);

/**
 * Feature: reportes-avanzados, Property 9: Filtrado por Rango de Fechas
 * 
 * For any reporte con filtro de rango de fechas, todos los registros incluidos 
 * deben tener su campo fecha dentro del rango.
 */
test('filtro de fechas incluye solo registros en el rango', function () {
    $reporteService = new ReporteService();
    
    // Crear registros con fechas aleatorias
    $fechaInicio = now()->subMonths(3);
    $fechaFin = now()->subMonth();
    
    // Registros dentro del rango
    Repase::factory()
        ->count(10)
        ->create([
            'fecha' => fake()->dateTimeBetween($fechaInicio, $fechaFin),
        ]);
    
    // Registros fuera del rango
    Repase::factory()
        ->count(5)
        ->create([
            'fecha' => fake()->dateTimeBetween(now()->subYears(2), $fechaInicio->copy()->subDay()),
        ]);
    
    Repase::factory()
        ->count(5)
        ->create([
            'fecha' => fake()->dateTimeBetween($fechaFin->copy()->addDay(), now()),
        ]);
    
    // Ejecutar reporte
    $resultado = $reporteService->calcularRentabilidadClinica([
        'fecha_inicio' => $fechaInicio->format('Y-m-d'),
        'fecha_fin' => $fechaFin->format('Y-m-d'),
    ]);
    
    // Verificar que todos los repases están en el rango
    $repasesIds = $resultado->pluck('repases')->flatten()->pluck('id');
    $repases = Repase::whereIn('id', $repasesIds)->get();
    
    foreach ($repases as $repase) {
        expect($repase->fecha)
            ->toBeGreaterThanOrEqual($fechaInicio)
            ->toBeLessThanOrEqual($fechaFin);
    }
})->repeat(100);

/**
 * Feature: reportes-avanzados, Property 29: Round-Trip de Datos Exportados
 * 
 * For any reporte exportado, si se recalculan los totales a partir de los datos 
 * exportados, los valores deben ser idénticos a los de la interfaz web.
 */
test('datos exportados a excel mantienen integridad de cálculos', function () {
    $reporteService = new ReporteService();
    $exportService = new ExportService();
    
    // Generar datos aleatorios
    $clinicas = Clinica::factory()->count(5)->create();
    
    foreach ($clinicas as $clinica) {
        Repase::factory()
            ->count(fake()->numberBetween(3, 10))
            ->for($clinica)
            ->create();
    }
    
    // Calcular reporte original
    $datosOriginales = $reporteService->calcularRentabilidadClinica([
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->format('Y-m-d'),
    ]);
    
    // Exportar a Excel
    $rutaExcel = $exportService->exportarExcel('rentabilidad-clinica', $datosOriginales, []);
    
    // Leer Excel y recalcular totales
    $datosExcel = Excel::toCollection(new RentabilidadClinicaImport(), $rutaExcel)->first();
    
    $totalGananciaOriginal = $datosOriginales->sum('ganancia_neta');
    $totalGananciaExcel = $datosExcel->sum('ganancia_neta');
    
    expect($totalGananciaExcel)
        ->toEqualWithDelta($totalGananciaOriginal, 0.01);
})->repeat(100);
```

### Unit Testing

**Enfoque**: Las pruebas unitarias se enfocan en casos específicos, ejemplos concretos, y condiciones de error.

**Estructura de Tests**:

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── ReporteServiceTest.php
│   │   └── ExportServiceTest.php
│   └── Middleware/
│       └── EnsureUserIsAdminTest.php
├── Feature/
│   ├── ReporteControllerTest.php
│   ├── RentabilidadClinicaTest.php
│   ├── RentabilidadExamenTest.php
│   ├── ProductividadTest.php
│   ├── ComparativoTest.php
│   ├── ExportExcelTest.php
│   └── ExportPdfTest.php
└── Property/
    ├── CalculosReportesPropertyTest.php
    ├── FiltrosPropertyTest.php
    ├── ValidacionPropertyTest.php
    └── ExportacionPropertyTest.php
```

**Ejemplos de Unit Tests**:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Clinica;
use App\Models\Repase;

class ReporteControllerTest extends TestCase
{
    /** @test */
    public function usuario_regular_no_puede_acceder_a_reportes()
    {
        $usuario = User::factory()->create(['role' => 'usuario']);
        
        $response = $this->actingAs($usuario)
            ->get(route('reportes.index'));
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function administrador_puede_acceder_a_reportes()
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        
        $response = $this->actingAs($admin)
            ->get(route('reportes.index'));
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function reporte_muestra_mensaje_cuando_no_hay_datos()
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        
        $response = $this->actingAs($admin)
            ->get(route('reportes.rentabilidad-clinica', [
                'fecha_inicio' => '2020-01-01',
                'fecha_fin' => '2020-01-31',
            ]));
        
        $response->assertStatus(200);
        $response->assertSee('No se encontraron datos');
    }
    
    /** @test */
    public function validacion_rechaza_fecha_inicio_posterior_a_fecha_fin()
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        
        $response = $this->actingAs($admin)
            ->get(route('reportes.rentabilidad-clinica', [
                'fecha_inicio' => '2024-12-31',
                'fecha_fin' => '2024-01-01',
            ]));
        
        $response->assertSessionHasErrors('fecha_fin');
    }
    
    /** @test */
    public function exportacion_excel_genera_archivo_con_nombre_correcto()
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        
        Clinica::factory()
            ->has(Repase::factory()->count(5))
            ->create();
        
        $response = $this->actingAs($admin)
            ->post(route('reportes.export.excel'), [
                'tipo' => 'rentabilidad-clinica',
                'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
                'fecha_fin' => now()->format('Y-m-d'),
            ]);
        
        $response->assertDownload();
        
        $filename = $response->headers->get('content-disposition');
        expect($filename)->toContain('reporte_rentabilidad-clinica_');
        expect($filename)->toContain('.xlsx');
    }
}
```

### Integration Testing

**Objetivo**: Verificar que los componentes funcionan correctamente juntos.

**Áreas de Integración**:

1. **Controller + Service + Model**: Flujo completo de generación de reporte
2. **Service + Export**: Generación y exportación de datos
3. **Middleware + Controller**: Autorización y acceso
4. **Filtros + Queries**: Aplicación correcta de filtros en consultas

**Ejemplo**:

```php
/** @test */
public function flujo_completo_de_reporte_con_filtros_multiples()
{
    $admin = User::factory()->create(['role' => 'administrador']);
    $clinica = Clinica::factory()->create();
    
    // Crear datos de prueba
    Repase::factory()
        ->count(10)
        ->for($clinica)
        ->create([
            'fecha' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    
    // Solicitar reporte con filtros
    $response = $this->actingAs($admin)
        ->get(route('reportes.rentabilidad-clinica', [
            'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
            'clinica_id' => $clinica->id,
        ]));
    
    $response->assertStatus(200);
    $response->assertViewHas('datos');
    
    $datos = $response->viewData('datos');
    
    // Verificar que solo incluye la clínica filtrada
    expect($datos)->toHaveCount(1);
    expect($datos->first()->clinica_id)->toBe($clinica->id);
}
```

### Edge Cases Testing

**Casos Edge Importantes**:

1. **División por cero**: Ingresos = 0, cantidad = 0, valor anterior = 0
2. **Datos vacíos**: Sin repases en el período
3. **Fechas límite**: Mismo día para inicio y fin
4. **Valores negativos**: Gastos mayores que ingresos (ganancia negativa)
5. **Grandes volúmenes**: Miles de registros
6. **Caracteres especiales**: Nombres con acentos, símbolos

```php
/** @test */
public function maneja_correctamente_ganancia_negativa()
{
    $clinica = Clinica::factory()->create();
    
    Repase::factory()->create([
        'clinica_id' => $clinica->id,
        'total_examenes' => 100,
        'total_consultas' => 50,
        'total_gastos' => 200, // Gastos mayores que ingresos
        'fecha' => now(),
    ]);
    
    $reporteService = new ReporteService();
    $resultado = $reporteService->calcularRentabilidadClinica([
        'fecha_inicio' => now()->format('Y-m-d'),
        'fecha_fin' => now()->format('Y-m-d'),
    ]);
    
    expect($resultado->first()->ganancia_neta)->toBeLessThan(0);
    expect($resultado->first()->margen_ganancia)->toBeLessThan(0);
}
```

### Performance Testing

**Objetivo**: Asegurar que los reportes se generen en tiempo razonable.

**Métricas**:
- Reporte con 100 registros: < 1 segundo
- Reporte con 1000 registros: < 3 segundos
- Exportación Excel con 1000 registros: < 5 segundos
- Exportación PDF con 100 registros: < 3 segundos

```php
/** @test */
public function reporte_con_mil_registros_se_genera_en_menos_de_3_segundos()
{
    $clinicas = Clinica::factory()->count(10)->create();
    
    foreach ($clinicas as $clinica) {
        Repase::factory()
            ->count(100)
            ->for($clinica)
            ->create();
    }
    
    $inicio = microtime(true);
    
    $reporteService = new ReporteService();
    $resultado = $reporteService->calcularRentabilidadClinica([
        'fecha_inicio' => now()->subYear()->format('Y-m-d'),
        'fecha_fin' => now()->format('Y-m-d'),
    ]);
    
    $duracion = microtime(true) - $inicio;
    
    expect($duracion)->toBeLessThan(3.0);
    expect($resultado)->toHaveCount(10);
}
```

### Coverage Goals

**Objetivos de Cobertura**:
- Cobertura de líneas: > 90%
- Cobertura de branches: > 85%
- Cobertura de métodos: 100% para ReporteService y ExportService

**Comando para verificar cobertura**:
```bash
php artisan test --coverage --min=90
```

### Continuous Integration

**Pipeline de CI**:
1. Ejecutar linter (PHP CS Fixer)
2. Ejecutar análisis estático (PHPStan nivel 8)
3. Ejecutar unit tests
4. Ejecutar property-based tests (100 iteraciones)
5. Ejecutar feature tests
6. Generar reporte de cobertura
7. Verificar cobertura mínima (90%)

