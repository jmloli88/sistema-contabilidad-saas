# Implementation Plan: Módulo de Reportes Avanzados

## Overview

Este plan implementa el Módulo de Reportes Avanzados para el Sistema de Contabilidad Médica. El módulo proporciona 4 tipos de reportes principales (Rentabilidad por Clínica, Rentabilidad por Examen, Productividad, y Comparativo) con visualizaciones avanzadas usando Chart.js y capacidades de exportación a Excel y PDF.

**Stack Tecnológico:**
- Laravel 11 con SQLite
- Tailwind CSS para estilos
- Chart.js para visualizaciones
- Alpine.js para interactividad
- Laravel Excel para exportación Excel
- DomPDF para exportación PDF

**Arquitectura:**
- Patrón MVC con capa de servicios
- Middleware de autorización para administradores
- Consultas optimizadas con agregaciones SQL
- Caché de 5 minutos para resultados

## Tasks

- [x] 1. Configurar dependencias y estructura base
  - Instalar paquetes Laravel Excel (maatwebsite/excel) y DomPDF (barryvdh/laravel-dompdf)
  - Crear estructura de directorios para servicios, vistas y assets
  - Configurar publicación de assets de DomPDF
  - _Requirements: 18.1, 18.2_

- [ ] 2. Implementar capa de servicios
  - [x] 2.1 Crear ReporteService con métodos de cálculo
    - Implementar calcularRentabilidadClinica() con agregaciones SQL
    - Implementar calcularRentabilidadExamen() con agregaciones SQL
    - Implementar calcularProductividad() con métricas de exámenes
    - Implementar calcularComparativo() para dos períodos
    - Implementar calcularMargenGanancia() con manejo de división por cero
    - Implementar calcularVariacionPorcentual() con manejo de división por cero
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 5.1, 5.2, 5.3, 5.4, 6.2, 6.3, 6.4, 6.5, 11.1, 11.2_

  - [ ]* 2.2 Escribir property test para cálculo de ganancia neta
    - **Property 4: Invariante de Ganancia Neta**
    - **Valida: Requirements 3.3, 3.10**

  - [ ]* 2.3 Escribir property test para cálculo de margen de ganancia
    - **Property 5: Cálculo de Margen de Ganancia**
    - **Valida: Requirements 3.4, 11.1, 11.2, 6.9**

  - [ ]* 2.4 Escribir property test para filtrado por rango de fechas
    - **Property 9: Filtrado por Rango de Fechas**
    - **Valida: Requirements 3.9, 4.6**

  - [x] 2.5 Crear ExportService para exportaciones
    - Implementar exportarExcel() usando Laravel Excel
    - Implementar exportarPdf() usando DomPDF
    - Configurar formato de celdas para Excel (moneda, porcentaje)
    - Configurar template PDF con encabezados y estilos
    - _Requirements: 9.2, 9.5, 10.2, 10.3, 10.6, 10.9_

  - [ ]* 2.6 Escribir property test para round-trip de datos exportados
    - **Property 29: Round-Trip de Datos Exportados**
    - **Valida: Requirements 9.9, 20.4**

- [ ] 3. Implementar scopes y optimizaciones en modelos
  - [x] 3.1 Agregar scopes a modelo Repase
    - Implementar scopeByDateRange() para filtrado por fechas
    - Implementar scopeByClinica() para filtrado por clínica
    - Implementar scopeByEstado() para filtrado por estado
    - Configurar eager loading en relaciones
    - _Requirements: 7.1, 7.2, 15.2_

  - [x] 3.2 Crear índices de base de datos para optimización
    - Crear migración con índice compuesto en repases(fecha, clinica_id)
    - Crear índice en repase_examenes(repase_id, examen_id)
    - Crear índice en gastos(repase_id)
    - _Requirements: 15.1_

- [ ] 4. Implementar ReporteController
  - [x] 4.1 Crear ReporteController con método index
    - Implementar index() para dashboard principal de reportes
    - Aplicar middleware de autorización 'admin'
    - _Requirements: 2.1, 2.2, 1.5_

  - [x] 4.2 Implementar método rentabilidadClinica
    - Validar parámetros de entrada (fecha_inicio, fecha_fin, clinica_id)
    - Llamar a ReporteService.calcularRentabilidadClinica()
    - Manejar caso de datos vacíos con mensaje apropiado
    - Retornar vista con datos y filtros aplicados
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 7.1, 7.2, 14.1, 14.2, 14.3, 14.4, 17.5_

  - [ ]* 4.3 Escribir property test para invariante de suma por clínica
    - **Property 35: Invariante de Suma Total de Ganancia por Clínica**
    - **Valida: Requirements 20.1**

  - [x] 4.4 Implementar método rentabilidadExamen
    - Validar parámetros de entrada (fecha_inicio, fecha_fin, clinica_id, examen_id)
    - Llamar a ReporteService.calcularRentabilidadExamen()
    - Aplicar ordenamiento por total_ingresos descendente
    - Retornar vista con datos y filtros aplicados
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.6, 4.7, 4.8, 7.1, 7.2, 7.3_

  - [ ]* 4.5 Escribir property test para invariante de suma por examen
    - **Property 36: Invariante de Suma Total de Ingresos por Examen**
    - **Valida: Requirements 20.2**

  - [x] 4.6 Implementar método productividad
    - Validar parámetros de entrada (fecha_inicio, fecha_fin, clinica_id)
    - Llamar a ReporteService.calcularProductividad()
    - Calcular métricas: total_examenes, examenes_por_dia, examenes_por_repase
    - Incluir desgloses por examen y por clínica
    - Retornar vista con datos tabulares y preparados para gráficos
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8_

  - [ ]* 4.7 Escribir property test para invariante de productividad
    - **Property 19: Invariante de Suma de Productividad por Clínica**
    - **Property 20: Invariante de Suma de Productividad por Examen**
    - **Valida: Requirements 5.9, 20.3**

  - [x] 4.8 Implementar método comparativo
    - Validar dos períodos (periodo_actual y periodo_anterior)
    - Llamar a ReporteService.calcularComparativo()
    - Calcular variaciones porcentuales para cada métrica
    - Manejar división por cero mostrando "N/A"
    - Retornar vista con comparación lado a lado
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9_

  - [ ]* 4.9 Escribir property test para cálculo de variación porcentual
    - **Property 22: Cálculo de Variación Porcentual**
    - **Valida: Requirements 6.5**

  - [x] 4.10 Implementar método exportExcel
    - Validar parámetros (tipo, formato, filtros)
    - Regenerar datos del reporte según tipo
    - Llamar a ExportService.exportarExcel()
    - Generar nombre de archivo con patrón reporte_{tipo}_{fecha}.xlsx
    - Retornar descarga con deleteFileAfterSend
    - Manejar errores con try-catch y logging
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7, 9.8, 14.7, 17.2_

  - [x] 4.11 Implementar método exportPdf
    - Validar parámetros (tipo, formato, filtros)
    - Regenerar datos del reporte según tipo
    - Llamar a ExportService.exportarPdf()
    - Incluir gráficos como imágenes en PDF
    - Generar nombre de archivo con patrón reporte_{tipo}_{fecha}.pdf
    - Retornar descarga con deleteFileAfterSend
    - Manejar errores con try-catch y logging
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9, 10.10, 17.3_

  - [ ]* 4.12 Escribir unit tests para ReporteController
    - Test de autorización: usuario regular no puede acceder
    - Test de autorización: administrador puede acceder
    - Test de validación: rechaza fecha_inicio > fecha_fin
    - Test de datos vacíos: muestra mensaje apropiado
    - Test de exportación: genera archivo con nombre correcto

- [x] 5. Checkpoint - Verificar backend funcional
  - Ejecutar tests unitarios y property tests
  - Verificar que todos los cálculos sean correctos
  - Asegurar que las validaciones funcionen
  - Preguntar al usuario si hay dudas o ajustes necesarios

- [ ] 6. Implementar vistas Blade
  - [x] 6.1 Crear vista index (dashboard principal)
    - Diseñar layout con cards para cada tipo de reporte
    - Incluir iconos y descripciones breves
    - Aplicar estilos Tailwind consistentes con el sistema
    - Agregar breadcrumbs de navegación
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 6.2 Crear vista rentabilidad-clinica
    - Incluir componente de filtros (fechas, clínica)
    - Crear tabla sortable con columnas: nombre_clinica, total_ingresos, total_gastos, ganancia_neta, margen_ganancia, cantidad_repases
    - Aplicar formato monetario con dos decimales y símbolo
    - Aplicar formato de porcentaje con dos decimales
    - Aplicar colores condicionales a margen_ganancia (verde >50, amarillo 20-50, rojo <20)
    - Incluir contenedor para gráfico de barras Chart.js
    - Incluir botones de exportación Excel y PDF
    - _Requirements: 3.6, 3.7, 3.8, 11.3, 11.4, 11.5, 11.6_

  - [x] 6.3 Crear vista rentabilidad-examen
    - Incluir componente de filtros (fechas, clínica, examen)
    - Crear tabla sortable con columnas: nombre_examen, cantidad_total, total_ingresos, ingreso_promedio
    - Aplicar formato monetario y de porcentaje
    - Incluir contenedor para gráfico de pie Chart.js
    - Incluir botones de exportación
    - _Requirements: 4.4, 4.5, 7.3_

  - [x] 6.4 Crear vista productividad
    - Incluir componente de filtros (fechas, clínica)
    - Mostrar métricas principales: total_examenes, examenes_por_dia, examenes_por_repase
    - Crear tabla de desglose por tipo de examen
    - Crear tabla de desglose por clínica
    - Incluir contenedor para gráfico de barras horizontales
    - Incluir botones de exportación
    - _Requirements: 5.8_

  - [x] 6.5 Crear vista comparativo
    - Incluir selector de dos períodos (actual y anterior)
    - Crear tabla de comparación lado a lado
    - Mostrar variaciones porcentuales con colores (verde positivo, rojo negativo)
    - Manejar display de "N/A" para divisiones por cero
    - Incluir contenedor para gráfico de líneas de tendencia
    - Incluir botones de exportación
    - _Requirements: 6.6, 6.7, 6.8, 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.7_

  - [x] 6.6 Crear componente reutilizable de filtros (partial)
    - Implementar inputs de fecha con validación HTML5
    - Implementar dropdown de clínicas con opción "Todas"
    - Implementar dropdown de exámenes con opción "Todos"
    - Incluir botón "Aplicar Filtros"
    - Incluir botón "Limpiar Filtros"
    - Aplicar diseño responsivo con Tailwind
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8_

  - [x] 6.7 Crear componente reutilizable de botones de exportación (partial)
    - Botón "Exportar a Excel" con icono
    - Botón "Exportar a PDF" con icono
    - Aplicar estilos consistentes con el sistema
    - _Requirements: 9.1, 10.1_

  - [x] 6.8 Crear template PDF para exportaciones
    - Diseñar encabezado con título, fecha de generación, y filtros aplicados
    - Diseñar layout de tabla con estilos profesionales
    - Incluir espacio para imágenes de gráficos
    - Diseñar pie de página con números de página
    - Aplicar branding de la empresa
    - _Requirements: 10.3, 10.5, 10.6, 10.9, 10.10_

  - [x] 6.9 Aplicar diseño responsivo a todas las vistas
    - Implementar scroll horizontal en tablas para móviles
    - Apilar filtros verticalmente en pantallas pequeñas
    - Ajustar tamaño de gráficos según viewport
    - Asegurar touch targets de mínimo 44px
    - Mantener legibilidad de texto en pantallas pequeñas
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6_

- [x] 7. Implementar componentes JavaScript
  - [x] 7.1 Crear módulo de Chart.js para visualizaciones
    - Implementar función crearGraficoBarras() para rentabilidad por clínica
    - Implementar función crearGraficoPie() para distribución de ingresos por examen
    - Implementar función crearGraficoLineas() para tendencias comparativas
    - Implementar función crearGraficoBarrasHorizontales() para productividad
    - Configurar tooltips con valores exactos al hover
    - Configurar leyendas con etiquetas claras
    - Aplicar esquema de colores consistente
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8_

  - [x] 7.2 Crear componente Alpine.js para manejo de filtros
    - Implementar data() con estado de filtros
    - Implementar método aplicarFiltros() con llamada AJAX
    - Implementar método limpiarFiltros() que resetea valores
    - Mostrar indicador de carga durante regeneración
    - Actualizar tabla y gráficos sin recargar página
    - Manejar errores de validación del servidor
    - _Requirements: 7.9, 15.6_

  - [x] 7.3 Implementar funcionalidad de exportación con feedback
    - Mostrar indicador de carga al exportar
    - Mostrar mensaje de éxito al completar descarga
    - Manejar errores de exportación con mensajes claros
    - _Requirements: 17.1, 17.2, 17.3_

- [ ] 8. Configurar rutas y middleware
  - [x] 8.1 Definir grupo de rutas con prefijo /reportes
    - Aplicar middleware 'auth' y 'admin' a todas las rutas
    - Definir ruta GET /reportes para index
    - Definir ruta GET /reportes/rentabilidad-clinica
    - Definir ruta GET /reportes/rentabilidad-examen
    - Definir ruta GET /reportes/productividad
    - Definir ruta GET /reportes/comparativo
    - Definir ruta POST /reportes/export/excel
    - Definir ruta POST /reportes/export/pdf
    - Asignar nombres de ruta con prefijo 'reportes.'
    - _Requirements: 1.1, 1.2, 1.5_

  - [x] 8.2 Agregar enlace de navegación al menú principal
    - Modificar navigation.blade.php para incluir enlace "Reportes"
    - Mostrar enlace solo si usuario es administrador
    - Aplicar estilos consistentes con otros enlaces del menú
    - _Requirements: 1.4, 18.6_

  - [ ]* 8.3 Escribir property test para autorización
    - **Property 1: Autorización de Acceso a Reportes**
    - **Valida: Requirements 1.1, 1.4**

- [ ] 9. Implementar manejo de errores y logging
  - [ ] 9.1 Configurar manejo de errores en ReporteController
    - Implementar try-catch para errores de base de datos
    - Implementar try-catch para errores de exportación
    - Registrar errores en log con contexto completo
    - Retornar mensajes de error en español
    - _Requirements: 14.8, 17.4, 17.6, 17.7_

  - [ ] 9.2 Implementar validación de parámetros con mensajes personalizados
    - Personalizar mensajes de validación en español
    - Validar formato de fechas (Y-m-d)
    - Validar orden de fechas (inicio <= fin)
    - Validar existencia de IDs referenciados
    - Validar formato de exportación (excel|pdf)
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8_

  - [ ]* 9.3 Escribir property tests para validación
    - **Property 23: Validación de Orden de Fechas**
    - **Property 31: Validación de Formato de Fecha**
    - **Property 32: Validación de Integridad Referencial**
    - **Property 33: Validación de Formato de Exportación**
    - **Property 34: Mensajes de Error en Español**
    - **Valida: Requirements 14.1, 14.2, 14.3, 14.5, 14.6, 14.7, 14.8**

  - [ ] 9.4 Configurar logging de eventos importantes
    - Registrar generación exitosa de reportes (nivel INFO)
    - Registrar exportaciones completadas (nivel INFO)
    - Registrar errores con stack trace (nivel ERROR)
    - Incluir contexto: usuario, filtros, tiempo de ejecución
    - _Requirements: 15.7_

- [ ] 10. Implementar optimizaciones de rendimiento
  - [ ] 10.1 Configurar caché de resultados
    - Implementar caché de 5 minutos para resultados idénticos
    - Usar hash de filtros como clave de caché
    - Invalidar caché al cambiar filtros
    - _Requirements: 15.3_

  - [ ] 10.2 Optimizar consultas SQL
    - Usar agregaciones SQL (SUM, COUNT, AVG) en lugar de PHP
    - Implementar eager loading para relaciones
    - Verificar uso de índices con EXPLAIN
    - _Requirements: 15.1, 15.2, 15.4_

  - [ ] 10.3 Implementar paginación para grandes volúmenes
    - Aplicar paginación cuando resultados > 1000 registros
    - Mantener filtros al cambiar de página
    - _Requirements: 15.5_

  - [ ]* 10.4 Escribir performance test
    - Test: reporte con 1000 registros se genera en < 3 segundos
    - Test: exportación Excel con 1000 registros en < 5 segundos

- [ ] 11. Checkpoint - Verificar integración completa
  - Ejecutar todos los tests (unit, property, feature)
  - Verificar que las vistas se rendericen correctamente
  - Probar flujo completo: filtros → reporte → exportación
  - Verificar diseño responsivo en diferentes dispositivos
  - Asegurar que todos los tests pasen
  - Preguntar al usuario si hay ajustes necesarios

- [ ] 12. Documentación y refinamiento final
  - [ ] 12.1 Agregar PHPDoc a todos los métodos
    - Documentar ReporteController con @param y @return
    - Documentar ReporteService con @param y @return
    - Documentar ExportService con @param y @return
    - Incluir ejemplos de uso en comentarios
    - _Requirements: 19.1, 19.2, 19.3, 19.5_

  - [ ] 12.2 Agregar comentarios inline en lógica compleja
    - Comentar cálculos matemáticos complejos
    - Comentar manejo de casos edge (división por cero)
    - Comentar optimizaciones de queries
    - Usar español para comentarios
    - _Requirements: 19.3_

  - [ ]* 12.3 Escribir tests de integración end-to-end
    - Test: flujo completo con filtros múltiples
    - Test: exportación mantiene integridad de datos
    - Test: manejo de casos edge (ganancia negativa, datos vacíos)

  - [ ] 12.4 Verificar cobertura de tests
    - Ejecutar reporte de cobertura con --coverage
    - Asegurar cobertura > 90% en servicios
    - Asegurar todas las propiedades tienen tests
    - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5_

- [ ] 13. Final checkpoint - Revisión completa
  - Ejecutar suite completa de tests
  - Verificar que todos los requirements estén cubiertos
  - Revisar que todas las propiedades de correctitud estén validadas
  - Probar manualmente cada tipo de reporte
  - Verificar exportaciones Excel y PDF
  - Confirmar diseño responsivo
  - Asegurar mensajes de error claros en español
  - Preguntar al usuario si el módulo cumple con las expectativas

## Notes

- Las tareas marcadas con `*` son opcionales (principalmente tests) y pueden omitirse para un MVP más rápido
- Cada tarea referencia los requirements específicos para trazabilidad
- Los checkpoints aseguran validación incremental y oportunidad para feedback
- Los property tests validan propiedades universales de correctitud con 100 iteraciones cada uno
- Los unit tests validan ejemplos específicos y casos edge
- La implementación sigue el patrón MVC existente en Laravel
- Se reutilizan modelos existentes sin modificaciones
- El módulo es de solo lectura (no modifica datos existentes)
