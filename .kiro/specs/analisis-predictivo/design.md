# Design Document - Módulo de Análisis Predictivo

## Overview

El Módulo de Análisis Predictivo es una extensión avanzada del Sistema de Contabilidad Médica que proporciona capacidades de machine learning y análisis de tendencias para optimizar la toma de decisiones financieras y operativas. Este módulo utiliza algoritmos predictivos para generar proyecciones de ingresos, gastos y capacidad operativa basados en datos históricos del sistema existente.

### Objetivos del Sistema

- **Predicción de Ingresos**: Proyectar ingresos futuros usando regresión lineal, promedio móvil y análisis estacional
- **Forecasting de Gastos**: Predecir gastos futuros con alertas tempranas de sobrecosto
- **Análisis de Capacidad**: Evaluar límites operativos y proyectar fechas de saturación
- **Dashboard Interactivo**: Visualizar predicciones con gráficos interactivos y filtros dinámicos
- **Actualización Automática**: Mantener modelos predictivos actualizados mediante jobs programados

### Integración con Sistema Existente

El módulo se integra seamlessly con:
- **Modelos Eloquent**: Repase, Clinica, Examen, RepaseExamen, Gasto
- **Stack Tecnológico**: Laravel 11, SQLite, Tailwind CSS, Chart.js, Alpine.js
- **Arquitectura**: Patrón MVC con capa de servicios especializada
- **Autenticación**: Sistema de roles existente con middleware de autorización

## Architecture

### Patrón Arquitectónico

El módulo sigue una **arquitectura en capas** con separación clara de responsabilidades:

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ Dashboard Views │  │ Alpine.js       │  │ Chart.js     │ │
│  │ (Blade)         │  │ Components      │  │ Visualizations│ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                    Controller Layer                          │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ PredictiveController │ │ API Endpoints   │  │ Middleware   │ │
│  │ (HTTP Handlers) │  │ (Real-time)     │  │ (Auth/Admin) │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                    Service Layer                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ IncomePredictor │  │ TrendDetector   │  │ ExpenseForecaster│ │
│  │ Service         │  │ Service         │  │ Service      │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ CapacityAnalyzer│  │ ExportService   │  │ CacheService │ │
│  │ Service         │  │                 │  │              │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                    Data Layer                                │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ Eloquent Models │  │ Prediction      │  │ Cache        │ │
│  │ (Existing)      │  │ Configuration   │  │ Storage      │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Principios de Diseño

1. **Single Responsibility**: Cada servicio tiene una responsabilidad específica
2. **Dependency Injection**: Servicios inyectados para facilitar testing
3. **Caching Strategy**: Caché inteligente para optimizar rendimiento
4. **Job Queue**: Procesamiento asíncrono para actualizaciones automáticas
5. **Configuration Driven**: Parámetros configurables sin cambios de código

## Components and Interfaces

### Core Services

#### 1. IncomePredictor Service

**Responsabilidad**: Generar predicciones de ingresos usando múltiples algoritmos

```php
interface IncomePredictorInterface
{
    public function predictIncome(array $filters, int $months): PredictionResult;
    public function getAvailableAlgorithms(): array;
    public function calculateAccuracy(string $algorithm, array $historicalData): float;
}

class IncomePredictor implements IncomePredictorInterface
{
    public function predictIncome(array $filters, int $months): PredictionResult
    {
        // Implementa regresión lineal, promedio móvil y análisis estacional
        // Retorna predicciones para 3, 6 y 12 meses
    }
}
```

**Algoritmos Implementados**:
- **Regresión Lineal**: Para tendencias lineales a largo plazo
- **Promedio Móvil**: Para suavizado de fluctuaciones estacionales
- **Análisis Estacional**: Para patrones cíclicos anuales

#### 2. TrendDetector Service

**Responsabilidad**: Identificar patrones estacionales y tendencias

```php
interface TrendDetectorInterface
{
    public function detectSeasonalPatterns(array $data, int $minMonths = 24): SeasonalAnalysis;
    public function calculateTrendStrength(array $data): float;
    public function compareYearOverYear(array $currentYear, array $previousYear): ComparisonResult;
}

class TrendDetector implements TrendDetectorInterface
{
    public function detectSeasonalPatterns(array $data, int $minMonths = 24): SeasonalAnalysis
    {
        // Descomposición temporal: tendencia + estacionalidad + ruido
        // Calcula porcentajes de variación por mes
    }
}
```

#### 3. ExpenseForecaster Service

**Responsabilidad**: Predecir gastos futuros y generar alertas

```php
interface ExpenseForecasterInterface
{
    public function forecastExpenses(array $filters, int $months): ExpenseForecast;
    public function calculateCorrelation(array $incomes, array $expenses): float;
    public function checkThresholdAlerts(ExpenseForecast $forecast): array;
}

class ExpenseForecaster implements ExpenseForecasterInterface
{
    public function forecastExpenses(array $filters, int $months): ExpenseForecast
    {
        // Proyecta gastos por categoría
        // Calcula correlaciones con ingresos
        // Genera alertas automáticas
    }
}
```

#### 4. CapacityAnalyzer Service

**Responsabilidad**: Analizar capacidad operativa y proyectar saturación

```php
interface CapacityAnalyzerInterface
{
    public function analyzeCurrentCapacity(array $filters): CapacityAnalysis;
    public function projectSaturationDate(array $filters): ?Carbon;
    public function recommendActions(CapacityAnalysis $analysis): array;
}

class CapacityAnalyzer implements CapacityAnalyzerInterface
{
    public function analyzeCurrentCapacity(array $filters): CapacityAnalysis
    {
        // Calcula utilización actual por clínica
        // Identifica cuellos de botella
        // Proyecta crecimiento futuro
    }
}
```

### Controller Layer

#### PredictiveController

**Responsabilidad**: Manejar requests HTTP y coordinar servicios

```php
class PredictiveController extends Controller
{
    public function __construct(
        private IncomePredictorInterface $incomePredictor,
        private TrendDetectorInterface $trendDetector,
        private ExpenseForecasterInterface $expenseForecaster,
        private CapacityAnalyzerInterface $capacityAnalyzer,
        private ExportServiceInterface $exportService
    ) {}

    public function dashboard(): View
    {
        // Dashboard principal con resumen de todas las predicciones
    }

    public function incomeProjection(Request $request): View
    {
        // Vista de predicción de ingresos con gráficos
    }

    public function expenseForecast(Request $request): View
    {
        // Vista de forecasting de gastos con alertas
    }

    public function capacityAnalysis(Request $request): View
    {
        // Vista de análisis de capacidad operativa
    }

    public function trendAnalysis(Request $request): View
    {
        // Vista de análisis de tendencias estacionales
    }
}
```

### API Endpoints

**Real-time Data Updates**:

```php
Route::prefix('api/predictive')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/income/{months}', [PredictiveApiController::class, 'getIncomeProjection']);
    Route::get('/expenses/{months}', [PredictiveApiController::class, 'getExpenseForecast']);
    Route::get('/capacity/current', [PredictiveApiController::class, 'getCurrentCapacity']);
    Route::get('/trends/seasonal', [PredictiveApiController::class, 'getSeasonalTrends']);
    Route::post('/configuration', [PredictiveApiController::class, 'updateConfiguration']);
});
```

### Job Queue System

#### Automated Model Updates

```php
class UpdatePredictiveModelsJob implements ShouldQueue
{
    public function handle(): void
    {
        // Ejecuta diariamente a las 02:00 AM
        // Actualiza todos los modelos predictivos
        // Recalcula métricas de precisión
        // Envía notificaciones si hay errores
    }
}

class ValidateModelAccuracyJob implements ShouldQueue
{
    public function handle(): void
    {
        // Ejecuta semanalmente
        // Compara predicciones pasadas con valores reales
        // Calcula MAPE y RMSE
        // Sugiere ajustes de parámetros si precisión < 70%
    }
}
```

## Data Models

### Nuevas Tablas para Configuración

#### prediction_configurations

```sql
CREATE TABLE prediction_configurations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key VARCHAR(255) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    description TEXT,
    validation_rules TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Configuraciones por defecto
INSERT INTO prediction_configurations (key, value, description, validation_rules) VALUES
('expense_alert_threshold', '25', 'Umbral de alerta para gastos (% sobre promedio)', 'numeric|min:1|max:50'),
('active_algorithms', '["linear_regression","moving_average","seasonal"]', 'Algoritmos activos', 'json'),
('cache_duration_minutes', '60', 'Duración del caché en minutos', 'numeric|min:5|max:1440'),
('min_historical_months', '12', 'Mínimo de meses históricos requeridos', 'numeric|min:6|max:60'),
('capacity_alert_threshold', '85', 'Umbral de alerta de capacidad (%)', 'numeric|min:50|max:95');
```

#### prediction_cache

```sql
CREATE TABLE prediction_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cache_key VARCHAR(255) NOT NULL UNIQUE,
    prediction_type VARCHAR(100) NOT NULL,
    filters_hash VARCHAR(255) NOT NULL,
    result_data TEXT NOT NULL, -- JSON
    accuracy_metrics TEXT, -- JSON con MAPE, RMSE
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_prediction_cache_type_hash ON prediction_cache(prediction_type, filters_hash);
CREATE INDEX idx_prediction_cache_expires ON prediction_cache(expires_at);
```

#### prediction_accuracy_log

```sql
CREATE TABLE prediction_accuracy_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prediction_type VARCHAR(100) NOT NULL,
    algorithm VARCHAR(100) NOT NULL,
    prediction_date DATE NOT NULL,
    actual_date DATE NOT NULL,
    predicted_value DECIMAL(15,2) NOT NULL,
    actual_value DECIMAL(15,2) NOT NULL,
    absolute_error DECIMAL(15,2) NOT NULL,
    percentage_error DECIMAL(8,4) NOT NULL,
    created_at TIMESTAMP
);

CREATE INDEX idx_accuracy_log_type_algorithm ON prediction_accuracy_log(prediction_type, algorithm);
CREATE INDEX idx_accuracy_log_dates ON prediction_accuracy_log(prediction_date, actual_date);
```

### Extensiones a Modelos Existentes

#### Nuevos Scopes en Modelo Repase

```php
class Repase extends Model
{
    // Scopes existentes...

    public function scopeForPrediction($query, array $filters = [])
    {
        return $query->with(['clinica', 'examenes', 'gastos'])
                    ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => 
                        $q->where('fecha', '>=', $fecha))
                    ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => 
                        $q->where('fecha', '<=', $fecha))
                    ->when($filters['clinica_id'] ?? null, fn($q, $id) => 
                        $q->where('clinica_id', $id));
    }

    public function scopeGroupedByMonth($query)
    {
        return $query->selectRaw('
                DATE_FORMAT(fecha, "%Y-%m") as month,
                COUNT(*) as total_repases,
                SUM(total) as total_ingresos,
                clinica_id
            ')
            ->groupBy('month', 'clinica_id')
            ->orderBy('month');
    }
}
```

### Índices de Optimización

```sql
-- Índices para consultas predictivas
CREATE INDEX idx_repases_fecha_clinica_prediction ON repases(fecha, clinica_id, total);
CREATE INDEX idx_repases_month_year ON repases(strftime('%Y-%m', fecha));
CREATE INDEX idx_gastos_repase_tipo ON gastos(repase_id, tipo, monto);
CREATE INDEX idx_repase_examenes_prediction ON repase_examenes(repase_id, examen_id, precio);
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, I identified several areas where properties can be consolidated to eliminate redundancy:

**Consolidation Opportunities:**
- Properties for time period generation (1.1, 3.1) can be combined into a single comprehensive property
- Properties for data sufficiency validation (1.2, 1.4, 2.1, 2.5) can be consolidated
- Properties for threshold alerting (3.2, 4.2) share similar logic and can be unified
- Properties for export functionality (6.1, 6.2, 6.3, 6.4, 6.5) can be combined into comprehensive export properties
- Properties for configuration validation (7.1, 7.4) can be merged
- Properties for accuracy metrics (8.1, 8.2) can be combined

**Final Property Set:**
After reflection, the following properties provide unique validation value without redundancy:

### Property 1: Time Period Projection Generation

*For any* prediction request (income or expense), the system should generate exactly three projections for 3, 6, and 12 months respectively

**Validates: Requirements 1.1, 3.1**

### Property 2: Historical Data Sufficiency Validation

*For any* prediction algorithm, predictions should only be generated when sufficient historical data exists (minimum 12 months for income, 24 months for seasonal analysis)

**Validates: Requirements 1.2, 1.4, 2.1, 2.5**

### Property 3: Algorithm Availability

*For any* income prediction request, all three algorithms (linear regression, moving average, seasonal analysis) should be available and return valid results

**Validates: Requirements 1.3**

### Property 4: Automatic Update Scheduling

*For any* system deployment, the predictive model update job should be scheduled to execute daily at 02:00 AM

**Validates: Requirements 1.5, 9.1**

### Property 5: Seasonal Pattern Calculation

*For any* detected seasonal pattern, the percentage variation relative to annual average should be calculated correctly using statistical formulas

**Validates: Requirements 2.2**

### Property 6: Year-over-Year Comparison

*For any* trend analysis with multiple years of data, year-over-year comparisons should show calculated deviations between periods

**Validates: Requirements 2.3**

### Property 7: Confidence Interval Generation

*For any* trend graph, 95% confidence intervals should be calculated and included in the visualization data

**Validates: Requirements 2.4**

### Property 8: Threshold-Based Alerting

*For any* projected value (expense or capacity), alerts should be generated when the projection exceeds the configured threshold percentage

**Validates: Requirements 3.2, 4.2**

### Property 9: Correlation Calculation

*For any* expense forecast, the Pearson correlation coefficient between expenses and incomes should be calculated using the standard statistical formula

**Validates: Requirements 3.3**

### Property 10: Expense Categorization

*For any* expense prediction, results should be properly categorized by expense type (personal, equipos, suministros, otros)

**Validates: Requirements 3.4**

### Property 11: Configuration Override

*For any* configurable parameter, custom values should take precedence over default values when specified

**Validates: Requirements 3.5**

### Property 12: Capacity Utilization Calculation

*For any* capacity analysis, utilization percentage should be calculated as (current_exams / maximum_capacity) * 100

**Validates: Requirements 4.1**

### Property 13: Saturation Date Projection

*For any* capacity analysis with growth trends, a projected saturation date should be calculated and returned

**Validates: Requirements 4.3**

### Property 14: Per-Clinic Growth Analysis

*For any* capacity analysis, growth trends should be calculated separately for each clinic in the system

**Validates: Requirements 4.4**

### Property 15: Bottleneck Recommendations

*For any* capacity analysis where bottlenecks are detected, actionable recommendations should be generated

**Validates: Requirements 4.5**

### Property 16: Chart.js Integration

*For any* dashboard view, Chart.js components should be rendered for all available prediction types

**Validates: Requirements 5.1**

### Property 17: Period Filter Synchronization

*For any* period selection change, all related charts and data displays should update consistently

**Validates: Requirements 5.3**

### Property 18: Clinic Filtering Options

*For any* dashboard view, both individual clinic and consolidated view options should be available

**Validates: Requirements 5.4**

### Property 19: Performance Requirements

*For any* dashboard load with up to 5 years of data, the system should complete loading within 3 seconds

**Validates: Requirements 5.5**

### Property 20: Export Format Generation

*For any* export request, the system should generate files in the requested format (Excel with multiple sheets or PDF with charts) containing all prediction data

**Validates: Requirements 6.1, 6.2**

### Property 21: Export Metadata Inclusion

*For any* exported report, metadata including generation date, analysis period, and parameters should be included in the file

**Validates: Requirements 6.3**

### Property 22: Unique Filename Generation

*For any* export operation, filenames should include timestamps to ensure uniqueness and prevent overwrites

**Validates: Requirements 6.4**

### Property 23: Export Performance

*For any* export operation with up to 10,000 records, the system should complete within 30 seconds

**Validates: Requirements 6.5**

### Property 24: Configuration Parameter Validation

*For any* configuration parameter, values should be validated within acceptable ranges before being applied

**Validates: Requirements 7.1, 7.4**

### Property 25: Algorithm Selection Configuration

*For any* prediction algorithm, it should be possible to enable or disable it individually through configuration

**Validates: Requirements 7.2**

### Property 26: Automatic Recalculation

*For any* configuration parameter change, affected predictions should be recalculated automatically

**Validates: Requirements 7.3**

### Property 27: Configuration Audit Trail

*For any* configuration change, an audit log entry should be created with timestamp and user information

**Validates: Requirements 7.5**

### Property 28: Accuracy Metrics Calculation

*For any* prediction accuracy validation, both MAPE and RMSE metrics should be calculated correctly using standard formulas

**Validates: Requirements 8.1, 8.2**

### Property 29: Low Accuracy Suggestions

*For any* prediction type with accuracy below 70%, parameter adjustment suggestions should be generated

**Validates: Requirements 8.3**

### Property 30: Monthly Accuracy Reporting

*For any* prediction type, monthly accuracy reports should be generated automatically

**Validates: Requirements 8.4**

### Property 31: Historical Accuracy Tracking

*For any* accuracy metric calculation, results should be stored for historical trend analysis

**Validates: Requirements 8.5**

### Property 32: Data Incorporation in Updates

*For any* new data added to the system, it should be included in the next scheduled model update cycle

**Validates: Requirements 9.2**

### Property 33: Update Error Handling

*For any* failed automatic update, errors should be logged and administrator notifications should be sent

**Validates: Requirements 9.3**

### Property 34: Model Backup

*For any* model update operation, a backup of the previous model should be created before applying changes

**Validates: Requirements 9.4**

### Property 35: Update Performance

*For any* scheduled model update, the operation should complete within 10 minutes

**Validates: Requirements 9.5**

### Property 36: Eloquent Model Integration

*For any* data access operation, existing Eloquent models (Repase, Clinica, Examen, RepaseExamen, Gasto) should be used without modification

**Validates: Requirements 10.1**

### Property 37: Laravel Convention Compliance

*For any* code structure element (routes, controllers, middleware), Laravel 11 conventions should be followed

**Validates: Requirements 10.2**

### Property 38: Database Compatibility

*For any* database operation, the existing SQLite database should be used without requiring schema migrations

**Validates: Requirements 10.3**

### Property 39: Authentication Integration

*For any* access control requirement, the existing authentication and authorization system should be used

**Validates: Requirements 10.4**

## Error Handling

### Exception Hierarchy

```php
namespace App\Exceptions\Predictive;

class PredictiveException extends Exception {}

class InsufficientDataException extends PredictiveException
{
    public function __construct(string $dataType, int $required, int $available)
    {
        parent::__construct(
            "Datos insuficientes para {$dataType}. Requeridos: {$required} meses, Disponibles: {$available} meses"
        );
    }
}

class ModelUpdateException extends PredictiveException {}
class ConfigurationException extends PredictiveException {}
class ExportException extends PredictiveException {}
```

### Error Recovery Strategies

1. **Graceful Degradation**: Si un algoritmo falla, usar algoritmos alternativos
2. **Fallback Data**: Si datos recientes no están disponibles, usar datos históricos más antiguos
3. **Cache Fallback**: Si cálculos fallan, usar resultados cacheados previos
4. **User Notification**: Informar claramente sobre limitaciones y errores

### Logging Strategy

```php
// Configuración de logging específico para módulo predictivo
'channels' => [
    'predictive' => [
        'driver' => 'daily',
        'path' => storage_path('logs/predictive.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

## Testing Strategy

### Dual Testing Approach

El módulo implementará tanto **unit tests** como **property-based tests** para asegurar cobertura completa:

**Unit Tests** se enfocarán en:
- Casos específicos de algoritmos predictivos
- Manejo de errores y excepciones
- Integración entre componentes
- Casos edge como datos vacíos o valores extremos

**Property Tests** validarán:
- Propiedades universales que deben cumplirse para todos los inputs
- Invariantes matemáticas de algoritmos predictivos
- Comportamiento consistente across diferentes datasets
- Correctitud de cálculos estadísticos

### Property-Based Testing Configuration

**Librería**: Utilizaremos **Pest** con **QuickCheck-style property testing** para PHP
**Configuración**: Mínimo 100 iteraciones por property test
**Tagging**: Cada test referenciará su propiedad de diseño correspondiente

Formato de tags: **Feature: analisis-predictivo, Property {number}: {property_text}**

### Test Categories

#### 1. Algorithm Correctness Tests
```php
// Property Test Example
test('income prediction generates exactly 3 time periods')
    ->property(fn() => [
        'historical_data' => generateHistoricalData(24), // 24 months
        'filters' => generateFilters()
    ])
    ->check(function ($data) {
        $predictor = app(IncomePredictor::class);
        $result = $predictor->predictIncome($data['filters'], 12);
        
        expect($result->getProjections())->toHaveCount(3);
        expect($result->getProjections())->toHaveKeys(['3_months', '6_months', '12_months']);
    })
    ->tag('Feature: analisis-predictivo, Property 1: Time Period Projection Generation');
```

#### 2. Data Validation Tests
```php
test('predictions require sufficient historical data')
    ->property(fn() => generateInsufficientData(rand(1, 11))) // < 12 months
    ->check(function ($insufficientData) {
        $predictor = app(IncomePredictor::class);
        
        expect(fn() => $predictor->predictIncome([], 12))
            ->toThrow(InsufficientDataException::class);
    })
    ->tag('Feature: analisis-predictivo, Property 2: Historical Data Sufficiency Validation');
```

#### 3. Mathematical Accuracy Tests
```php
test('pearson correlation calculation is mathematically correct')
    ->property(fn() => [
        'incomes' => generateRandomArray(50, 1000, 10000),
        'expenses' => generateRandomArray(50, 500, 5000)
    ])
    ->check(function ($data) {
        $forecaster = app(ExpenseForecaster::class);
        $correlation = $forecaster->calculateCorrelation($data['incomes'], $data['expenses']);
        
        // Verify correlation is within valid range [-1, 1]
        expect($correlation)->toBeGreaterThanOrEqual(-1);
        expect($correlation)->toBeLessThanOrEqual(1);
        
        // Verify against manual calculation
        $expectedCorrelation = calculatePearsonCorrelation($data['incomes'], $data['expenses']);
        expect($correlation)->toBeCloseTo($expectedCorrelation, 0.001);
    })
    ->tag('Feature: analisis-predictivo, Property 9: Correlation Calculation');
```

#### 4. Performance Tests
```php
test('dashboard loads within performance requirements')
    ->property(fn() => generateLargeDataset(5 * 365)) // 5 years of daily data
    ->check(function ($largeDataset) {
        $startTime = microtime(true);
        
        $controller = app(PredictiveController::class);
        $response = $controller->dashboard();
        
        $loadTime = microtime(true) - $startTime;
        expect($loadTime)->toBeLessThan(3.0); // 3 seconds max
    })
    ->tag('Feature: analisis-predictivo, Property 19: Performance Requirements');
```

### Unit Test Examples

```php
class IncomePredictorTest extends TestCase
{
    public function test_linear_regression_with_known_data()
    {
        // Test with known dataset where we can predict the outcome
        $knownData = [
            ['month' => '2023-01', 'income' => 10000],
            ['month' => '2023-02', 'income' => 11000],
            ['month' => '2023-03', 'income' => 12000],
            // ... linear progression
        ];
        
        $predictor = new IncomePredictor();
        $result = $predictor->predictIncome(['algorithm' => 'linear_regression'], 3);
        
        // Verify the prediction follows expected linear trend
        $this->assertEqualsWithDelta(15000, $result->getProjection('3_months'), 500);
    }
    
    public function test_handles_empty_data_gracefully()
    {
        $predictor = new IncomePredictor();
        
        $this->expectException(InsufficientDataException::class);
        $predictor->predictIncome([], 3);
    }
}
```

### Integration Tests

```php
class PredictiveDashboardTest extends TestCase
{
    public function test_complete_dashboard_workflow()
    {
        // Create test data
        $this->createTestClinics();
        $this->createTestRepases(24); // 24 months of data
        
        // Test dashboard access
        $response = $this->actingAs($this->adminUser())
                        ->get(route('predictive.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas(['incomeProjections', 'expenseForecasts', 'capacityAnalysis']);
    }
    
    public function test_export_maintains_data_integrity()
    {
        $this->createTestData();
        
        $response = $this->actingAs($this->adminUser())
                        ->post(route('predictive.export.excel'), [
                            'type' => 'income_projection',
                            'filters' => ['clinica_id' => 1]
                        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        
        // Verify exported data matches dashboard data
        $exportedData = $this->parseExcelResponse($response);
        $dashboardData = $this->getDashboardData(['clinica_id' => 1]);
        
        $this->assertEquals($dashboardData['totals'], $exportedData['totals']);
    }
}
```

### Test Data Generators

```php
class PredictiveTestDataGenerator
{
    public static function generateHistoricalData(int $months): array
    {
        $data = [];
        $baseIncome = 50000;
        $trend = 500; // Monthly growth
        $seasonality = 0.1; // 10% seasonal variation
        
        for ($i = 0; $i < $months; $i++) {
            $month = Carbon::now()->subMonths($months - $i - 1);
            $seasonal = sin(($month->month / 12) * 2 * pi()) * $seasonality;
            $noise = (rand(-100, 100) / 100) * 0.05; // 5% random noise
            
            $income = $baseIncome + ($i * $trend) + ($baseIncome * $seasonal) + ($baseIncome * $noise);
            
            $data[] = [
                'month' => $month->format('Y-m'),
                'income' => max(0, $income), // Ensure non-negative
                'expenses' => $income * 0.7 + (rand(-1000, 1000)), // ~70% of income with variation
                'exams' => rand(100, 200)
            ];
        }
        
        return $data;
    }
    
    public static function generateSeasonalPattern(): array
    {
        // Generate data with known seasonal pattern for testing
        return [
            'Jan' => 0.8,  // 20% below average
            'Feb' => 0.9,  // 10% below average
            'Mar' => 1.1,  // 10% above average
            // ... etc
        ];
    }
}
```

### Coverage Requirements

- **Minimum 90% code coverage** para servicios principales
- **100% property coverage** - cada propiedad debe tener al menos un property test
- **Edge case coverage** - todos los casos límite identificados deben tener unit tests
- **Integration coverage** - flujos completos end-to-end deben estar cubiertos

La estrategia de testing asegura que tanto la correctitud matemática como la integración del sistema estén completamente validadas.