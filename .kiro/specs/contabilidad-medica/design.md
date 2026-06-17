# Design Document: Sistema de Contabilidad Médica

## Overview

El Sistema de Contabilidad Médica es una aplicación web Laravel 12 diseñada para gestionar los ingresos y gastos generados durante visitas médicas a clínicas. El sistema permite registrar "repases médicos" que incluyen exámenes realizados, consultas y gastos operativos, proporcionando control financiero completo con visualizaciones y reportes.

### Objetivos del Diseño

- Garantizar integridad transaccional en todas las operaciones de repases
- Implementar cálculos automáticos precisos y validados en backend
- Proporcionar una arquitectura escalable preparada para multi-tenancy
- Optimizar consultas mediante eager loading y prevención de N+1
- Ofrecer una interfaz responsiva y profesional con Tailwind CSS

### Stack Tecnológico

- **Backend**: Laravel 12, PHP 8.2+
- **Base de Datos**: MySQL 8.0+
- **Autenticación**: Laravel Breeze
- **Frontend**: Blade Templates + Tailwind CSS
- **Visualizaciones**: Chart.js para gráficos
- **Calendario**: FullCalendar JS
- **Validación**: Laravel FormRequests

## Architecture

### Patrón Arquitectónico

El sistema sigue el patrón MVC (Model-View-Controller) de Laravel con una capa adicional de servicios para lógica de negocio compleja:

```
┌─────────────────────────────────────────────────────────────┐
│                         Presentation Layer                   │
│  (Blade Views + Tailwind CSS + Chart.js + FullCalendar)    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      Controller Layer                        │
│  (RESTful Controllers + FormRequest Validation)             │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                       Service Layer                          │
│  (Business Logic + Transaction Management + Calculations)   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                        Model Layer                           │
│  (Eloquent Models + Relationships + Scopes)                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      Database Layer                          │
│  (MySQL + Migrations + Indexes + Foreign Keys)              │
└─────────────────────────────────────────────────────────────┘
```

### Flujo de Datos Principal

1. **Usuario** → Envía request HTTP
2. **Middleware** → Valida autenticación
3. **Controller** → Recibe request
4. **FormRequest** → Valida datos de entrada
5. **Service** → Ejecuta lógica de negocio en transacción
6. **Model** → Interactúa con base de datos
7. **View** → Renderiza respuesta con datos
8. **Usuario** → Recibe respuesta HTML/JSON

### Módulos del Sistema

1. **Autenticación**: Laravel Breeze (login, registro, recuperación)
2. **Gestión de Clínicas**: CRUD completo
3. **Catálogo de Exámenes**: Seeders con 7 exámenes predefinidos
4. **Gestión de Repases**: Creación, edición, visualización, soft delete
5. **Detalle de Exámenes**: Relación many-to-many con cálculos
6. **Registro de Gastos**: 5 tipos con validación específica
7. **Dashboard**: Métricas financieras con filtros
8. **Visualizaciones**: Gráficos Chart.js
9. **Calendario**: Vista mensual FullCalendar
10. **Búsqueda y Filtrado**: Filtros dinámicos

## Components and Interfaces

### Controllers

#### ClinicaController
```php
class ClinicaController extends Controller
{
    public function index(): View
    public function create(): View
    public function store(StoreClinicaRequest $request): RedirectResponse
    public function show(Clinica $clinica): View
    public function edit(Clinica $clinica): View
    public function update(UpdateClinicaRequest $request, Clinica $clinica): RedirectResponse
    public function destroy(Clinica $clinica): RedirectResponse
}
```

#### RepaseController
```php
class RepaseController extends Controller
{
    public function __construct(private RepaseService $repaseService) {}
    
    public function index(Request $request): View
    public function create(): View
    public function store(StoreRepaseRequest $request): RedirectResponse
    public function show(Repase $repase): View
    public function edit(Repase $repase): View
    public function update(UpdateRepaseRequest $request, Repase $repase): RedirectResponse
    public function destroy(Repase $repase): RedirectResponse
}
```

#### DashboardController
```php
class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}
    
    public function index(Request $request): View
    // Retorna métricas calculadas según filtros aplicados
}
```

#### CalendarioController
```php
class CalendarioController extends Controller
{
    public function index(Request $request): View
    public function events(Request $request): JsonResponse
    // Retorna eventos en formato FullCalendar
}
```

### Services

#### RepaseService
```php
class RepaseService
{
    /**
     * Crea un repase con sus exámenes y gastos en una transacción
     */
    public function createRepase(array $data): Repase
    
    /**
     * Actualiza un repase y recalcula totales
     */
    public function updateRepase(Repase $repase, array $data): Repase
    
    /**
     * Calcula el total de exámenes según tipo_precio
     */
    public function calculateTotalExamenes(array $examenes, string $tipoPrecio): float
    
    /**
     * Calcula el total de gastos
     */
    public function calculateTotalGastos(array $gastos): float
    
    /**
     * Calcula el total neto
     */
    public function calculateTotalNeto(float $totalExamenes, float $totalConsultas, float $totalGastos): float
    
    /**
     * Determina el estado según fecha_pago
     */
    public function determineEstado(?string $fechaPago): string
}
```

#### DashboardService
```php
class DashboardService
{
    /**
     * Calcula métricas del dashboard según filtros
     */
    public function getMetrics(array $filters): array
    
    /**
     * Obtiene datos para gráfico de ingresos vs gastos
     */
    public function getIngresosVsGastosChart(array $filters): array
    
    /**
     * Obtiene datos para gráfico por clínica
     */
    public function getTotalesPorClinicaChart(array $filters): array
    
    /**
     * Obtiene datos para gráfico pagados vs pendientes
     */
    public function getPagadosVsPendientesChart(array $filters): array
}
```

### Form Requests

#### StoreRepaseRequest
```php
class StoreRepaseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'clinica_id' => 'required|exists:clinicas,id',
            'fecha' => 'required|date|date_format:Y-m-d',
            'fecha_pago' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha',
            'tipo_precio' => 'required|in:sin_nota,con_nota',
            'estado' => 'required|in:pendiente,pagado',
            'total_consultas' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:1000',
            'examenes' => 'required|array|min:1',
            'examenes.*.examen_id' => 'required|exists:examenes,id',
            'examenes.*.cantidad' => 'required|integer|min:1',
            'gastos' => 'nullable|array',
            'gastos.*.tipo' => 'required|in:doctor,tecnico,laudos,gasolina,extra',
            'gastos.*.descripcion' => 'required_if:gastos.*.tipo,extra|nullable|string|min:3',
            'gastos.*.monto' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
        ];
    }
}
```

### Models

#### Clinica
```php
class Clinica extends Model
{
    protected $fillable = ['nombre', 'direccion', 'telefono'];
    
    public function repases(): HasMany
}
```

#### Examen
```php
class Examen extends Model
{
    protected $fillable = ['nombre', 'precio_sin_nota', 'precio_con_nota'];
    
    protected $casts = [
        'precio_sin_nota' => 'decimal:2',
        'precio_con_nota' => 'decimal:2',
    ];
    
    public function repaseExamenes(): HasMany
}
```

#### Repase
```php
class Repase extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'clinica_id', 'fecha', 'fecha_pago', 'estado', 'tipo_precio',
        'total_examenes', 'total_consultas', 'total_gastos', 'total_neto',
        'observaciones'
    ];
    
    protected $casts = [
        'fecha' => 'date',
        'fecha_pago' => 'date',
        'total_examenes' => 'decimal:2',
        'total_consultas' => 'decimal:2',
        'total_gastos' => 'decimal:2',
        'total_neto' => 'decimal:2',
    ];
    
    public function clinica(): BelongsTo
    public function repaseExamenes(): HasMany
    public function gastos(): HasMany
    
    // Scopes para filtrado
    public function scopeByClinica(Builder $query, ?int $clinicaId): Builder
    public function scopeByEstado(Builder $query, ?string $estado): Builder
    public function scopeByDateRange(Builder $query, ?string $from, ?string $to): Builder
}
```

#### RepaseExamen
```php
class RepaseExamen extends Model
{
    protected $table = 'repase_examenes';
    
    protected $fillable = [
        'repase_id', 'examen_id', 'cantidad', 
        'precio_unitario_usado', 'subtotal'
    ];
    
    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario_usado' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];
    
    public function repase(): BelongsTo
    public function examen(): BelongsTo
}
```

#### Gasto
```php
class Gasto extends Model
{
    protected $fillable = ['repase_id', 'tipo', 'descripcion', 'monto'];
    
    protected $casts = [
        'monto' => 'decimal:2',
    ];
    
    public function repase(): BelongsTo
}
```

### Frontend Components

#### Blade Views Structure
```
resources/views/
├── layouts/
│   ├── app.blade.php (layout principal con Tailwind)
│   └── navigation.blade.php
├── clinicas/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── repases/
│   ├── index.blade.php
│   ├── create.blade.php (formulario dinámico con Alpine.js)
│   ├── edit.blade.php
│   └── show.blade.php
├── dashboard/
│   └── index.blade.php (métricas + gráficos Chart.js)
└── calendario/
    └── index.blade.php (FullCalendar)
```

#### JavaScript Components

**Formulario Dinámico de Repase** (Alpine.js):
- Agregar/eliminar exámenes dinámicamente
- Agregar/eliminar gastos dinámicamente
- Cálculo en tiempo real de subtotales
- Cálculo en tiempo real de total_neto
- Validación de campos requeridos

**Dashboard Charts** (Chart.js):
- Gráfico de barras: Ingresos vs Gastos por mes
- Gráfico de pastel: Totales por clínica
- Gráfico de dona: Pagados vs Pendientes

**Calendario** (FullCalendar):
- Vista mensual de repases
- Color coding: rojo (pendiente), verde (pagado)
- Click en evento muestra modal con detalles
- Filtro por clínica

## Data Models

### Database Schema

#### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_email (email)
);
```

#### clinicas
```sql
CREATE TABLE clinicas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    direccion TEXT NULL,
    telefono VARCHAR(20) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_nombre (nombre)
);
```

#### examenes
```sql
CREATE TABLE examenes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    precio_sin_nota DECIMAL(10,2) NOT NULL,
    precio_con_nota DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_nombre (nombre),
    CHECK (precio_sin_nota < precio_con_nota)
);
```

#### repases
```sql
CREATE TABLE repases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinica_id BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    fecha_pago DATE NULL,
    estado ENUM('pendiente', 'pagado') NOT NULL DEFAULT 'pendiente',
    tipo_precio ENUM('sin_nota', 'con_nota') NOT NULL,
    total_examenes DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_consultas DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_gastos DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_neto DECIMAL(10,2) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (clinica_id) REFERENCES clinicas(id) ON DELETE RESTRICT,
    INDEX idx_clinica_id (clinica_id),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado),
    INDEX idx_deleted_at (deleted_at)
);
```

#### repase_examenes
```sql
CREATE TABLE repase_examenes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repase_id BIGINT UNSIGNED NOT NULL,
    examen_id BIGINT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario_usado DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (repase_id) REFERENCES repases(id) ON DELETE CASCADE,
    FOREIGN KEY (examen_id) REFERENCES examenes(id) ON DELETE RESTRICT,
    INDEX idx_repase_id (repase_id),
    INDEX idx_examen_id (examen_id)
);
```

#### gastos
```sql
CREATE TABLE gastos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repase_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('doctor', 'tecnico', 'laudos', 'gasolina', 'extra') NOT NULL,
    descripcion VARCHAR(255) NULL,
    monto DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (repase_id) REFERENCES repases(id) ON DELETE CASCADE,
    INDEX idx_repase_id (repase_id),
    INDEX idx_tipo (tipo)
);
```

### Entity Relationship Diagram

```mermaid
erDiagram
    users ||--o{ repases : "crea (futuro multi-tenancy)"
    clinicas ||--o{ repases : "tiene"
    repases ||--o{ repase_examenes : "contiene"
    repases ||--o{ gastos : "tiene"
    examenes ||--o{ repase_examenes : "usado en"
    
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        timestamps
    }
    
    clinicas {
        bigint id PK
        string nombre
        text direccion
        string telefono
        timestamps
    }
    
    examenes {
        bigint id PK
        string nombre
        decimal precio_sin_nota
        decimal precio_con_nota
        timestamps
    }
    
    repases {
        bigint id PK
        bigint clinica_id FK
        date fecha
        date fecha_pago
        enum estado
        enum tipo_precio
        decimal total_examenes
        decimal total_consultas
        decimal total_gastos
        decimal total_neto
        text observaciones
        timestamps
        timestamp deleted_at
    }
    
    repase_examenes {
        bigint id PK
        bigint repase_id FK
        bigint examen_id FK
        int cantidad
        decimal precio_unitario_usado
        decimal subtotal
        timestamps
    }
    
    gastos {
        bigint id PK
        bigint repase_id FK
        enum tipo
        string descripcion
        decimal monto
        timestamps
    }
```

### Data Seeding

#### ExamenSeeder
```php
class ExamenSeeder extends Seeder
{
    public function run(): void
    {
        $examenes = [
            [
                'nombre' => 'Electroencefalograma c/ mapeamento 3d + foto estimulo',
                'precio_sin_nota' => 200.00,
                'precio_con_nota' => 220.00
            ],
            [
                'nombre' => 'Electroencefalograma c/ mapa',
                'precio_sin_nota' => 120.00,
                'precio_con_nota' => 140.00
            ],
            [
                'nombre' => 'Electroencefalograma',
                'precio_sin_nota' => 100.00,
                'precio_con_nota' => 120.00
            ],
            [
                'nombre' => 'Electroneuromiografia MEMBROS unilateral',
                'precio_sin_nota' => 150.00,
                'precio_con_nota' => 180.00
            ],
            [
                'nombre' => 'Electroneuromiografia FACIAL unilateral',
                'precio_sin_nota' => 170.00,
                'precio_con_nota' => 200.00
            ],
            [
                'nombre' => 'Potencial evocado VISUAL unilateral',
                'precio_sin_nota' => 146.00,
                'precio_con_nota' => 166.00
            ],
            [
                'nombre' => 'Potencial evocado AUDITIVO unilateral',
                'precio_sin_nota' => 146.00,
                'precio_con_nota' => 166.00
            ],
        ];
        
        foreach ($examenes as $examen) {
            Examen::create($examen);
        }
    }
}
```

### Business Logic Calculations

#### Cálculo de Subtotal por Examen
```
subtotal = cantidad × precio_unitario_usado

donde precio_unitario_usado = 
    si tipo_precio == "sin_nota" → examen.precio_sin_nota
    si tipo_precio == "con_nota" → examen.precio_con_nota
```

#### Cálculo de Total Exámenes
```
total_examenes = Σ(subtotal de cada repase_examen)
```

#### Cálculo de Total Gastos
```
total_gastos = Σ(monto de cada gasto)
```

#### Cálculo de Total Neto
```
total_neto = (total_examenes + total_consultas) - total_gastos
```

#### Determinación de Estado
```
estado = fecha_pago != null ? "pagado" : "pendiente"
```

### Invariantes del Sistema

1. **Invariante de Total Exámenes**: Para cualquier repase, recalcular total_examenes desde sus repase_examenes debe producir el mismo valor almacenado (±0.01 por precisión decimal)

2. **Invariante de Total Gastos**: Para cualquier repase, recalcular total_gastos desde sus gastos debe producir el mismo valor almacenado (±0.01 por precisión decimal)

3. **Invariante de Total Neto**: Para cualquier repase, la fórmula `(total_examenes + total_consultas) - total_gastos` debe igualar total_neto (±0.01 por precisión decimal)

4. **Invariante de Precios**: Para cualquier examen, precio_sin_nota < precio_con_nota

5. **Invariante de Estado**: Si fecha_pago existe, entonces estado == "pagado"


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Invariante de Cálculo de Subtotal por Examen

*For any* repase_examen record, el subtotal debe ser igual a cantidad multiplicado por precio_unitario_usado, donde precio_unitario_usado es precio_sin_nota si tipo_precio es "sin_nota", o precio_con_nota si tipo_precio es "con_nota".

**Validates: Requirements 5.3, 5.4, 5.5**

### Property 2: Invariante de Total Exámenes

*For any* repase, recalcular total_examenes como la suma de todos los subtotales de sus repase_examenes debe producir el mismo valor almacenado en el campo total_examenes (con precisión ±0.01).

**Validates: Requirements 5.6, 5.9**

### Property 3: Invariante de Total Gastos

*For any* repase, recalcular total_gastos como la suma de todos los montos de sus gastos debe producir el mismo valor almacenado en el campo total_gastos (con precisión ±0.01).

**Validates: Requirements 7.7, 7.8**

### Property 4: Invariante de Total Neto

*For any* repase, el valor de total_neto debe ser igual a (total_examenes + total_consultas) - total_gastos (con precisión ±0.01).

**Validates: Requirements 8.1, 8.7**


### Property 5: Estado Automático según Fecha de Pago

*For any* repase, si fecha_pago tiene un valor (no es null), entonces el estado debe ser "pagado"; si fecha_pago es null, el estado debe ser "pendiente".

**Validates: Requirements 4.4**

### Property 6: Recálculo Automático al Cambiar Tipo de Precio

*For any* repase existente, si se cambia el tipo_precio de "sin_nota" a "con_nota" o viceversa, todos los subtotales de repase_examenes y el total_examenes deben recalcularse automáticamente usando los precios correspondientes.

**Validates: Requirements 5.7**

### Property 7: Validación de Precios de Exámenes

*For any* examen, el precio_sin_nota debe ser estrictamente menor que precio_con_nota.

**Validates: Requirements 3.5**

### Property 8: Validación de Campos Requeridos en Repase

*For any* intento de crear un repase sin los campos requeridos (clinica_id, fecha, tipo_precio, estado), el sistema debe rechazar la operación con un error de validación.

**Validates: Requirements 4.1**

### Property 9: Validación de Cantidad Positiva

*For any* repase_examen, la cantidad debe ser un entero positivo mayor que cero; intentar crear con cantidad <= 0 debe ser rechazado.

**Validates: Requirements 5.2, 14.7**


### Property 10: Validación de Total Consultas No Negativo

*For any* repase, el valor de total_consultas debe ser un número no negativo (>= 0); intentar crear con valor negativo debe ser rechazado.

**Validates: Requirements 6.3**

### Property 11: Validación de Tipos de Gasto

*For any* gasto, el tipo debe ser exactamente uno de: "doctor", "tecnico", "laudos", "gasolina", "extra"; cualquier otro valor debe ser rechazado.

**Validates: Requirements 7.2, 14.12**

### Property 12: Validación de Descripción para Gasto Extra

*For any* gasto con tipo "extra", el campo descripcion debe ser requerido y tener al menos 3 caracteres; para gastos de otros tipos, descripcion es opcional.

**Validates: Requirements 7.3, 7.4**

### Property 13: Validación de Monto de Gasto

*For any* gasto, el monto debe ser un número decimal positivo con máximo 2 decimales; valores negativos o con más de 2 decimales deben ser rechazados.

**Validates: Requirements 7.5, 14.6**

### Property 14: Soft Delete de Repases Pendientes

*For any* repase con estado "pendiente", al eliminarlo debe aplicarse soft delete (deleted_at se establece) y el registro debe seguir existiendo en la base de datos.

**Validates: Requirements 4.6, 9.5**


### Property 15: Prevención de Eliminación de Repases Pagados

*For any* repase con estado "pagado", intentar eliminarlo debe ser rechazado y el registro debe permanecer intacto.

**Validates: Requirements 9.4**

### Property 16: Atomicidad Transaccional en Creación de Repase

*For any* operación de creación de repase con sus exámenes y gastos relacionados, si cualquier parte de la operación falla, todos los cambios deben revertirse (rollback) y ningún dato debe persistirse en la base de datos.

**Validates: Requirements 4.5, 19.3**

### Property 17: Atomicidad Transaccional en Actualización de Repase

*For any* operación de actualización de repase con sus exámenes y gastos relacionados, si cualquier parte de la operación falla, todos los cambios deben revertirse y el estado anterior debe mantenerse.

**Validates: Requirements 19.2, 19.3**

### Property 18: Validación de Fechas

*For any* repase, la fecha debe ser una fecha válida en formato Y-m-d; si fecha_pago está presente, también debe ser una fecha válida en formato Y-m-d.

**Validates: Requirements 14.3, 14.4**

### Property 19: Validación de Orden de Fechas

*For any* repase con fecha_pago, la fecha_pago debe ser mayor o igual a la fecha del repase.

**Validates: Requirements 14.5**


### Property 20: Validación de Referencias de Foreign Keys

*For any* repase, el clinica_id debe referenciar una clínica existente; para cualquier repase_examen, el examen_id debe referenciar un examen existente; intentar usar IDs inexistentes debe ser rechazado.

**Validates: Requirements 14.8, 14.9**

### Property 21: Validación de Valores Enum

*For any* repase, tipo_precio debe ser "sin_nota" o "con_nota", y estado debe ser "pendiente" o "pagado"; cualquier otro valor debe ser rechazado.

**Validates: Requirements 14.10, 14.11**

### Property 22: Cálculo de Total Ingresos en Dashboard

*For any* conjunto de repases (filtrados o no), el total_ingresos del dashboard debe ser igual a la suma de (total_examenes + total_consultas) de todos los repases en el conjunto.

**Validates: Requirements 10.1**

### Property 23: Cálculo de Total Gastos en Dashboard

*For any* conjunto de repases (filtrados o no), el total_gastos del dashboard debe ser igual a la suma de total_gastos de todos los repases en el conjunto.

**Validates: Requirements 10.2**

### Property 24: Cálculo de Total Neto en Dashboard

*For any* conjunto de repases (filtrados o no), el total_neto del dashboard debe ser igual a total_ingresos menos total_gastos.

**Validates: Requirements 10.3**


### Property 25: Cálculo de Total Pendiente en Dashboard

*For any* conjunto de repases, el total_pendiente del dashboard debe ser igual a la suma de total_neto de todos los repases donde estado = "pendiente".

**Validates: Requirements 10.4**

### Property 26: Cálculo de Total Pagado en Dashboard

*For any* conjunto de repases, el total_pagado del dashboard debe ser igual a la suma de total_neto de todos los repases donde estado = "pagado".

**Validates: Requirements 10.5**

### Property 27: Filtrado por Clínica

*For any* filtro de clínica aplicado en el dashboard, todas las métricas deben calcularse usando únicamente los repases que pertenecen a esa clínica específica.

**Validates: Requirements 10.6**

### Property 28: Filtrado por Estado

*For any* filtro de estado aplicado en el dashboard, todas las métricas deben calcularse usando únicamente los repases que tienen ese estado específico.

**Validates: Requirements 10.7**

### Property 29: Filtrado por Rango de Fechas

*For any* filtro de rango de fechas aplicado en el dashboard, todas las métricas deben calcularse usando únicamente los repases cuya fecha cae dentro del rango especificado (inclusive).

**Validates: Requirements 10.8**


### Property 30: Combinación de Filtros con Lógica AND

*For any* combinación de múltiples filtros (clínica, estado, rango de fechas) aplicados simultáneamente, el sistema debe aplicar todos los filtros usando lógica AND, retornando solo los repases que cumplen todos los criterios.

**Validates: Requirements 10.9**

### Property 31: CRUD Round Trip de Clínicas

*For any* clínica creada con datos válidos, leer la clínica inmediatamente después debe retornar exactamente los mismos datos; actualizar la clínica y leerla nuevamente debe retornar los datos actualizados.

**Validates: Requirements 2.2, 2.3, 2.4**

### Property 32: Eliminación de Clínicas

*For any* clínica sin repases asociados, eliminarla debe resultar en que la clínica ya no exista en la base de datos.

**Validates: Requirements 2.5**

### Property 33: Cascade Delete de Relaciones

*For any* repase eliminado (soft delete), sus gastos y repase_examenes relacionados deben eliminarse en cascada automáticamente.

**Validates: Requirements 18.3**

### Property 34: Prevención de N+1 Queries

*For any* operación que carga múltiples repases con sus relaciones (clínica, exámenes, gastos), el número de queries ejecutadas debe ser constante (3-4 queries) independientemente del número de repases, usando eager loading.

**Validates: Requirements 15.7**


### Property 35: Validación de Cálculos en Backend

*For any* repase enviado con valores calculados incorrectos (subtotales, total_examenes, total_gastos, total_neto), el backend debe recalcular los valores correctos y rechazar la operación si los valores enviados no coinciden con los calculados.

**Validates: Requirements 5.8, 8.6**

### Property 36: Repases con Múltiples Exámenes

*For any* repase, debe ser posible agregar uno o más exámenes (mínimo 1); intentar crear un repase sin exámenes debe ser rechazado.

**Validates: Requirements 5.1**

### Property 37: Repases con Cero o Más Gastos

*For any* repase, debe ser posible crearlo con cero gastos, con un gasto, o con múltiples gastos; todos los casos deben funcionar correctamente.

**Validates: Requirements 7.1**

### Property 38: Actualización de Repases

*For any* repase existente, debe ser posible actualizarlo modificando sus datos, exámenes y gastos; los cambios deben persistirse correctamente y los totales deben recalcularse.

**Validates: Requirements 9.3**

### Property 39: Redirección de Usuarios No Autenticados

*For any* ruta protegida del sistema, un usuario no autenticado que intente acceder debe ser redirigido a la página de login.

**Validates: Requirements 1.5**



## Error Handling

### Estrategia General de Manejo de Errores

El sistema implementa una estrategia de manejo de errores en múltiples capas:

1. **Validación de Entrada (FormRequests)**: Primera línea de defensa
2. **Validación de Negocio (Services)**: Lógica de negocio y cálculos
3. **Integridad de Base de Datos (Constraints)**: Foreign keys, checks, unique
4. **Manejo de Transacciones**: Rollback automático en caso de fallo
5. **Logging**: Registro de errores para debugging

### Tipos de Errores y Respuestas

#### Errores de Validación (422 Unprocessable Entity)

**Escenarios:**
- Campos requeridos faltantes
- Formatos de fecha inválidos
- Valores fuera de rango (negativos, decimales incorrectos)
- Referencias a IDs inexistentes
- Valores enum inválidos

**Respuesta:**
```php
return redirect()->back()
    ->withErrors($validator)
    ->withInput();
```

**Mensajes en Español:**
- "El campo clínica es obligatorio"
- "La fecha debe ser una fecha válida"
- "La fecha de pago debe ser posterior o igual a la fecha del repase"
- "El monto debe ser un número positivo con máximo 2 decimales"
- "La cantidad debe ser un número entero mayor que cero"


#### Errores de Lógica de Negocio (403 Forbidden / 400 Bad Request)

**Escenarios:**
- Intentar eliminar un repase pagado
- Cálculos que no coinciden con los valores enviados
- Operaciones no permitidas según el estado

**Respuesta:**
```php
return redirect()->back()
    ->with('error', 'No se puede eliminar un repase que ya ha sido pagado')
    ->withInput();
```

**Mensajes:**
- "No se puede eliminar un repase que ya ha sido pagado"
- "Los cálculos enviados no coinciden con los valores esperados"
- "No se puede cambiar el estado de un repase eliminado"

#### Errores de Integridad de Base de Datos (500 Internal Server Error)

**Escenarios:**
- Violación de foreign key constraints
- Violación de unique constraints
- Violación de check constraints

**Manejo:**
```php
try {
    // Operación de base de datos
} catch (\Illuminate\Database\QueryException $e) {
    if ($e->getCode() === '23000') {
        // Violación de integridad referencial
        return redirect()->back()
            ->with('error', 'No se puede eliminar porque tiene registros relacionados')
            ->withInput();
    }
    throw $e;
}
```


#### Errores de Transacción (500 Internal Server Error)

**Escenarios:**
- Fallo en cualquier operación dentro de una transacción
- Deadlocks de base de datos
- Timeouts de conexión

**Manejo:**
```php
DB::beginTransaction();
try {
    // Crear repase
    // Crear exámenes
    // Crear gastos
    DB::commit();
    return redirect()->route('repases.show', $repase)
        ->with('success', 'Repase creado exitosamente');
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Error al crear repase: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
        'data' => $request->all()
    ]);
    return redirect()->back()
        ->with('error', 'Ocurrió un error al guardar el repase. Por favor intente nuevamente.')
        ->withInput();
}
```

#### Errores de Autenticación (401 Unauthorized)

**Escenarios:**
- Usuario no autenticado intenta acceder a ruta protegida
- Sesión expirada

**Respuesta:**
```php
// Manejado automáticamente por middleware auth
return redirect()->route('login')
    ->with('error', 'Debe iniciar sesión para acceder a esta página');
```


#### Errores de Recurso No Encontrado (404 Not Found)

**Escenarios:**
- ID de repase, clínica o examen no existe
- Registro fue soft deleted

**Respuesta:**
```php
// Manejado automáticamente por route model binding
abort(404, 'El repase solicitado no existe');
```

### Logging de Errores

**Niveles de Log:**
- **ERROR**: Fallos de transacciones, excepciones no esperadas
- **WARNING**: Intentos de operaciones no permitidas
- **INFO**: Operaciones exitosas importantes (creación, actualización, eliminación)

**Formato de Log:**
```php
Log::error('Error en transacción de repase', [
    'user_id' => auth()->id(),
    'operation' => 'create_repase',
    'data' => $sanitizedData,
    'exception' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

### Mensajes Flash para Usuario

**Éxito:**
```php
->with('success', 'Repase creado exitosamente')
->with('success', 'Clínica actualizada correctamente')
->with('success', 'Repase eliminado')
```

**Error:**
```php
->with('error', 'Ocurrió un error al procesar su solicitud')
->with('error', 'No se puede eliminar un repase pagado')
```

**Advertencia:**
```php
->with('warning', 'Algunos cálculos fueron ajustados automáticamente')
```



## Testing Strategy

### Enfoque Dual de Testing

El sistema implementa una estrategia de testing comprehensiva que combina:

1. **Unit Tests**: Para casos específicos, ejemplos concretos y edge cases
2. **Property-Based Tests**: Para propiedades universales que deben cumplirse con cualquier entrada válida

Ambos tipos de tests son complementarios y necesarios:
- Los unit tests capturan bugs concretos y validan ejemplos específicos
- Los property tests verifican correctness general a través de cientos de inputs aleatorios

### Librería de Property-Based Testing

**Librería seleccionada**: No existe una librería madura de PBT para PHP comparable a QuickCheck (Haskell) o Hypothesis (Python). Por lo tanto, implementaremos un enfoque híbrido:

1. **Para propiedades críticas de cálculo**: Implementar generadores simples de datos aleatorios en PHPUnit
2. **Para validaciones**: Unit tests con múltiples casos de prueba
3. **Configuración**: Mínimo 100 iteraciones por property test

### Estructura de Tests

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── ClinicaTest.php
│   │   ├── ExamenTest.php
│   │   ├── RepaseTest.php
│   │   ├── RepaseExamenTest.php
│   │   └── GastoTest.php
│   ├── Services/
│   │   ├── RepaseServiceTest.php
│   │   └── DashboardServiceTest.php
│   └── Calculations/
│       ├── SubtotalCalculationTest.php
│       ├── TotalExamenesCalculationTest.php
│       ├── TotalGastosCalculationTest.php
│       └── TotalNetoCalculationTest.php
├── Feature/
│   ├── Auth/
│   │   └── AuthenticationTest.php
│   ├── Clinicas/
│   │   └── ClinicaCRUDTest.php
│   ├── Repases/
│   │   ├── RepaseCreationTest.php
│   │   ├── RepaseUpdateTest.php
│   │   ├── RepaseDeletionTest.php
│   │   └── RepaseTransactionTest.php
│   ├── Dashboard/
│   │   ├── DashboardMetricsTest.php
│   │   └── DashboardFiltersTest.php
│   └── Validation/
│       ├── RepaseValidationTest.php
│       └── GastoValidationTest.php
└── Property/
    ├── RepaseInvariantsTest.php
    ├── CalculationPropertiesTest.php
    ├── ValidationPropertiesTest.php
    └── TransactionPropertiesTest.php
```


### Property-Based Tests

Cada property test debe:
- Ejecutar mínimo 100 iteraciones con datos aleatorios
- Incluir un comentario referenciando la propiedad del diseño
- Usar el formato de tag: `Feature: contabilidad-medica, Property {number}: {property_text}`

#### Ejemplo de Property Test

```php
/**
 * Feature: contabilidad-medica, Property 1: Invariante de Cálculo de Subtotal por Examen
 * 
 * For any repase_examen record, el subtotal debe ser igual a cantidad 
 * multiplicado por precio_unitario_usado.
 */
public function test_subtotal_calculation_invariant(): void
{
    $iterations = 100;
    
    for ($i = 0; $i < $iterations; $i++) {
        // Generar datos aleatorios
        $cantidad = rand(1, 50);
        $precioSinNota = rand(50, 500) + (rand(0, 99) / 100);
        $precioConNota = $precioSinNota + rand(10, 50);
        $tipoPrecio = rand(0, 1) ? 'sin_nota' : 'con_nota';
        
        $precioUsado = $tipoPrecio === 'sin_nota' ? $precioSinNota : $precioConNota;
        $subtotalEsperado = $cantidad * $precioUsado;
        
        // Crear examen y repase
        $examen = Examen::factory()->create([
            'precio_sin_nota' => $precioSinNota,
            'precio_con_nota' => $precioConNota,
        ]);
        
        $repase = Repase::factory()->create(['tipo_precio' => $tipoPrecio]);
        
        $repaseExamen = RepaseExamen::create([
            'repase_id' => $repase->id,
            'examen_id' => $examen->id,
            'cantidad' => $cantidad,
            'precio_unitario_usado' => $precioUsado,
            'subtotal' => $subtotalEsperado,
        ]);
        
        // Verificar invariante
        $this->assertEquals(
            $subtotalEsperado,
            $repaseExamen->subtotal,
            "Subtotal debe ser cantidad × precio_unitario_usado (iteración $i)",
            0.01
        );
    }
}
```


```php
/**
 * Feature: contabilidad-medica, Property 2: Invariante de Total Exámenes
 * 
 * For any repase, recalcular total_examenes como la suma de todos los 
 * subtotales debe producir el mismo valor almacenado.
 */
public function test_total_examenes_invariant(): void
{
    $iterations = 100;
    
    for ($i = 0; $i < $iterations; $i++) {
        $repase = Repase::factory()->create();
        
        // Generar cantidad aleatoria de exámenes (1-10)
        $numExamenes = rand(1, 10);
        $sumaSubtotales = 0;
        
        for ($j = 0; $j < $numExamenes; $j++) {
            $cantidad = rand(1, 20);
            $precio = rand(50, 300) + (rand(0, 99) / 100);
            $subtotal = $cantidad * $precio;
            $sumaSubtotales += $subtotal;
            
            RepaseExamen::factory()->create([
                'repase_id' => $repase->id,
                'cantidad' => $cantidad,
                'precio_unitario_usado' => $precio,
                'subtotal' => $subtotal,
            ]);
        }
        
        // Actualizar total_examenes
        $repase->update(['total_examenes' => $sumaSubtotales]);
        
        // Verificar invariante: recalcular debe dar el mismo resultado
        $totalRecalculado = $repase->repaseExamenes()->sum('subtotal');
        
        $this->assertEquals(
            $repase->total_examenes,
            $totalRecalculado,
            "Total exámenes debe ser suma de subtotales (iteración $i)",
            0.01
        );
    }
}
```


```php
/**
 * Feature: contabilidad-medica, Property 4: Invariante de Total Neto
 * 
 * For any repase, el valor de total_neto debe ser igual a 
 * (total_examenes + total_consultas) - total_gastos.
 */
public function test_total_neto_invariant(): void
{
    $iterations = 100;
    
    for ($i = 0; $i < $iterations; $i++) {
        // Generar valores aleatorios
        $totalExamenes = rand(100, 5000) + (rand(0, 99) / 100);
        $totalConsultas = rand(0, 2000) + (rand(0, 99) / 100);
        $totalGastos = rand(0, 3000) + (rand(0, 99) / 100);
        
        $totalNetoEsperado = ($totalExamenes + $totalConsultas) - $totalGastos;
        
        $repase = Repase::factory()->create([
            'total_examenes' => $totalExamenes,
            'total_consultas' => $totalConsultas,
            'total_gastos' => $totalGastos,
            'total_neto' => $totalNetoEsperado,
        ]);
        
        // Verificar invariante
        $totalNetoCalculado = ($repase->total_examenes + $repase->total_consultas) 
                            - $repase->total_gastos;
        
        $this->assertEquals(
            $repase->total_neto,
            $totalNetoCalculado,
            "Total neto debe ser (examenes + consultas) - gastos (iteración $i)",
            0.01
        );
    }
}
```

### Unit Tests

Los unit tests se enfocan en:
- Casos específicos y ejemplos concretos
- Edge cases (valores límite, casos especiales)
- Integración entre componentes
- Validaciones específicas


#### Ejemplos de Unit Tests

```php
/** Test de ejemplo específico: Seeder crea exactamente 7 exámenes */
public function test_examen_seeder_creates_seven_records(): void
{
    $this->seed(ExamenSeeder::class);
    
    $this->assertDatabaseCount('examenes', 7);
    
    // Verificar que los nombres específicos existen
    $this->assertDatabaseHas('examenes', [
        'nombre' => 'Electroencefalograma c/ mapeamento 3d + foto estimulo',
        'precio_sin_nota' => 200.00,
        'precio_con_nota' => 220.00,
    ]);
}

/** Test de edge case: Repase con cero gastos */
public function test_repase_can_be_created_with_zero_gastos(): void
{
    $repase = Repase::factory()->create([
        'total_examenes' => 500.00,
        'total_consultas' => 200.00,
        'total_gastos' => 0.00,
        'total_neto' => 700.00,
    ]);
    
    $this->assertEquals(0, $repase->gastos()->count());
    $this->assertEquals(700.00, $repase->total_neto);
}

/** Test de validación: Cantidad debe ser positiva */
public function test_cantidad_must_be_positive(): void
{
    $this->actingAs(User::factory()->create());
    
    $response = $this->post(route('repases.store'), [
        'clinica_id' => Clinica::factory()->create()->id,
        'fecha' => now()->format('Y-m-d'),
        'tipo_precio' => 'sin_nota',
        'estado' => 'pendiente',
        'total_consultas' => 0,
        'examenes' => [
            [
                'examen_id' => Examen::factory()->create()->id,
                'cantidad' => 0, // Inválido
            ]
        ],
    ]);
    
    $response->assertSessionHasErrors('examenes.0.cantidad');
}
```


```php
/** Test de transacción: Rollback en caso de error */
public function test_repase_creation_rolls_back_on_error(): void
{
    $this->actingAs(User::factory()->create());
    
    // Simular error en medio de transacción usando un observer
    Gasto::creating(function ($gasto) {
        if ($gasto->tipo === 'doctor') {
            throw new \Exception('Simulated error');
        }
    });
    
    $initialRepaseCount = Repase::count();
    $initialGastoCount = Gasto::count();
    
    try {
        $this->post(route('repases.store'), [
            'clinica_id' => Clinica::factory()->create()->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_precio' => 'sin_nota',
            'estado' => 'pendiente',
            'total_consultas' => 100,
            'examenes' => [
                [
                    'examen_id' => Examen::factory()->create()->id,
                    'cantidad' => 1,
                ]
            ],
            'gastos' => [
                [
                    'tipo' => 'doctor',
                    'monto' => 50.00,
                ]
            ],
        ]);
    } catch (\Exception $e) {
        // Esperado
    }
    
    // Verificar que no se creó nada (rollback exitoso)
    $this->assertEquals($initialRepaseCount, Repase::count());
    $this->assertEquals($initialGastoCount, Gasto::count());
}

/** Test de soft delete */
public function test_repase_pendiente_can_be_soft_deleted(): void
{
    $repase = Repase::factory()->create(['estado' => 'pendiente']);
    
    $repase->delete();
    
    $this->assertSoftDeleted('repases', ['id' => $repase->id]);
    $this->assertNotNull($repase->fresh()->deleted_at);
}
```


```php
/** Test de prevención de eliminación de repases pagados */
public function test_repase_pagado_cannot_be_deleted(): void
{
    $this->actingAs(User::factory()->create());
    
    $repase = Repase::factory()->create([
        'estado' => 'pagado',
        'fecha_pago' => now(),
    ]);
    
    $response = $this->delete(route('repases.destroy', $repase));
    
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('repases', ['id' => $repase->id]);
}

/** Test de N+1 queries prevention */
public function test_repases_index_prevents_n_plus_one_queries(): void
{
    // Crear 20 repases con relaciones
    Repase::factory()
        ->count(20)
        ->has(RepaseExamen::factory()->count(3))
        ->has(Gasto::factory()->count(2))
        ->create();
    
    // Contar queries
    DB::enableQueryLog();
    
    $this->actingAs(User::factory()->create())
        ->get(route('repases.index'));
    
    $queries = DB::getQueryLog();
    
    // Debe ser ~3-4 queries independientemente del número de repases
    // 1: SELECT repases, 2: SELECT clinicas, 3: SELECT repase_examenes, 4: SELECT gastos
    $this->assertLessThanOrEqual(5, count($queries), 
        'Debe usar eager loading para prevenir N+1');
}
```


### Cobertura de Testing

**Objetivo de Cobertura**: Mínimo 80% de cobertura de código

**Áreas Críticas (100% de cobertura requerida):**
- RepaseService (cálculos y transacciones)
- DashboardService (métricas y filtros)
- FormRequests (validaciones)
- Modelos (relaciones y scopes)

**Áreas de Cobertura Estándar (80%):**
- Controllers (lógica de negocio delegada a services)
- Seeders y Factories

**Herramientas:**
- PHPUnit para ejecución de tests
- Xdebug o PCOV para cobertura de código
- Laravel Dusk (opcional) para tests E2E de UI

### Configuración de PHPUnit

```xml
<!-- phpunit.xml -->
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Property">
            <directory suffix="Test.php">./tests/Property</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory>./app/Console</directory>
            <file>./app/Http/Middleware/RedirectIfAuthenticated.php</file>
        </exclude>
    </coverage>
</phpunit>
```


### Comandos de Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar solo unit tests
php artisan test --testsuite=Unit

# Ejecutar solo feature tests
php artisan test --testsuite=Feature

# Ejecutar solo property tests
php artisan test --testsuite=Property

# Ejecutar con cobertura
php artisan test --coverage

# Ejecutar con cobertura mínima requerida
php artisan test --coverage --min=80

# Ejecutar tests específicos
php artisan test --filter=test_total_neto_invariant

# Ejecutar en paralelo (más rápido)
php artisan test --parallel
```

### Factories para Testing

```php
// ClinicaFactory
class ClinicaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'direccion' => $this->faker->address(),
            'telefono' => $this->faker->phoneNumber(),
        ];
    }
}

// RepaseFactory
class RepaseFactory extends Factory
{
    public function definition(): array
    {
        $totalExamenes = $this->faker->randomFloat(2, 100, 5000);
        $totalConsultas = $this->faker->randomFloat(2, 0, 2000);
        $totalGastos = $this->faker->randomFloat(2, 0, 3000);
        
        return [
            'clinica_id' => Clinica::factory(),
            'fecha' => $this->faker->date(),
            'fecha_pago' => null,
            'estado' => 'pendiente',
            'tipo_precio' => $this->faker->randomElement(['sin_nota', 'con_nota']),
            'total_examenes' => $totalExamenes,
            'total_consultas' => $totalConsultas,
            'total_gastos' => $totalGastos,
            'total_neto' => ($totalExamenes + $totalConsultas) - $totalGastos,
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
    
    public function pagado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pagado',
            'fecha_pago' => $this->faker->dateTimeBetween($attributes['fecha'], 'now'),
        ]);
    }
}
```

### Resumen de Estrategia de Testing

1. **Property-Based Tests**: 39 propiedades con 100+ iteraciones cada una
2. **Unit Tests**: Casos específicos, edge cases, ejemplos concretos
3. **Feature Tests**: Integración de componentes, flujos completos
4. **Cobertura**: Mínimo 80% general, 100% en áreas críticas
5. **Ejecución**: Automatizada en CI/CD pipeline
6. **Mantenimiento**: Tests actualizados con cada cambio de requisitos

---

**Fin del Documento de Diseño**
