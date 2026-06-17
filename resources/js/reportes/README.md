# Módulos JavaScript de Reportes Avanzados

Este directorio contiene los módulos JavaScript para el sistema de reportes avanzados.

## Archivos

### 1. `charts.js`
Módulo de Chart.js para crear visualizaciones de datos.

**Funciones disponibles:**

- `crearGraficoBarras(elementId, datos, opciones)` - Crea gráfico de barras para rentabilidad por clínica
- `crearGraficoPie(elementId, datos, opciones)` - Crea gráfico de pie para distribución de ingresos
- `crearGraficoLineas(elementId, datos, opciones)` - Crea gráfico de líneas para tendencias comparativas
- `crearGraficoBarrasHorizontales(elementId, datos, opciones)` - Crea gráfico de barras horizontales para productividad

**Ejemplo de uso en Blade:**

```html
<canvas id="miGrafico"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script type="module">
    import { crearGraficoBarras } from '/resources/js/reportes/charts.js';
    
    const datos = @json($datos);
    crearGraficoBarras('miGrafico', datos);
</script>
```

O usando la versión global (sin módulos):

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/resources/js/reportes/charts.js"></script>
<script>
    const datos = @json($datos);
    window.ReporteCharts.crearGraficoBarras('miGrafico', datos);
</script>
```

### 2. `filtros.js`
Componente Alpine.js para manejo interactivo de filtros.

**Uso con Alpine.js:**

```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<div x-data="filtrosReporte('{{ route('reportes.rentabilidad-clinica') }}', {{ json_encode($filtros) }})">
    <input type="date" x-model="fechaInicio">
    <input type="date" x-model="fechaFin">
    
    <button @click="aplicarFiltros()" :disabled="cargando">
        <span x-text="textoBotonAplicar"></span>
    </button>
    
    <button @click="limpiarFiltros()">Limpiar</button>
    
    <div x-show="cargando">Cargando...</div>
    <div x-show="error" x-text="error"></div>
</div>
```

### 3. `exportacion.js`
Módulo para manejar exportaciones con feedback visual.

**Características:**
- Indicador de carga durante la exportación
- Mensajes de éxito/error
- Descarga automática del archivo
- Auto-inicialización en formularios de exportación

**Uso automático:**
El módulo se inicializa automáticamente y detecta formularios con las rutas:
- `form[action*="export/excel"]`
- `form[action*="export/pdf"]`

**Uso manual:**

```javascript
import { manejarExportacion } from './exportacion.js';

const form = document.getElementById('miFormulario');
await manejarExportacion(form, 'excel'); // o 'pdf'
```

### 4. `index.js`
Punto de entrada principal que exporta todos los módulos.

**Uso:**

```javascript
import * as Reportes from './reportes/index.js';

// Usar funciones
Reportes.crearGraficoBarras('miGrafico', datos);
Reportes.inicializarExportacion();
```

## Integración con Vite

Para usar estos módulos con Vite, agregar en `vite.config.js`:

```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/reportes/index.js', // Agregar esta línea
            ],
            refresh: true,
        }),
    ],
});
```

Luego en las vistas Blade:

```blade
@vite(['resources/js/reportes/index.js'])
```

## Uso sin módulos ES6 (CDN)

Todos los módulos también están disponibles globalmente:

```html
<script src="/resources/js/reportes/charts.js"></script>
<script src="/resources/js/reportes/exportacion.js"></script>

<script>
    // Usar funciones globales
    window.ReporteCharts.crearGraficoBarras('miGrafico', datos);
    window.ReporteExportacion.inicializarExportacion();
</script>
```

## Dependencias Externas

### Chart.js (Requerido para charts.js)
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

### Alpine.js (Requerido para filtros.js)
```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

## Características

### Charts (charts.js)
- ✅ Esquema de colores consistente
- ✅ Tooltips con valores exactos formateados
- ✅ Leyendas claras y descriptivas
- ✅ Responsive y adaptable
- ✅ Formato de moneda automático

### Filtros (filtros.js)
- ✅ Validación de fechas
- ✅ Llamadas AJAX sin recargar página
- ✅ Indicador de carga
- ✅ Manejo de errores
- ✅ Limpieza de filtros

### Exportación (exportacion.js)
- ✅ Indicador de carga visual
- ✅ Mensajes de éxito/error
- ✅ Descarga automática
- ✅ Auto-inicialización
- ✅ Manejo de errores

## Validaciones de Requirements

- **Requirement 8.1-8.8**: Visualizaciones gráficas implementadas ✅
- **Requirement 7.9**: Filtros con AJAX implementados ✅
- **Requirement 15.6**: Indicador de carga implementado ✅
- **Requirement 17.1-17.3**: Feedback de exportación implementado ✅

## Notas

- Todos los módulos son compatibles con ES6 modules y uso global
- Los mensajes están en español según los requirements
- El código es minimal y enfocado en funcionalidad esencial
- Compatible con Tailwind CSS para estilos
