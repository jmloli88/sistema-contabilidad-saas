# Requirements Document

## Introduction

El Módulo de Reportes Avanzados es una extensión del Sistema de Contabilidad Médica que proporciona capacidades analíticas profundas para evaluar la rentabilidad, productividad y tendencias del negocio médico. Este módulo permite a los administradores generar reportes detallados con visualizaciones avanzadas y exportarlos en múltiples formatos para análisis y presentación.

## Glossary

- **Sistema**: La aplicación web de contabilidad médica existente
- **Módulo_Reportes**: El nuevo módulo de reportes avanzados
- **Administrador**: Usuario con rol de administrador que tiene acceso completo al Módulo_Reportes
- **Usuario_Regular**: Usuario sin privilegios de administrador que no puede acceder al Módulo_Reportes
- **Reporte_Rentabilidad_Clínica**: Análisis financiero detallado por establecimiento médico
- **Reporte_Rentabilidad_Examen**: Análisis financiero detallado por tipo de procedimiento médico
- **Reporte_Productividad**: Análisis de cantidad de exámenes realizados por período
- **Reporte_Comparativo**: Análisis que compara métricas entre diferentes períodos temporales
- **Margen_Ganancia**: Porcentaje calculado como ((ingresos - gastos) / ingresos) * 100
- **Período**: Rango de fechas definido por fecha_inicio y fecha_fin
- **Formato_Exportación**: Tipo de archivo de salida que puede ser "excel" o "pdf"
- **Tendencia**: Patrón de cambio en métricas a lo largo del tiempo
- **Filtro_Reporte**: Criterio de selección que puede incluir rango_fechas, clinica_id, examen_id
- **Repase**: Registro diario de actividad médica (definido en sistema existente)
- **Clínica**: Establecimiento médico (definido en sistema existente)
- **Examen**: Procedimiento médico con precio definido (definido en sistema existente)

## Requirements

### Requirement 1: Control de Acceso al Módulo

**User Story:** Como administrador del sistema, quiero que solo usuarios con rol de administrador puedan acceder a reportes avanzados, para que la información financiera sensible esté protegida.

#### Acceptance Criteria

1. THE Sistema SHALL restrict access to Módulo_Reportes to users with role "administrador"
2. WHEN a Usuario_Regular attempts to access Módulo_Reportes routes, THE Sistema SHALL return HTTP 403 Forbidden response
3. WHEN a Usuario_Regular attempts to access Módulo_Reportes routes, THE Sistema SHALL redirect to dashboard with error message
4. THE Sistema SHALL display Módulo_Reportes navigation link only for Administrador users
5. THE Sistema SHALL apply authorization middleware to all Módulo_Reportes routes

### Requirement 2: Interfaz Principal de Reportes

**User Story:** Como administrador, quiero una interfaz centralizada para acceder a todos los tipos de reportes, para que pueda navegar fácilmente entre diferentes análisis.

#### Acceptance Criteria

1. THE Sistema SHALL provide a main reports dashboard at route "/reportes"
2. THE Sistema SHALL display cards or buttons for each available report type
3. THE Sistema SHALL display report type options: Reporte_Rentabilidad_Clínica, Reporte_Rentabilidad_Examen, Reporte_Productividad, Reporte_Comparativo
4. THE Sistema SHALL use consistent styling with the existing Sistema interface
5. THE Sistema SHALL provide navigation breadcrumbs showing current location within Módulo_Reportes

### Requirement 3: Reporte de Rentabilidad por Clínica

**User Story:** Como administrador, quiero generar un reporte de rentabilidad por clínica, para que pueda identificar qué establecimientos son más rentables.

#### Acceptance Criteria

1. THE Sistema SHALL calculate total_ingresos per Clínica as sum of (total_examenes + total_consultas) from all Repase records for that Clínica
2. THE Sistema SHALL calculate total_gastos per Clínica as sum of total_gastos from all Repase records for that Clínica
3. THE Sistema SHALL calculate ganancia_neta per Clínica as total_ingresos minus total_gastos
4. THE Sistema SHALL calculate Margen_Ganancia per Clínica using the formula ((total_ingresos - total_gastos) / total_ingresos) * 100
5. THE Sistema SHALL calculate cantidad_repases per Clínica as count of Repase records for that Clínica
6. THE Sistema SHALL display results in a sortable table with columns: nombre_clinica, total_ingresos, total_gastos, ganancia_neta, Margen_Ganancia, cantidad_repases
7. THE Sistema SHALL format monetary values with two decimal places and currency symbol
8. THE Sistema SHALL format Margen_Ganancia as percentage with two decimal places
9. WHEN Filtro_Reporte includes rango_fechas, THE Sistema SHALL include only Repase records where fecha falls within the specified Período
10. FOR ALL Clínica records in the report, THE Sistema SHALL ensure that ganancia_neta equals total_ingresos minus total_gastos within 0.01 precision (invariant property)

### Requirement 4: Reporte de Rentabilidad por Tipo de Examen

**User Story:** Como administrador, quiero generar un reporte de rentabilidad por tipo de examen, para que pueda identificar qué procedimientos generan más ingresos.

#### Acceptance Criteria

1. THE Sistema SHALL calculate total_ingresos per Examen as sum of (cantidad * precio) from all repase_examenes records for that Examen
2. THE Sistema SHALL calculate cantidad_total per Examen as sum of cantidad from all repase_examenes records for that Examen
3. THE Sistema SHALL calculate ingreso_promedio per Examen as total_ingresos divided by cantidad_total
4. THE Sistema SHALL display results in a sortable table with columns: nombre_examen, cantidad_total, total_ingresos, ingreso_promedio
5. THE Sistema SHALL format monetary values with two decimal places and currency symbol
6. WHEN Filtro_Reporte includes rango_fechas, THE Sistema SHALL include only repase_examenes from Repase records where fecha falls within the specified Período
7. WHEN Filtro_Reporte includes clinica_id, THE Sistema SHALL include only repase_examenes from Repase records for that Clínica
8. THE Sistema SHALL order results by total_ingresos in descending order by default
9. FOR ALL Examen records in the report, THE Sistema SHALL ensure that ingreso_promedio equals total_ingresos divided by cantidad_total within 0.01 precision (invariant property)

### Requirement 5: Reporte de Productividad

**User Story:** Como administrador, quiero generar un reporte de productividad, para que pueda analizar la cantidad de exámenes realizados en diferentes períodos.

#### Acceptance Criteria

1. THE Sistema SHALL calculate total_examenes_realizados as sum of cantidad from all repase_examenes records within the Período
2. THE Sistema SHALL calculate examenes_por_dia as total_examenes_realizados divided by number of days in Período
3. THE Sistema SHALL calculate total_repases as count of Repase records within the Período
4. THE Sistema SHALL calculate examenes_por_repase as total_examenes_realizados divided by total_repases
5. THE Sistema SHALL display breakdown by Examen showing cantidad_total per each Examen type
6. THE Sistema SHALL display breakdown by Clínica showing cantidad_total per each Clínica
7. WHEN Filtro_Reporte includes clinica_id, THE Sistema SHALL calculate metrics using only Repase records for that Clínica
8. THE Sistema SHALL display results with both tabular data and bar charts
9. FOR ALL productivity calculations, THE Sistema SHALL ensure that sum of examenes per Clínica equals total_examenes_realizados (invariant property)

### Requirement 6: Reporte Comparativo de Períodos

**User Story:** Como administrador, quiero comparar métricas entre diferentes períodos, para que pueda identificar tendencias y cambios en el negocio.

#### Acceptance Criteria

1. THE Sistema SHALL accept two Período inputs: periodo_actual and periodo_anterior
2. THE Sistema SHALL calculate total_ingresos for both periodo_actual and periodo_anterior
3. THE Sistema SHALL calculate total_gastos for both periodo_actual and periodo_anterior
4. THE Sistema SHALL calculate ganancia_neta for both periodo_actual and periodo_anterior
5. THE Sistema SHALL calculate variacion_porcentual for each metric as ((valor_actual - valor_anterior) / valor_anterior) * 100
6. THE Sistema SHALL display variacion_porcentual with positive values in green and negative values in red
7. THE Sistema SHALL display side-by-side comparison table with columns: metrica, periodo_anterior, periodo_actual, variacion_porcentual
8. THE Sistema SHALL display line chart showing Tendencia of ingresos and gastos across both periods
9. WHEN periodo_anterior has zero value for a metric, THE Sistema SHALL display "N/A" for variacion_porcentual instead of attempting division by zero

### Requirement 7: Filtros de Reportes

**User Story:** Como administrador, quiero aplicar filtros flexibles a los reportes, para que pueda analizar datos específicos según mis necesidades.

#### Acceptance Criteria

1. THE Sistema SHALL provide date range filter with fecha_inicio and fecha_fin inputs
2. THE Sistema SHALL provide Clínica dropdown filter showing all available Clínica records
3. THE Sistema SHALL provide Examen dropdown filter showing all available Examen records (applicable only to Reporte_Rentabilidad_Examen)
4. THE Sistema SHALL validate that fecha_inicio is less than or equal to fecha_fin
5. THE Sistema SHALL apply all selected filters using AND logic
6. THE Sistema SHALL persist filter selections when navigating between report views
7. THE Sistema SHALL provide a "Limpiar Filtros" button that resets all filters to default values
8. WHEN no date range is specified, THE Sistema SHALL default to current month
9. WHEN filters are changed, THE Sistema SHALL regenerate the report without page reload using AJAX

### Requirement 8: Visualizaciones Gráficas Avanzadas

**User Story:** Como administrador, quiero ver gráficos avanzados en los reportes, para que pueda comprender visualmente los datos financieros.

#### Acceptance Criteria

1. THE Sistema SHALL display bar chart for Reporte_Rentabilidad_Clínica comparing ganancia_neta across Clínica records
2. THE Sistema SHALL display pie chart for Reporte_Rentabilidad_Examen showing distribution of total_ingresos by Examen type
3. THE Sistema SHALL display line chart for Reporte_Comparativo showing Tendencia over time
4. THE Sistema SHALL display horizontal bar chart for Reporte_Productividad showing cantidad_total by Examen type
5. THE Sistema SHALL use Chart.js library for all chart rendering
6. THE Sistema SHALL provide chart legends with clear labels
7. THE Sistema SHALL use color-coded visualization with consistent color scheme
8. WHEN hovering over chart elements, THE Sistema SHALL display detailed tooltips with exact values

### Requirement 9: Exportación a Excel

**User Story:** Como administrador, quiero exportar reportes a Excel, para que pueda realizar análisis adicionales o compartir datos con otros.

#### Acceptance Criteria

1. THE Sistema SHALL provide "Exportar a Excel" button on each report view
2. WHEN "Exportar a Excel" is clicked, THE Sistema SHALL generate an Excel file using Laravel Excel package
3. THE Sistema SHALL include all visible table data in the Excel export
4. THE Sistema SHALL include applied filters information in a summary sheet
5. THE Sistema SHALL format Excel cells appropriately: currency format for monetary values, percentage format for Margen_Ganancia
6. THE Sistema SHALL name the Excel file with pattern: "reporte_{tipo}_{fecha_generacion}.xlsx"
7. THE Sistema SHALL trigger automatic download of the generated Excel file
8. THE Sistema SHALL include column headers in the Excel file matching the table headers
9. FOR ALL exported data, THE Sistema SHALL ensure that recalculating totals from exported rows produces the same values as displayed in the web interface (round-trip property)

### Requirement 10: Exportación a PDF

**User Story:** Como administrador, quiero exportar reportes a PDF, para que pueda imprimir o presentar informes profesionales.

#### Acceptance Criteria

1. THE Sistema SHALL provide "Exportar a PDF" button on each report view
2. WHEN "Exportar a PDF" is clicked, THE Sistema SHALL generate a PDF file using DomPDF or similar package
3. THE Sistema SHALL include report title, generation date, and applied filters in PDF header
4. THE Sistema SHALL include all visible table data in the PDF
5. THE Sistema SHALL include chart visualizations as images in the PDF
6. THE Sistema SHALL apply professional styling with company branding to PDF layout
7. THE Sistema SHALL name the PDF file with pattern: "reporte_{tipo}_{fecha_generacion}.pdf"
8. THE Sistema SHALL trigger automatic download of the generated PDF file
9. THE Sistema SHALL format PDF for A4 page size with appropriate margins
10. THE Sistema SHALL include page numbers in PDF footer

### Requirement 11: Cálculos de Margen de Ganancia

**User Story:** Como administrador, quiero ver el margen de ganancia en los reportes, para que pueda evaluar la eficiencia financiera.

#### Acceptance Criteria

1. THE Sistema SHALL calculate Margen_Ganancia using the formula ((ingresos - gastos) / ingresos) * 100
2. WHEN ingresos equals zero, THE Sistema SHALL display Margen_Ganancia as "N/A" instead of attempting division by zero
3. THE Sistema SHALL display Margen_Ganancia with two decimal places followed by "%" symbol
4. WHEN Margen_Ganancia is greater than 50, THE Sistema SHALL display it in green color
5. WHEN Margen_Ganancia is between 20 and 50, THE Sistema SHALL display it in yellow color
6. WHEN Margen_Ganancia is less than 20, THE Sistema SHALL display it in red color
7. THE Sistema SHALL validate Margen_Ganancia calculation in backend before displaying

### Requirement 12: Comparativas Mes a Mes

**User Story:** Como administrador, quiero ver comparativas mes a mes, para que pueda identificar patrones estacionales en mi negocio.

#### Acceptance Criteria

1. THE Sistema SHALL provide a "Comparativa Mensual" view within Reporte_Comparativo
2. THE Sistema SHALL display data for the last 12 months by default
3. THE Sistema SHALL calculate total_ingresos, total_gastos, and ganancia_neta for each month
4. THE Sistema SHALL display results in a line chart showing Tendencia across months
5. THE Sistema SHALL display results in a table with rows for each month
6. THE Sistema SHALL format month labels as "Mes Año" (e.g., "Enero 2024")
7. WHEN Filtro_Reporte includes clinica_id, THE Sistema SHALL calculate metrics using only data for that Clínica
8. THE Sistema SHALL allow selection of custom month range instead of default 12 months

### Requirement 13: Comparativas Año a Año

**User Story:** Como administrador, quiero ver comparativas año a año, para que pueda evaluar el crecimiento del negocio a largo plazo.

#### Acceptance Criteria

1. THE Sistema SHALL provide a "Comparativa Anual" view within Reporte_Comparativo
2. THE Sistema SHALL display data for all available years in the database
3. THE Sistema SHALL calculate total_ingresos, total_gastos, and ganancia_neta for each year
4. THE Sistema SHALL display results in a bar chart comparing metrics across years
5. THE Sistema SHALL display results in a table with rows for each year
6. THE Sistema SHALL calculate year-over-year growth percentage for each metric
7. WHEN Filtro_Reporte includes clinica_id, THE Sistema SHALL calculate metrics using only data for that Clínica

### Requirement 14: Validación de Datos de Entrada

**User Story:** Como administrador, quiero que el sistema valide mis filtros y parámetros, para que evite errores en la generación de reportes.

#### Acceptance Criteria

1. THE Sistema SHALL validate that fecha_inicio is a valid date in format Y-m-d
2. THE Sistema SHALL validate that fecha_fin is a valid date in format Y-m-d
3. THE Sistema SHALL validate that fecha_inicio is less than or equal to fecha_fin
4. WHEN fecha_fin is before fecha_inicio, THE Sistema SHALL display validation error message
5. THE Sistema SHALL validate that clinica_id references an existing Clínica record when provided
6. THE Sistema SHALL validate that examen_id references an existing Examen record when provided
7. THE Sistema SHALL validate that Formato_Exportación is either "excel" or "pdf"
8. WHEN invalid parameters are provided, THE Sistema SHALL return validation errors with descriptive messages in Spanish

### Requirement 15: Optimización de Rendimiento

**User Story:** Como administrador, quiero que los reportes se generen rápidamente, para que pueda trabajar eficientemente con grandes volúmenes de datos.

#### Acceptance Criteria

1. THE Sistema SHALL use database indexes on fecha, clinica_id, and examen_id columns for query optimization
2. THE Sistema SHALL use eager loading to prevent N+1 query problems when loading related Repase, Clínica, and Examen data
3. THE Sistema SHALL cache report results for 5 minutes when using identical filters
4. THE Sistema SHALL use database aggregation functions (SUM, COUNT, AVG) instead of PHP calculations when possible
5. WHEN generating reports with more than 1000 records, THE Sistema SHALL implement pagination for table display
6. THE Sistema SHALL display loading indicator while report is being generated
7. THE Sistema SHALL execute report queries asynchronously to prevent UI blocking

### Requirement 16: Interfaz Responsiva

**User Story:** Como administrador, quiero acceder a reportes desde dispositivos móviles, para que pueda revisar métricas en cualquier momento.

#### Acceptance Criteria

1. THE Sistema SHALL use responsive design with Tailwind CSS for all Módulo_Reportes views
2. THE Sistema SHALL display tables with horizontal scroll on mobile devices when content exceeds screen width
3. THE Sistema SHALL stack filter inputs vertically on mobile devices
4. THE Sistema SHALL resize charts appropriately for different screen sizes
5. THE Sistema SHALL maintain readability of text and numbers on small screens
6. THE Sistema SHALL provide touch-friendly buttons and controls with minimum 44px touch target size

### Requirement 17: Mensajes de Estado y Errores

**User Story:** Como administrador, quiero recibir mensajes claros sobre el estado de mis acciones, para que sepa si los reportes se generaron correctamente.

#### Acceptance Criteria

1. WHEN a report is generated successfully, THE Sistema SHALL display success message
2. WHEN export to Excel completes, THE Sistema SHALL display "Archivo Excel generado exitosamente" message
3. WHEN export to PDF completes, THE Sistema SHALL display "Archivo PDF generado exitosamente" message
4. WHEN an error occurs during report generation, THE Sistema SHALL display descriptive error message in Spanish
5. WHEN no data is available for selected filters, THE Sistema SHALL display "No se encontraron datos para los filtros seleccionados" message
6. THE Sistema SHALL use flash messages that auto-dismiss after 5 seconds
7. THE Sistema SHALL use color-coded messages: green for success, red for errors, yellow for warnings

### Requirement 18: Integración con Sistema Existente

**User Story:** Como desarrollador, quiero que el Módulo_Reportes se integre perfectamente con el sistema existente, para que el código sea mantenible y consistente.

#### Acceptance Criteria

1. THE Sistema SHALL reuse existing Eloquent models: Repase, Clínica, Examen, RepaseExamen, Gasto
2. THE Sistema SHALL follow the existing MVC architecture pattern
3. THE Sistema SHALL use existing authentication and authorization mechanisms
4. THE Sistema SHALL maintain consistent naming conventions with existing codebase
5. THE Sistema SHALL use existing Tailwind CSS configuration and color scheme
6. THE Sistema SHALL add Módulo_Reportes navigation link to existing navigation menu
7. THE Sistema SHALL use existing database connection and configuration

### Requirement 19: Documentación de Código

**User Story:** Como desarrollador, quiero código bien documentado, para que pueda mantener y extender el Módulo_Reportes fácilmente.

#### Acceptance Criteria

1. THE Sistema SHALL include PHPDoc comments for all controller methods
2. THE Sistema SHALL include PHPDoc comments for all service class methods
3. THE Sistema SHALL include inline comments in Spanish explaining complex business logic
4. THE Sistema SHALL include README file documenting Módulo_Reportes features and usage
5. THE Sistema SHALL include code examples in documentation for common report generation scenarios

### Requirement 20: Pruebas de Integridad de Datos

**User Story:** Como desarrollador, quiero asegurar la integridad de los cálculos en reportes, para que los datos presentados sean siempre precisos.

#### Acceptance Criteria

1. FOR ALL Reporte_Rentabilidad_Clínica calculations, THE Sistema SHALL ensure that sum of ganancia_neta across all Clínica equals total system ganancia_neta (invariant property)
2. FOR ALL Reporte_Rentabilidad_Examen calculations, THE Sistema SHALL ensure that sum of total_ingresos across all Examen equals total system ingresos from examenes (invariant property)
3. FOR ALL Reporte_Productividad calculations, THE Sistema SHALL ensure that sum of cantidad_total across all Examen equals total examenes in system (invariant property)
4. FOR ALL exported reports, THE Sistema SHALL ensure that recalculating totals from exported data produces identical values to web interface (round-trip property)
5. THE Sistema SHALL validate all monetary calculations with precision of 0.01 before displaying or exporting
