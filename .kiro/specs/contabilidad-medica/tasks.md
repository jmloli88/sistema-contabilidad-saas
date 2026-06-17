# Plan de Implementación: Sistema de Contabilidad Médica

## Descripción General

Este plan implementa un sistema completo de contabilidad médica en Laravel 12 con:
- Autenticación mediante Laravel Breeze
- Gestión de Clínicas (CRUD)
- Catálogo de 7 Exámenes predefinidos
- Gestión de Repases con cálculos automáticos
- Dashboard con métricas y gráficos (Chart.js)
- Vista de Calendario (FullCalendar)
- 39 propiedades de correctness validadas mediante tests

## Tareas

- [ ] 1. Configuración inicial del proyecto Laravel 12
  - [x] 1.1 Crear proyecto Laravel 12 y configurar base de datos
    - Ejecutar `composer create-project laravel/laravel contabilidad-medica "12.*"`
    - Configurar archivo `.env` con credenciales de MySQL
    - Configurar `APP_NAME`, `APP_URL`, `APP_LOCALE=es`
    - Verificar conexión con `php artisan migrate`
    - _Requirements: 17.2, 17.6_

  - [x] 1.2 Instalar Laravel Breeze para autenticación
    - Ejecutar `composer require laravel/breeze --dev`
    - Ejecutar `php artisan breeze:install blade`
    - Ejecutar `npm install && npm run build`
    - Ejecutar `php artisan migrate`
    - Verificar rutas de autenticación funcionando
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 1.3 Instalar dependencias frontend
    - Instalar Chart.js: `npm install chart.js`
    - Instalar FullCalendar: `npm install @fullcalendar/core @fullcalendar/daygrid @fullcalendar/interaction`
    - Instalar Alpine.js (si no viene con Breeze): `npm install alpinejs`
    - Actualizar `vite.config.js` si es necesario
    - _Requirements: 11.1, 12.6, 16.1_

- [ ] 2. Crear migraciones de base de datos
  - [x] 2.1 Crear migración para tabla clinicas
    - Ejecutar `php artisan make:migration create_clinicas_table`
    - Definir campos: id, nombre (string 255), direccion (text nullable), telefono (string 20 nullable), timestamps
    - Agregar índice en nombre
    - _Requirements: 2.1, 18.1_

  - [x] 2.2 Crear migración para tabla examenes
    - Ejecutar `php artisan make:migration create_examenes_table`
    - Definir campos: id, nombre (string 255), precio_sin_nota (decimal 10,2), precio_con_nota (decimal 10,2), timestamps
    - Agregar índice en nombre
    - Agregar constraint CHECK: precio_sin_nota < precio_con_nota
    - _Requirements: 3.1, 3.5, 18.1, 18.7_

  - [x] 2.3 Crear migración para tabla repases
    - Ejecutar `php artisan make:migration create_repases_table`
    - Definir campos: id, clinica_id (foreign key), fecha (date), fecha_pago (date nullable), estado (enum: pendiente/pagado, default pendiente), tipo_precio (enum: sin_nota/con_nota), total_examenes (decimal 10,2, default 0), total_consultas (decimal 10,2, default 0), total_gastos (decimal 10,2, default 0), total_neto (decimal 10,2, default 0), observaciones (text nullable), timestamps, deleted_at (softDeletes)
    - Agregar foreign key: clinica_id references clinicas(id) ON DELETE RESTRICT
    - Agregar índices: clinica_id, fecha, estado, deleted_at
    - _Requirements: 4.1, 4.2, 4.3, 4.6, 15.1, 18.1, 18.3, 18.4, 18.5, 18.6, 18.7_

  - [x] 2.4 Crear migración para tabla repase_examenes
    - Ejecutar `php artisan make:migration create_repase_examenes_table`
    - Definir campos: id, repase_id (foreign key), examen_id (foreign key), cantidad (unsigned integer), precio_unitario_usado (decimal 10,2), subtotal (decimal 10,2), timestamps
    - Agregar foreign key: repase_id references repases(id) ON DELETE CASCADE
    - Agregar foreign key: examen_id references examenes(id) ON DELETE RESTRICT
    - Agregar índices: repase_id, examen_id
    - _Requirements: 5.2, 5.5, 15.4, 15.5, 18.1, 18.3, 18.4, 18.7_

  - [x] 2.5 Crear migración para tabla gastos
    - Ejecutar `php artisan make:migration create_gastos_table`
    - Definir campos: id, repase_id (foreign key), tipo (enum: doctor/tecnico/laudos/gasolina/extra), descripcion (string 255 nullable), monto (decimal 10,2), timestamps
    - Agregar foreign key: repase_id references repases(id) ON DELETE CASCADE
    - Agregar índices: repase_id, tipo
    - _Requirements: 7.2, 7.3, 7.5, 15.6, 18.1, 18.3, 18.4, 18.7_

  - [x] 2.6 Ejecutar migraciones y verificar estructura
    - Ejecutar `php artisan migrate`
    - Verificar que todas las tablas se crearon correctamente
    - Verificar foreign keys y constraints en la base de datos
    - _Requirements: 18.1, 18.3, 18.4_

- [ ] 3. Crear modelos Eloquent con relaciones
  - [x] 3.1 Crear modelo Clinica
    - Ejecutar `php artisan make:model Clinica`
    - Definir $fillable: nombre, direccion, telefono
    - Definir relación hasMany con Repase
    - _Requirements: 2.1, 15.1, 17.6_

  - [x] 3.2 Crear modelo Examen
    - Ejecutar `php artisan make:model Examen`
    - Definir $fillable: nombre, precio_sin_nota, precio_con_nota
    - Definir $casts: precio_sin_nota y precio_con_nota como decimal:2
    - Definir relación hasMany con RepaseExamen
    - _Requirements: 3.1, 17.6_

  - [x] 3.3 Crear modelo Repase con SoftDeletes
    - Ejecutar `php artisan make:model Repase`
    - Importar trait SoftDeletes
    - Definir $fillable: clinica_id, fecha, fecha_pago, estado, tipo_precio, total_examenes, total_consultas, total_gastos, total_neto, observaciones
    - Definir $casts: fecha y fecha_pago como date, total_examenes, total_consultas, total_gastos, total_neto como decimal:2
    - Definir relación belongsTo con Clinica
    - Definir relación hasMany con RepaseExamen
    - Definir relación hasMany con Gasto
    - _Requirements: 4.1, 4.2, 4.6, 15.1, 15.2, 15.3, 17.6_

  - [x] 3.4 Agregar scopes al modelo Repase
    - Crear scope scopeByClinica para filtrar por clinica_id
    - Crear scope scopeByEstado para filtrar por estado
    - Crear scope scopeByDateRange para filtrar por rango de fechas
    - _Requirements: 13.2, 13.3, 13.4_

  - [x] 3.5 Crear modelo RepaseExamen
    - Ejecutar `php artisan make:model RepaseExamen`
    - Definir $table = 'repase_examenes'
    - Definir $fillable: repase_id, examen_id, cantidad, precio_unitario_usado, subtotal
    - Definir $casts: cantidad como integer, precio_unitario_usado y subtotal como decimal:2
    - Definir relación belongsTo con Repase
    - Definir relación belongsTo con Examen
    - _Requirements: 5.2, 5.5, 15.4, 15.5, 17.6_

  - [x] 3.6 Crear modelo Gasto
    - Ejecutar `php artisan make:model Gasto`
    - Definir $fillable: repase_id, tipo, descripcion, monto
    - Definir $casts: monto como decimal:2
    - Definir relación belongsTo con Repase
    - _Requirements: 7.2, 7.5, 15.6, 17.6_

- [ ] 4. Crear seeders y factories
  - [x] 4.1 Crear ExamenSeeder con 7 exámenes predefinidos
    - Ejecutar `php artisan make:seeder ExamenSeeder`
    - Implementar método run() que cree exactamente 7 registros de Examen con los datos especificados en Requirements 3.2
    - Registrar seeder en DatabaseSeeder
    - Ejecutar `php artisan db:seed --class=ExamenSeeder`
    - Verificar que se crearon los 7 exámenes correctamente
    - _Requirements: 3.1, 3.2, 18.2_

  - [x] 4.2 Crear factories para testing
    - Ejecutar `php artisan make:factory ClinicaFactory`
    - Ejecutar `php artisan make:factory RepaseFactory`
    - Ejecutar `php artisan make:factory RepaseExamenFactory`
    - Ejecutar `php artisan make:factory GastoFactory`
    - Implementar método definition() en cada factory con datos faker apropiados
    - Agregar state 'pagado' en RepaseFactory
    - _Requirements: Testing Strategy_

- [ ] 5. Crear servicios de lógica de negocio
  - [x] 5.1 Crear RepaseService
    - Ejecutar `php artisan make:class Services/RepaseService`
    - Implementar método createRepase(array $data): Repase con transacción DB
    - Implementar método updateRepase(Repase $repase, array $data): Repase con transacción DB
    - Implementar método calculateTotalExamenes(array $examenes, string $tipoPrecio): float
    - Implementar método calculateTotalGastos(array $gastos): float
    - Implementar método calculateTotalNeto(float $totalExamenes, float $totalConsultas, float $totalGastos): float
    - Implementar método determineEstado(?string $fechaPago): string
    - Agregar comentarios en español explicando cada método
    - _Requirements: 4.5, 5.6, 5.7, 5.8, 7.7, 8.1, 8.6, 17.4, 17.5, 19.1, 19.2_

  - [x] 5.2 Crear DashboardService
    - Ejecutar `php artisan make:class Services/DashboardService`
    - Implementar método getMetrics(array $filters): array que calcule total_ingresos, total_gastos, total_neto, total_pendiente, total_pagado
    - Implementar método getIngresosVsGastosChart(array $filters): array para datos de Chart.js
    - Implementar método getTotalesPorClinicaChart(array $filters): array para datos de Chart.js
    - Implementar método getPagadosVsPendientesChart(array $filters): array para datos de Chart.js
    - Aplicar filtros usando scopes del modelo Repase
    - Agregar comentarios en español explicando cada método
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9, 11.1, 11.2, 11.3, 17.4, 17.5_

- [ ] 6. Crear FormRequests para validación
  - [x] 6.1 Crear StoreClinicaRequest
    - Ejecutar `php artisan make:request StoreClinicaRequest`
    - Definir authorize() retornando true
    - Definir rules() con validaciones: nombre required|string|max:255, direccion nullable|string, telefono nullable|string|max:20
    - Definir messages() con mensajes en español
    - _Requirements: 2.1, 14.1, 14.2_

  - [x] 6.2 Crear UpdateClinicaRequest
    - Ejecutar `php artisan make:request UpdateClinicaRequest`
    - Definir authorize() retornando true
    - Definir rules() con las mismas validaciones que StoreClinicaRequest
    - Definir messages() con mensajes en español
    - _Requirements: 2.4, 14.1, 14.2_

  - [x] 6.3 Crear StoreRepaseRequest
    - Ejecutar `php artisan make:request StoreRepaseRequest`
    - Definir authorize() retornando true
    - Definir rules() con validaciones completas según Requirements 14.3-14.12
    - Incluir validaciones para: clinica_id, fecha, fecha_pago, tipo_precio, estado, total_consultas, observaciones, examenes array, examenes.*.examen_id, examenes.*.cantidad, gastos array, gastos.*.tipo, gastos.*.descripcion, gastos.*.monto
    - Definir messages() con mensajes en español
    - _Requirements: 4.1, 4.2, 5.1, 5.2, 6.2, 6.3, 7.1, 7.2, 7.3, 7.4, 7.5, 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8, 14.9, 14.10, 14.11, 14.12_

  - [x] 6.4 Crear UpdateRepaseRequest
    - Ejecutar `php artisan make:request UpdateRepaseRequest`
    - Definir authorize() retornando true
    - Definir rules() con las mismas validaciones que StoreRepaseRequest
    - Definir messages() con mensajes en español
    - _Requirements: 9.3, 14.1, 14.2_

- [x] 7. Crear controllers RESTful
  - [x] 7.1 Crear ClinicaController
    - Ejecutar `php artisan make:controller ClinicaController --resource`
    - Implementar método index() con paginación
    - Implementar método create() retornando vista
    - Implementar método store(StoreClinicaRequest) con flash message
    - Implementar método show(Clinica) retornando vista
    - Implementar método edit(Clinica) retornando vista
    - Implementar método update(UpdateClinicaRequest, Clinica) con flash message
    - Implementar método destroy(Clinica) con manejo de error si tiene repases asociados
    - Agregar middleware auth en constructor
    - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.6, 17.3_

  - [x] 7.2 Crear RepaseController con inyección de RepaseService
    - Ejecutar `php artisan make:controller RepaseController --resource`
    - Inyectar RepaseService en constructor
    - Implementar método index(Request) con filtros y paginación usando scopes
    - Implementar método create() retornando vista con clínicas y exámenes
    - Implementar método store(StoreRepaseRequest) usando RepaseService->createRepase() con try-catch y transacción
    - Implementar método show(Repase) con eager loading de relaciones
    - Implementar método edit(Repase) retornando vista con datos precargados
    - Implementar método update(UpdateRepaseRequest, Repase) usando RepaseService->updateRepase() con try-catch y transacción
    - Implementar método destroy(Repase) validando que estado sea pendiente
    - Agregar middleware auth en constructor
    - _Requirements: 4.5, 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 15.7, 17.3, 19.1, 19.2, 19.3, 19.4_

  - [x] 7.3 Crear DashboardController con inyección de DashboardService
    - Ejecutar `php artisan make:controller DashboardController`
    - Inyectar DashboardService en constructor
    - Implementar método index(Request) que obtenga filtros del request
    - Llamar a DashboardService->getMetrics() con filtros
    - Llamar a métodos de charts del DashboardService
    - Retornar vista con métricas y datos de gráficos
    - Agregar middleware auth en constructor
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9, 11.1, 11.2, 11.3, 11.4, 17.3_

  - [x] 7.4 Crear CalendarioController
    - Ejecutar `php artisan make:controller CalendarioController`
    - Implementar método index(Request) retornando vista de calendario
    - Implementar método events(Request) retornando JsonResponse con repases en formato FullCalendar
    - Aplicar filtros de clínica si se proporciona
    - Formatear eventos con color según estado (rojo: pendiente, verde: pagado)
    - Agregar middleware auth en constructor
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 17.3_

- [ ] 8. Crear rutas web
  - [x] 8.1 Definir rutas en routes/web.php
    - Agregar ruta raíz que redirija a dashboard o login según autenticación
    - Agregar grupo de rutas con middleware auth
    - Agregar Route::resource para clinicas
    - Agregar Route::resource para repases
    - Agregar ruta GET /dashboard para DashboardController@index
    - Agregar ruta GET /calendario para CalendarioController@index
    - Agregar ruta GET /calendario/events para CalendarioController@events
    - Verificar que rutas de Breeze estén incluidas
    - _Requirements: 1.4, 1.5, 17.3_

- [x] 9. Checkpoint - Verificar estructura backend
  - Ejecutar `php artisan route:list` y verificar todas las rutas
  - Ejecutar `php artisan test` para verificar que no hay errores de sintaxis
  - Verificar que migraciones funcionan: `php artisan migrate:fresh --seed`
  - Asegurar que todos los tests pasan, preguntar al usuario si surgen dudas

- [ ] 10. Crear vistas Blade con Tailwind CSS
  - [x] 10.1 Actualizar layout principal
    - Modificar resources/views/layouts/app.blade.php para incluir navegación personalizada
    - Agregar enlaces a: Dashboard, Clínicas, Repases, Calendario
    - Incluir sección para flash messages (success, error, warning)
    - Asegurar que Tailwind CSS esté configurado correctamente
    - _Requirements: 16.1, 16.2, 16.3, 16.6_

  - [x] 10.2 Crear vistas de Clínicas
    - Crear resources/views/clinicas/index.blade.php con tabla paginada y botones de acción
    - Crear resources/views/clinicas/create.blade.php con formulario
    - Crear resources/views/clinicas/edit.blade.php con formulario precargado
    - Crear resources/views/clinicas/show.blade.php con detalles de clínica
    - Aplicar estilos Tailwind CSS minimalistas
    - _Requirements: 2.2, 2.3, 2.4, 2.6, 16.1, 16.2, 16.4_

  - [x] 10.3 Crear vistas de Repases - Listado y Detalle
    - Crear resources/views/repases/index.blade.php con tabla paginada, filtros y búsqueda
    - Incluir filtros por clínica, estado y rango de fechas
    - Crear resources/views/repases/show.blade.php mostrando todos los detalles, exámenes y gastos
    - Aplicar estilos Tailwind CSS con color coding según estado
    - _Requirements: 9.1, 9.2, 9.6, 13.1, 13.2, 13.3, 13.4, 13.6, 16.1, 16.2, 16.4, 16.5_

  - [x] 10.4 Crear vista de creación de Repase con formulario dinámico
    - Crear resources/views/repases/create.blade.php
    - Implementar formulario con Alpine.js para agregar/eliminar exámenes dinámicamente
    - Implementar formulario con Alpine.js para agregar/eliminar gastos dinámicamente
    - Implementar cálculo en tiempo real de subtotales de exámenes
    - Implementar cálculo en tiempo real de total_examenes
    - Implementar cálculo en tiempo real de total_gastos
    - Implementar cálculo en tiempo real de total_neto
    - Mostrar select de exámenes con precios según tipo_precio
    - Mostrar campos condicionales (descripcion requerida si tipo gasto es "extra")
    - Validar campos requeridos en frontend
    - _Requirements: 4.1, 4.2, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 6.1, 6.2, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 8.1, 8.5, 16.1, 16.2, 16.4_

  - [x] 10.5 Crear vista de edición de Repase
    - Crear resources/views/repases/edit.blade.php
    - Reutilizar lógica de formulario dinámico de create.blade.php
    - Precargar datos existentes de exámenes y gastos
    - Mantener funcionalidad de cálculos en tiempo real
    - _Requirements: 9.3, 16.1, 16.2, 16.4_

  - [x] 10.6 Crear vista de Dashboard
    - Crear resources/views/dashboard/index.blade.php
    - Mostrar cards con métricas: total_ingresos, total_gastos, total_neto, total_pendiente, total_pagado
    - Incluir filtros por clínica, estado y rango de fechas
    - Incluir contenedores canvas para 3 gráficos Chart.js
    - Aplicar diseño responsivo con Tailwind CSS
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9, 11.4, 16.1, 16.2, 16.4, 16.5_

  - [x] 10.7 Crear vista de Calendario
    - Crear resources/views/calendario/index.blade.php
    - Incluir contenedor div para FullCalendar
    - Incluir filtro por clínica
    - Aplicar estilos Tailwind CSS
    - _Requirements: 12.1, 12.5, 16.1, 16.2, 16.4_

- [ ] 11. Implementar JavaScript para interactividad
  - [ ] 11.1 Crear script para formulario dinámico de Repase
    - Crear resources/js/repase-form.js
    - Implementar funciones para agregar/eliminar filas de exámenes
    - Implementar funciones para agregar/eliminar filas de gastos
    - Implementar función calculateSubtotal(cantidad, precio)
    - Implementar función calculateTotalExamenes() que sume todos los subtotales
    - Implementar función calculateTotalGastos() que sume todos los montos
    - Implementar función calculateTotalNeto() usando fórmula (examenes + consultas) - gastos
    - Implementar listeners para actualizar cálculos en tiempo real
    - Implementar lógica para mostrar/ocultar precio según tipo_precio
    - Importar en app.js y compilar con `npm run build`
    - _Requirements: 5.5, 5.6, 5.7, 7.6, 7.7, 8.5_

  - [x] 11.2 Crear script para gráficos del Dashboard
    - Crear resources/js/dashboard-charts.js
    - Implementar función para crear gráfico de barras: Ingresos vs Gastos por mes
    - Implementar función para crear gráfico de pastel: Totales por clínica
    - Implementar función para crear gráfico de dona: Pagados vs Pendientes
    - Recibir datos desde blade via data attributes o variable JavaScript
    - Configurar Chart.js con labels y colores apropiados
    - Importar en app.js y compilar con `npm run build`
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

  - [x] 11.3 Crear script para Calendario
    - Crear resources/js/calendario.js
    - Inicializar FullCalendar con configuración en español
    - Configurar vista mensual (dayGridMonth)
    - Configurar source de eventos apuntando a ruta /calendario/events
    - Implementar color coding: rojo para pendiente, verde para pagado
    - Implementar click en evento para mostrar modal o redireccionar a detalle
    - Implementar filtro por clínica que recargue eventos
    - Importar en app.js y compilar con `npm run build`
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

  - [ ] 11.4 Implementar filtros dinámicos
    - Crear resources/js/filters.js
    - Implementar listeners para filtros de dashboard que actualicen métricas y gráficos sin recargar página
    - Implementar listeners para filtros de repases que actualicen tabla sin recargar página
    - Usar fetch API para obtener datos filtrados
    - Importar en app.js y compilar con `npm run build`
    - _Requirements: 10.6, 10.7, 10.8, 10.9, 13.5_

- [ ] 12. Checkpoint - Verificar funcionalidad completa
  - Ejecutar `npm run build` y verificar que no hay errores de compilación
  - Iniciar servidor: `php artisan serve`
  - Probar flujo completo: registro, login, crear clínica, crear repase, ver dashboard, ver calendario
  - Verificar que cálculos automáticos funcionan correctamente
  - Verificar que filtros funcionan en dashboard y listado de repases
  - Asegurar que todo funciona, preguntar al usuario si surgen dudas

- [ ] 13. Implementar property-based tests
  - [ ]* 13.1 Crear tests/Property/RepaseInvariantsTest.php
    - Crear clase RepaseInvariantsTest extends TestCase
    - Configurar trait RefreshDatabase
    - _Requirements: Testing Strategy_

  - [ ]* 13.2 Implementar Property 1: Invariante de Cálculo de Subtotal por Examen
    - Crear método test_subtotal_calculation_invariant()
    - Ejecutar 100 iteraciones con datos aleatorios
    - Verificar que subtotal = cantidad × precio_unitario_usado
    - Agregar comentario: "Feature: contabilidad-medica, Property 1: Invariante de Cálculo de Subtotal por Examen"
    - **Property 1: Invariante de Cálculo de Subtotal por Examen**
    - **Validates: Requirements 5.3, 5.4, 5.5**

  - [ ]* 13.3 Implementar Property 2: Invariante de Total Exámenes
    - Crear método test_total_examenes_invariant()
    - Ejecutar 100 iteraciones con datos aleatorios
    - Verificar que recalcular total_examenes produce el mismo valor almacenado (±0.01)
    - Agregar comentario: "Feature: contabilidad-medica, Property 2: Invariante de Total Exámenes"
    - **Property 2: Invariante de Total Exámenes**
    - **Validates: Requirements 5.6, 5.9**

  - [ ]* 13.4 Implementar Property 3: Invariante de Total Gastos
    - Crear método test_total_gastos_invariant()
    - Ejecutar 100 iteraciones con datos aleatorios
    - Verificar que recalcular total_gastos produce el mismo valor almacenado (±0.01)
    - Agregar comentario: "Feature: contabilidad-medica, Property 3: Invariante de Total Gastos"
    - **Property 3: Invariante de Total Gastos**
    - **Validates: Requirements 7.7, 7.8**

  - [ ]* 13.5 Implementar Property 4: Invariante de Total Neto
    - Crear método test_total_neto_invariant()
    - Ejecutar 100 iteraciones con datos aleatorios
    - Verificar que total_neto = (total_examenes + total_consultas) - total_gastos (±0.01)
    - Agregar comentario: "Feature: contabilidad-medica, Property 4: Invariante de Total Neto"
    - **Property 4: Invariante de Total Neto**
    - **Validates: Requirements 8.1, 8.7**

  - [ ]* 13.6 Implementar Property 5: Estado Automático según Fecha de Pago
    - Crear método test_estado_automatico_segun_fecha_pago()
    - Ejecutar 100 iteraciones con datos aleatorios
    - Verificar que si fecha_pago existe entonces estado = "pagado", sino estado = "pendiente"
    - Agregar comentario: "Feature: contabilidad-medica, Property 5: Estado Automático según Fecha de Pago"
    - **Property 5: Estado Automático según Fecha de Pago**
    - **Validates: Requirements 4.4**

  - [ ]* 13.7 Implementar Property 6: Recálculo Automático al Cambiar Tipo de Precio
    - Crear método test_recalculo_al_cambiar_tipo_precio()
    - Ejecutar 100 iteraciones con datos aleatorios
    - Verificar que al cambiar tipo_precio se recalculan subtotales y total_examenes
    - Agregar comentario: "Feature: contabilidad-medica, Property 6: Recálculo Automático al Cambiar Tipo de Precio"
    - **Property 6: Recálculo Automático al Cambiar Tipo de Precio**
    - **Validates: Requirements 5.7**

  - [ ]* 13.8 Implementar Property 7: Validación de Precios de Exámenes
    - Crear método test_precio_sin_nota_menor_que_con_nota()
    - Ejecutar 100 iteraciones con datos aleatorios
    - Verificar que precio_sin_nota < precio_con_nota para todos los exámenes
    - Agregar comentario: "Feature: contabilidad-medica, Property 7: Validación de Precios de Exámenes"
    - **Property 7: Validación de Precios de Exámenes**
    - **Validates: Requirements 3.5**

  - [ ]* 13.9 Crear tests/Property/ValidationPropertiesTest.php
    - Crear clase ValidationPropertiesTest extends TestCase
    - Configurar trait RefreshDatabase
    - _Requirements: Testing Strategy_

  - [ ]* 13.10 Implementar Property 8: Validación de Campos Requeridos en Repase
    - Crear método test_campos_requeridos_en_repase()
    - Intentar crear repase sin clinica_id, fecha, tipo_precio, estado
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 8: Validación de Campos Requeridos en Repase"
    - **Property 8: Validación de Campos Requeridos en Repase**
    - **Validates: Requirements 4.1**

  - [ ]* 13.11 Implementar Property 9: Validación de Cantidad Positiva
    - Crear método test_cantidad_debe_ser_positiva()
    - Ejecutar 50 iteraciones intentando crear con cantidad <= 0
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 9: Validación de Cantidad Positiva"
    - **Property 9: Validación de Cantidad Positiva**
    - **Validates: Requirements 5.2, 14.7**

  - [ ]* 13.12 Implementar Property 10: Validación de Total Consultas No Negativo
    - Crear método test_total_consultas_no_negativo()
    - Ejecutar 50 iteraciones intentando crear con total_consultas < 0
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 10: Validación de Total Consultas No Negativo"
    - **Property 10: Validación de Total Consultas No Negativo**
    - **Validates: Requirements 6.3**

  - [ ]* 13.13 Implementar Property 11: Validación de Tipos de Gasto
    - Crear método test_tipos_de_gasto_validos()
    - Intentar crear gastos con tipos inválidos
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 11: Validación de Tipos de Gasto"
    - **Property 11: Validación de Tipos de Gasto**
    - **Validates: Requirements 7.2, 14.12**

  - [ ]* 13.14 Implementar Property 12: Validación de Descripción para Gasto Extra
    - Crear método test_descripcion_requerida_para_gasto_extra()
    - Intentar crear gasto tipo "extra" sin descripcion o con menos de 3 caracteres
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 12: Validación de Descripción para Gasto Extra"
    - **Property 12: Validación de Descripción para Gasto Extra**
    - **Validates: Requirements 7.3, 7.4**

  - [ ]* 13.15 Implementar Property 13: Validación de Monto de Gasto
    - Crear método test_monto_gasto_valido()
    - Intentar crear gastos con montos negativos o con más de 2 decimales
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 13: Validación de Monto de Gasto"
    - **Property 13: Validación de Monto de Gasto**
    - **Validates: Requirements 7.5, 14.6**

  - [ ]* 13.16 Implementar Property 14: Soft Delete de Repases Pendientes
    - Crear método test_soft_delete_repases_pendientes()
    - Ejecutar 50 iteraciones con repases pendientes
    - Verificar que al eliminar se aplica soft delete (deleted_at se establece)
    - Agregar comentario: "Feature: contabilidad-medica, Property 14: Soft Delete de Repases Pendientes"
    - **Property 14: Soft Delete de Repases Pendientes**
    - **Validates: Requirements 4.6, 9.5**

  - [ ]* 13.17 Implementar Property 15: Prevención de Eliminación de Repases Pagados
    - Crear método test_prevencion_eliminacion_repases_pagados()
    - Ejecutar 50 iteraciones con repases pagados
    - Verificar que intentar eliminar es rechazado
    - Agregar comentario: "Feature: contabilidad-medica, Property 15: Prevención de Eliminación de Repases Pagados"
    - **Property 15: Prevención de Eliminación de Repases Pagados**
    - **Validates: Requirements 9.4**

  - [ ]* 13.18 Crear tests/Property/TransactionPropertiesTest.php
    - Crear clase TransactionPropertiesTest extends TestCase
    - Configurar trait RefreshDatabase
    - _Requirements: Testing Strategy_

  - [ ]* 13.19 Implementar Property 16: Atomicidad Transaccional en Creación de Repase
    - Crear método test_atomicidad_creacion_repase()
    - Simular error en medio de transacción
    - Verificar que todos los cambios se revierten (rollback)
    - Agregar comentario: "Feature: contabilidad-medica, Property 16: Atomicidad Transaccional en Creación de Repase"
    - **Property 16: Atomicidad Transaccional en Creación de Repase**
    - **Validates: Requirements 4.5, 19.3**

  - [ ]* 13.20 Implementar Property 17: Atomicidad Transaccional en Actualización de Repase
    - Crear método test_atomicidad_actualizacion_repase()
    - Simular error en medio de transacción de actualización
    - Verificar que todos los cambios se revierten
    - Agregar comentario: "Feature: contabilidad-medica, Property 17: Atomicidad Transaccional en Actualización de Repase"
    - **Property 17: Atomicidad Transaccional en Actualización de Repase**
    - **Validates: Requirements 19.2, 19.3**

  - [ ]* 13.21 Implementar Property 18: Validación de Fechas
    - Crear método test_validacion_fechas()
    - Intentar crear repases con fechas inválidas
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 18: Validación de Fechas"
    - **Property 18: Validación de Fechas**
    - **Validates: Requirements 14.3, 14.4**

  - [ ]* 13.22 Implementar Property 19: Validación de Orden de Fechas
    - Crear método test_fecha_pago_posterior_a_fecha()
    - Intentar crear repases con fecha_pago anterior a fecha
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 19: Validación de Orden de Fechas"
    - **Property 19: Validación de Orden de Fechas**
    - **Validates: Requirements 14.5**

  - [ ]* 13.23 Implementar Property 20: Validación de Referencias de Foreign Keys
    - Crear método test_validacion_foreign_keys()
    - Intentar crear repases con clinica_id inexistente
    - Intentar crear repase_examenes con examen_id inexistente
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 20: Validación de Referencias de Foreign Keys"
    - **Property 20: Validación de Referencias de Foreign Keys**
    - **Validates: Requirements 14.8, 14.9**

  - [ ]* 13.24 Implementar Property 21: Validación de Valores Enum
    - Crear método test_validacion_valores_enum()
    - Intentar crear repases con tipo_precio y estado inválidos
    - Verificar que se rechaza con error de validación
    - Agregar comentario: "Feature: contabilidad-medica, Property 21: Validación de Valores Enum"
    - **Property 21: Validación de Valores Enum**
    - **Validates: Requirements 14.10, 14.11**

  - [ ]* 13.25 Crear tests/Property/DashboardPropertiesTest.php
    - Crear clase DashboardPropertiesTest extends TestCase
    - Configurar trait RefreshDatabase
    - _Requirements: Testing Strategy_

  - [ ]* 13.26 Implementar Property 22: Cálculo de Total Ingresos en Dashboard
    - Crear método test_calculo_total_ingresos()
    - Ejecutar 50 iteraciones con conjuntos aleatorios de repases
    - Verificar que total_ingresos = suma de (total_examenes + total_consultas)
    - Agregar comentario: "Feature: contabilidad-medica, Property 22: Cálculo de Total Ingresos en Dashboard"
    - **Property 22: Cálculo de Total Ingresos en Dashboard**
    - **Validates: Requirements 10.1**

  - [ ]* 13.27 Implementar Property 23: Cálculo de Total Gastos en Dashboard
    - Crear método test_calculo_total_gastos_dashboard()
    - Ejecutar 50 iteraciones con conjuntos aleatorios de repases
    - Verificar que total_gastos = suma de total_gastos de todos los repases
    - Agregar comentario: "Feature: contabilidad-medica, Property 23: Cálculo de Total Gastos en Dashboard"
    - **Property 23: Cálculo de Total Gastos en Dashboard**
    - **Validates: Requirements 10.2**

  - [ ]* 13.28 Implementar Property 24: Cálculo de Total Neto en Dashboard
    - Crear método test_calculo_total_neto_dashboard()
    - Ejecutar 50 iteraciones con conjuntos aleatorios de repases
    - Verificar que total_neto = total_ingresos - total_gastos
    - Agregar comentario: "Feature: contabilidad-medica, Property 24: Cálculo de Total Neto en Dashboard"
    - **Property 24: Cálculo de Total Neto en Dashboard**
    - **Validates: Requirements 10.3**

  - [ ]* 13.29 Implementar Property 25: Cálculo de Total Pendiente en Dashboard
    - Crear método test_calculo_total_pendiente()
    - Ejecutar 50 iteraciones con conjuntos aleatorios de repases
    - Verificar que total_pendiente = suma de total_neto donde estado = "pendiente"
    - Agregar comentario: "Feature: contabilidad-medica, Property 25: Cálculo de Total Pendiente en Dashboard"
    - **Property 25: Cálculo de Total Pendiente en Dashboard**
    - **Validates: Requirements 10.4**

  - [ ]* 13.30 Implementar Property 26: Cálculo de Total Pagado en Dashboard
    - Crear método test_calculo_total_pagado()
    - Ejecutar 50 iteraciones con conjuntos aleatorios de repases
    - Verificar que total_pagado = suma de total_neto donde estado = "pagado"
    - Agregar comentario: "Feature: contabilidad-medica, Property 26: Cálculo de Total Pagado en Dashboard"
    - **Property 26: Cálculo de Total Pagado en Dashboard**
    - **Validates: Requirements 10.5**

  - [ ]* 13.31 Implementar Property 27: Filtrado por Clínica
    - Crear método test_filtrado_por_clinica()
    - Ejecutar 50 iteraciones con filtros de clínica
    - Verificar que métricas solo incluyen repases de esa clínica
    - Agregar comentario: "Feature: contabilidad-medica, Property 27: Filtrado por Clínica"
    - **Property 27: Filtrado por Clínica**
    - **Validates: Requirements 10.6**

  - [ ]* 13.32 Implementar Property 28: Filtrado por Estado
    - Crear método test_filtrado_por_estado()
    - Ejecutar 50 iteraciones con filtros de estado
    - Verificar que métricas solo incluyen repases con ese estado
    - Agregar comentario: "Feature: contabilidad-medica, Property 28: Filtrado por Estado"
    - **Property 28: Filtrado por Estado**
    - **Validates: Requirements 10.7**

  - [ ]* 13.33 Implementar Property 29: Filtrado por Rango de Fechas
    - Crear método test_filtrado_por_rango_fechas()
    - Ejecutar 50 iteraciones con rangos de fechas aleatorios
    - Verificar que métricas solo incluyen repases dentro del rango
    - Agregar comentario: "Feature: contabilidad-medica, Property 29: Filtrado por Rango de Fechas"
    - **Property 29: Filtrado por Rango de Fechas**
    - **Validates: Requirements 10.8**

  - [ ]* 13.34 Implementar Property 30: Combinación de Filtros con Lógica AND
    - Crear método test_combinacion_filtros_and()
    - Ejecutar 50 iteraciones con múltiples filtros simultáneos
    - Verificar que se aplican todos los filtros con lógica AND
    - Agregar comentario: "Feature: contabilidad-medica, Property 30: Combinación de Filtros con Lógica AND"
    - **Property 30: Combinación de Filtros con Lógica AND**
    - **Validates: Requirements 10.9**

  - [ ]* 13.35 Crear tests/Property/CRUDPropertiesTest.php
    - Crear clase CRUDPropertiesTest extends TestCase
    - Configurar trait RefreshDatabase
    - _Requirements: Testing Strategy_

  - [ ]* 13.36 Implementar Property 31: CRUD Round Trip de Clínicas
    - Crear método test_crud_round_trip_clinicas()
    - Ejecutar 50 iteraciones creando, leyendo, actualizando y leyendo clínicas
    - Verificar que datos persisten correctamente
    - Agregar comentario: "Feature: contabilidad-medica, Property 31: CRUD Round Trip de Clínicas"
    - **Property 31: CRUD Round Trip de Clínicas**
    - **Validates: Requirements 2.2, 2.3, 2.4**

  - [ ]* 13.37 Implementar Property 32: Eliminación de Clínicas
    - Crear método test_eliminacion_clinicas()
    - Crear clínicas sin repases asociados y eliminarlas
    - Verificar que ya no existen en la base de datos
    - Agregar comentario: "Feature: contabilidad-medica, Property 32: Eliminación de Clínicas"
    - **Property 32: Eliminación de Clínicas**
    - **Validates: Requirements 2.5**

  - [ ]* 13.38 Implementar Property 33: Cascade Delete de Relaciones
    - Crear método test_cascade_delete_relaciones()
    - Crear repases con exámenes y gastos, luego soft delete
    - Verificar que gastos y repase_examenes se eliminan en cascada
    - Agregar comentario: "Feature: contabilidad-medica, Property 33: Cascade Delete de Relaciones"
    - **Property 33: Cascade Delete de Relaciones**
    - **Validates: Requirements 18.3**

  - [ ]* 13.39 Implementar Property 34: Prevención de N+1 Queries
    - Crear método test_prevencion_n_plus_one()
    - Crear 20 repases con relaciones
    - Contar queries al cargar listado
    - Verificar que número de queries es constante (3-4) usando eager loading
    - Agregar comentario: "Feature: contabilidad-medica, Property 34: Prevención de N+1 Queries"
    - **Property 34: Prevención de N+1 Queries**
    - **Validates: Requirements 15.7**

  - [ ]* 13.40 Implementar Property 35: Validación de Cálculos en Backend
    - Crear método test_validacion_calculos_backend()
    - Intentar crear repases con valores calculados incorrectos
    - Verificar que backend recalcula y rechaza si no coinciden
    - Agregar comentario: "Feature: contabilidad-medica, Property 35: Validación de Cálculos en Backend"
    - **Property 35: Validación de Cálculos en Backend**
    - **Validates: Requirements 5.8, 8.6**

  - [ ]* 13.41 Implementar Property 36: Repases con Múltiples Exámenes
    - Crear método test_repases_con_multiples_examenes()
    - Ejecutar 50 iteraciones creando repases con 1-10 exámenes
    - Verificar que todos se crean correctamente
    - Intentar crear repase sin exámenes y verificar que se rechaza
    - Agregar comentario: "Feature: contabilidad-medica, Property 36: Repases con Múltiples Exámenes"
    - **Property 36: Repases con Múltiples Exámenes**
    - **Validates: Requirements 5.1**

  - [ ]* 13.42 Implementar Property 37: Repases con Cero o Más Gastos
    - Crear método test_repases_con_cero_o_mas_gastos()
    - Ejecutar 50 iteraciones creando repases con 0-5 gastos
    - Verificar que todos los casos funcionan correctamente
    - Agregar comentario: "Feature: contabilidad-medica, Property 37: Repases con Cero o Más Gastos"
    - **Property 37: Repases con Cero o Más Gastos**
    - **Validates: Requirements 7.1**

  - [ ]* 13.43 Implementar Property 38: Actualización de Repases
    - Crear método test_actualizacion_repases()
    - Ejecutar 50 iteraciones actualizando repases existentes
    - Verificar que cambios persisten y totales se recalculan
    - Agregar comentario: "Feature: contabilidad-medica, Property 38: Actualización de Repases"
    - **Property 38: Actualización de Repases**
    - **Validates: Requirements 9.3**

  - [ ]* 13.44 Implementar Property 39: Redirección de Usuarios No Autenticados
    - Crear método test_redireccion_usuarios_no_autenticados()
    - Intentar acceder a rutas protegidas sin autenticación
    - Verificar que se redirige a login
    - Agregar comentario: "Feature: contabilidad-medica, Property 39: Redirección de Usuarios No Autenticados"
    - **Property 39: Redirección de Usuarios No Autenticados**
    - **Validates: Requirements 1.5**

- [ ] 14. Implementar unit tests
  - [ ]* 14.1 Crear tests/Unit/Models/ExamenTest.php
    - Test: Seeder crea exactamente 7 exámenes
    - Test: Cada examen tiene precio_sin_nota < precio_con_nota
    - Test: Nombres de exámenes coinciden con especificación
    - _Requirements: 3.1, 3.2, 3.5_

  - [ ]* 14.2 Crear tests/Unit/Models/RepaseTest.php
    - Test: Repase usa SoftDeletes correctamente
    - Test: Casts de campos funcionan correctamente
    - Test: Relaciones están definidas correctamente
    - _Requirements: 4.6, 15.1, 15.2, 15.3_

  - [ ]* 14.3 Crear tests/Unit/Services/RepaseServiceTest.php
    - Test: calculateTotalExamenes con tipo_precio "sin_nota"
    - Test: calculateTotalExamenes con tipo_precio "con_nota"
    - Test: calculateTotalGastos con múltiples gastos
    - Test: calculateTotalNeto con valores específicos
    - Test: determineEstado retorna "pagado" si fecha_pago existe
    - Test: determineEstado retorna "pendiente" si fecha_pago es null
    - _Requirements: 5.6, 7.7, 8.1_

  - [ ]* 14.4 Crear tests/Unit/Services/DashboardServiceTest.php
    - Test: getMetrics calcula correctamente con datos específicos
    - Test: getMetrics aplica filtros correctamente
    - Test: Métodos de charts retornan formato correcto para Chart.js
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [ ]* 14.5 Crear tests/Feature/Clinicas/ClinicaCRUDTest.php
    - Test: Usuario autenticado puede crear clínica
    - Test: Usuario autenticado puede ver listado de clínicas
    - Test: Usuario autenticado puede editar clínica
    - Test: Usuario autenticado puede eliminar clínica sin repases
    - Test: No se puede eliminar clínica con repases asociados
    - _Requirements: 2.2, 2.3, 2.4, 2.5_

  - [ ]* 14.6 Crear tests/Feature/Repases/RepaseCreationTest.php
    - Test: Usuario autenticado puede crear repase válido
    - Test: Repase se crea con exámenes y gastos en transacción
    - Test: Cálculos automáticos son correctos
    - Test: Estado se establece automáticamente según fecha_pago
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ]* 14.7 Crear tests/Feature/Repases/RepaseUpdateTest.php
    - Test: Usuario autenticado puede actualizar repase
    - Test: Actualización recalcula totales correctamente
    - Test: Cambiar tipo_precio recalcula subtotales
    - _Requirements: 9.3, 5.7_

  - [ ]* 14.8 Crear tests/Feature/Repases/RepaseDeletionTest.php
    - Test: Repase pendiente puede ser soft deleted
    - Test: Repase pagado no puede ser eliminado
    - Test: Soft delete establece deleted_at
    - _Requirements: 9.4, 9.5_

  - [ ]* 14.9 Crear tests/Feature/Repases/RepaseTransactionTest.php
    - Test: Rollback en caso de error durante creación
    - Test: Rollback en caso de error durante actualización
    - Test: Cascade delete de relaciones funciona
    - _Requirements: 19.1, 19.2, 19.3, 18.3_

  - [ ]* 14.10 Crear tests/Feature/Dashboard/DashboardMetricsTest.php
    - Test: Dashboard muestra métricas correctas sin filtros
    - Test: Dashboard muestra métricas correctas con filtro de clínica
    - Test: Dashboard muestra métricas correctas con filtro de estado
    - Test: Dashboard muestra métricas correctas con filtro de fechas
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8_

  - [ ]* 14.11 Crear tests/Feature/Dashboard/DashboardFiltersTest.php
    - Test: Filtros múltiples se aplican con lógica AND
    - Test: Gráficos se actualizan según filtros
    - _Requirements: 10.9, 11.4_

  - [ ]* 14.12 Crear tests/Feature/Validation/RepaseValidationTest.php
    - Test: Validación rechaza campos requeridos faltantes
    - Test: Validación rechaza fechas inválidas
    - Test: Validación rechaza fecha_pago anterior a fecha
    - Test: Validación rechaza cantidad no positiva
    - Test: Validación rechaza total_consultas negativo
    - Test: Validación rechaza foreign keys inexistentes
    - Test: Validación rechaza valores enum inválidos
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8, 14.9, 14.10, 14.11_

  - [ ]* 14.13 Crear tests/Feature/Validation/GastoValidationTest.php
    - Test: Validación rechaza tipos de gasto inválidos
    - Test: Validación requiere descripcion para tipo "extra"
    - Test: Validación rechaza montos negativos
    - Test: Validación rechaza montos con más de 2 decimales
    - _Requirements: 7.2, 7.3, 7.4, 7.5, 14.12_

  - [ ]* 14.14 Crear tests/Feature/Auth/AuthenticationTest.php
    - Test: Usuario no autenticado es redirigido a login
    - Test: Usuario puede registrarse
    - Test: Usuario puede iniciar sesión
    - Test: Usuario puede cerrar sesión
    - _Requirements: 1.1, 1.2, 1.5_

- [ ] 15. Checkpoint - Ejecutar suite completa de tests
  - Ejecutar `php artisan test` y verificar que todos los tests pasan
  - Ejecutar `php artisan test --coverage` para verificar cobertura mínima de 80%
  - Revisar y corregir cualquier test fallido
  - Asegurar que todos los tests pasan, preguntar al usuario si surgen dudas

- [ ] 16. Documentación y configuración final
  - [x] 16.1 Crear README.md con instrucciones de instalación
    - Documentar requisitos del sistema: PHP 8.2+, MySQL 8.0+, Composer, Node.js
    - Documentar pasos de instalación: clonar repo, composer install, npm install, configurar .env, generar key, migrar, seed
    - Documentar comandos útiles: php artisan serve, npm run dev, php artisan test
    - Documentar estructura del proyecto
    - Documentar credenciales de acceso por defecto (si aplica)
    - _Requirements: 17.1, 17.2, 17.6_

  - [x] 16.2 Configurar archivo .env.example
    - Incluir todas las variables necesarias con valores de ejemplo
    - Documentar cada variable con comentarios
    - _Requirements: 17.2_

  - [x] 16.3 Verificar configuración de logging
    - Configurar logs en storage/logs/laravel.log
    - Verificar que errores de transacciones se loguean correctamente
    - _Requirements: 19.5_

  - [x] 16.4 Optimizar configuración de producción
    - Documentar comandos de optimización: config:cache, route:cache, view:cache
    - Documentar configuración de queue si se implementa en futuro
    - _Requirements: 17.1_

- [x] 17. Checkpoint final - Verificación completa del sistema
  - Ejecutar `php artisan migrate:fresh --seed`
  - Ejecutar `php artisan test`
  - Iniciar servidor y probar manualmente todos los flujos
  - Verificar que dashboard muestra datos correctos
  - Verificar que calendario funciona correctamente
  - Verificar que filtros funcionan en todas las vistas
  - Verificar que cálculos automáticos son precisos
  - Verificar que validaciones funcionan correctamente
  - Verificar que transacciones mantienen integridad de datos
  - Asegurar que todo funciona correctamente, preguntar al usuario si surgen dudas

## Notas

- Las tareas marcadas con `*` son opcionales (tests) y pueden omitirse para un MVP más rápido
- Cada tarea referencia los requisitos específicos para trazabilidad
- Los checkpoints aseguran validación incremental
- Los property tests validan propiedades universales de correctness
- Los unit tests validan ejemplos específicos y edge cases
- La implementación sigue las mejores prácticas de Laravel 12
- El código incluye comentarios en español para facilitar mantenimiento
- La arquitectura está preparada para escalar a multi-tenancy en el futuro
