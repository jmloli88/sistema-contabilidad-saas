# Requirements Document

## Introduction

El Sistema de Contabilidad Médica es una aplicación web diseñada para registrar y gestionar los ingresos y gastos diarios generados durante visitas a clínicas médicas. El sistema permite el registro de "repases médicos" que incluyen exámenes realizados, consultas médicas y gastos operativos, proporcionando un control financiero completo con visualizaciones y reportes.

## Glossary

- **Sistema**: La aplicación web de contabilidad médica
- **Usuario**: Profesional médico autenticado que utiliza el sistema
- **Repase**: Registro diario de actividad médica en una clínica que incluye ingresos y gastos
- **Clínica**: Establecimiento médico donde se realizan los repases
- **Examen**: Procedimiento médico con precio definido (ej: electroencefalograma)
- **Tipo_Precio**: Modalidad de facturación que puede ser "con_nota" o "sin_nota"
- **Estado_Repase**: Condición del pago que puede ser "pendiente" o "pagado"
- **Gasto**: Egreso asociado a un repase (doctor, técnico, laudos, gasolina, extra)
- **Total_Neto**: Resultado de (total_examenes + total_consultas) - total_gastos
- **Dashboard**: Panel principal con métricas y visualizaciones financieras
- **Calendario**: Vista mensual de repases organizados por fecha

## Requirements

### Requirement 1: Autenticación de Usuarios

**User Story:** Como usuario, quiero autenticarme en el sistema, para que pueda acceder de forma segura a mis datos de contabilidad médica.

#### Acceptance Criteria

1. THE Sistema SHALL provide login functionality using Laravel Breeze
2. THE Sistema SHALL provide user registration functionality
3. THE Sistema SHALL provide password recovery functionality
4. THE Sistema SHALL protect all routes except login and registration with authentication middleware
5. WHEN a non-authenticated user attempts to access protected routes, THE Sistema SHALL redirect to login page

### Requirement 2: Gestión de Clínicas

**User Story:** Como usuario, quiero gestionar las clínicas donde trabajo, para que pueda asociar mis repases a cada establecimiento.

#### Acceptance Criteria

1. THE Sistema SHALL store clinic data including nombre, direccion, telefono
2. THE Sistema SHALL provide create functionality for Clínica
3. THE Sistema SHALL provide read functionality for Clínica
4. THE Sistema SHALL provide update functionality for Clínica
5. THE Sistema SHALL provide delete functionality for Clínica
6. THE Sistema SHALL display a list of all Clínica with pagination

### Requirement 3: Catálogo de Exámenes

**User Story:** Como administrador del sistema, quiero tener un catálogo predefinido de exámenes con sus precios, para que los cálculos sean consistentes y precisos.

#### Acceptance Criteria

1. THE Sistema SHALL store exactly seven predefined Examen records with precio_sin_nota and precio_con_nota
2. THE Sistema SHALL seed Examen records with the following data: "Electroencefalograma c/ mapeamento 3d + foto estimulo" (precio_sin_nota: 200, precio_con_nota: 220), "Electroencefalograma c/ mapa" (120/140), "Electroencefalograma" (100/120), "Electroneuromiografia MEMBROS unilateral" (150/180), "Electroneuromiografia FACIAL unilateral" (170/200), "Potencial evocado VISUAL unilateral" (146/166), "Potencial evocado AUDITIVO unilateral" (146/166)
3. THE Sistema SHALL prevent modification of precio_sin_nota and precio_con_nota through the user interface
4. THE Sistema SHALL make all Examen records available for selection during Repase creation
5. THE Sistema SHALL validate that precio_sin_nota is less than precio_con_nota for each Examen

### Requirement 4: Creación de Repases

**User Story:** Como usuario, quiero crear un repase médico diario, para que pueda registrar todos los ingresos y gastos de mi visita a una clínica.

#### Acceptance Criteria

1. WHEN creating a Repase, THE Sistema SHALL require clinica_id, fecha, tipo_precio, and estado
2. WHEN creating a Repase, THE Sistema SHALL allow optional fields: fecha_pago, observaciones
3. WHEN creating a Repase, THE Sistema SHALL set estado to "pendiente" by default
4. WHEN fecha_pago is provided, THE Sistema SHALL automatically set estado to "pagado"
5. THE Sistema SHALL use database transactions for Repase creation including related examenes and gastos
6. THE Sistema SHALL apply SoftDeletes to Repase records

### Requirement 5: Selección de Exámenes en Repase

**User Story:** Como usuario, quiero seleccionar los exámenes realizados y sus cantidades, para que el sistema calcule automáticamente los ingresos por exámenes.

#### Acceptance Criteria

1. WHEN creating a Repase, THE Sistema SHALL allow selection of one or more Examen records
2. WHEN an Examen is selected, THE Sistema SHALL require input of cantidad as a positive integer
3. WHEN tipo_precio is "sin_nota", THE Sistema SHALL use precio_sin_nota from the Examen for subtotal calculation
4. WHEN tipo_precio is "con_nota", THE Sistema SHALL use precio_con_nota from the Examen for subtotal calculation
5. FOR EACH selected Examen, THE Sistema SHALL calculate subtotal as cantidad multiplied by the corresponding precio based on tipo_precio
6. THE Sistema SHALL calculate total_examenes as the sum of all Examen subtotals within the Repase
7. WHEN tipo_precio changes after Examen selection, THE Sistema SHALL recalculate all subtotals and total_examenes
8. THE Sistema SHALL validate all monetary calculations in the backend before persisting to database
9. FOR ALL Repase records, THE Sistema SHALL ensure that recalculating total_examenes from stored repase_examenes produces the same value (invariant property)

### Requirement 6: Registro de Consultas

**User Story:** Como usuario, quiero registrar el ingreso total por consultas médicas, para que se incluya en el cálculo de ingresos totales.

#### Acceptance Criteria

1. WHEN creating a Repase, THE Sistema SHALL provide a field for total_consultas
2. THE Sistema SHALL accept total_consultas as a manual numeric input
3. THE Sistema SHALL validate that total_consultas is a non-negative number
4. THE Sistema SHALL include total_consultas in the calculation of Total_Neto

### Requirement 7: Registro de Gastos

**User Story:** Como usuario, quiero registrar todos los gastos del día, para que el sistema calcule el total neto correctamente.

#### Acceptance Criteria

1. WHEN creating a Repase, THE Sistema SHALL allow adding zero or more Gasto records
2. THE Sistema SHALL support exactly five gasto tipos: "doctor", "tecnico", "laudos", "gasolina", "extra"
3. WHEN tipo is "extra", THE Sistema SHALL require a descripcion field with minimum length of 3 characters
4. WHEN tipo is not "extra", THE Sistema SHALL allow descripcion to be optional
5. FOR EACH Gasto, THE Sistema SHALL require monto as a positive decimal number with maximum 2 decimal places
6. THE Sistema SHALL allow dynamic addition and removal of Gasto records in the form interface
7. THE Sistema SHALL calculate total_gastos as the sum of all Gasto monto values within the Repase
8. FOR ALL Repase records, THE Sistema SHALL ensure that recalculating total_gastos from stored Gasto records produces the same value (invariant property)

### Requirement 8: Cálculo de Total Neto

**User Story:** Como usuario, quiero que el sistema calcule automáticamente el total neto, para que conozca mi ganancia real del día.

#### Acceptance Criteria

1. THE Sistema SHALL calculate Total_Neto using the formula: (total_examenes + total_consultas) - total_gastos
2. THE Sistema SHALL prevent modification of total_examenes through the user interface
3. THE Sistema SHALL prevent modification of total_gastos through the user interface
4. THE Sistema SHALL prevent modification of Total_Neto through the user interface
5. WHEN any component value changes (total_examenes, total_consultas, or total_gastos), THE Sistema SHALL recalculate Total_Neto
6. THE Sistema SHALL validate the Total_Neto calculation in the backend before persisting to database
7. FOR ALL Repase records, THE Sistema SHALL ensure that Total_Neto equals (total_examenes + total_consultas) - total_gastos within 0.01 precision (invariant property)

### Requirement 9: Gestión de Repases

**User Story:** Como usuario, quiero ver, editar y eliminar mis repases, para que pueda mantener mis registros actualizados.

#### Acceptance Criteria

1. THE Sistema SHALL display a list of all Repase records with pagination
2. THE Sistema SHALL provide view functionality for individual Repase details
3. THE Sistema SHALL provide edit functionality for Repase records
4. WHEN estado is "pagado", THE Sistema SHALL prevent deletion of the Repase
5. WHEN estado is "pendiente", THE Sistema SHALL allow soft deletion of the Repase
6. THE Sistema SHALL display all related examenes and gastos when viewing a Repase

### Requirement 10: Dashboard con Métricas

**User Story:** Como usuario, quiero ver un dashboard con métricas financieras, para que pueda analizar mi desempeño económico.

#### Acceptance Criteria

1. THE Sistema SHALL display total_ingresos calculated as the sum of (total_examenes + total_consultas) from all Repase records
2. THE Sistema SHALL display total_gastos calculated as the sum of total_gastos from all Repase records
3. THE Sistema SHALL display Total_Neto calculated as total_ingresos minus total_gastos
4. THE Sistema SHALL display total_pendiente calculated as the sum of Total_Neto from Repase records where estado equals "pendiente"
5. THE Sistema SHALL display total_pagado calculated as the sum of Total_Neto from Repase records where estado equals "pagado"
6. WHEN a Clínica filter is selected, THE Sistema SHALL recalculate all metrics using only Repase records for that Clínica
7. WHEN an Estado_Repase filter is selected, THE Sistema SHALL recalculate all metrics using only Repase records with that estado
8. WHEN a date range filter is applied, THE Sistema SHALL recalculate all metrics using only Repase records where fecha falls within the specified range
9. WHEN multiple filters are applied simultaneously, THE Sistema SHALL apply all filters using AND logic

### Requirement 11: Visualizaciones Gráficas

**User Story:** Como usuario, quiero ver gráficos de mis datos financieros, para que pueda identificar tendencias y patrones.

#### Acceptance Criteria

1. THE Sistema SHALL display a monthly chart comparing ingresos vs gastos using Chart.js
2. THE Sistema SHALL display a chart showing totals grouped by Clínica
3. THE Sistema SHALL display a chart comparing repases pagados vs pendientes
4. WHEN filters are applied, THE Sistema SHALL update all charts accordingly
5. THE Sistema SHALL render charts with clear labels and legends

### Requirement 12: Vista de Calendario

**User Story:** Como usuario, quiero ver mis repases en un calendario mensual, para que pueda visualizar mi actividad por fecha.

#### Acceptance Criteria

1. THE Sistema SHALL display a monthly calendar view of all Repase records
2. WHEN a Repase has estado "pendiente", THE Sistema SHALL display it in red color
3. WHEN a Repase has estado "pagado", THE Sistema SHALL display it in green color
4. WHEN a calendar date is clicked, THE Sistema SHALL display the Repase details
5. WHEN a clínica filter is applied, THE Sistema SHALL show only Repase records for that Clínica
6. THE Sistema SHALL use FullCalendar JS library for calendar functionality

### Requirement 13: Búsqueda y Filtrado

**User Story:** Como usuario, quiero buscar y filtrar mis repases, para que pueda encontrar información específica rápidamente.

#### Acceptance Criteria

1. THE Sistema SHALL provide a search field for Repase records
2. THE Sistema SHALL provide filter options for Clínica
3. THE Sistema SHALL provide filter options for Estado_Repase
4. THE Sistema SHALL provide filter options for date range
5. THE Sistema SHALL apply filters dynamically without page reload
6. THE Sistema SHALL display filtered results with pagination

### Requirement 14: Validación de Datos

**User Story:** Como usuario, quiero que el sistema valide mis datos de entrada, para que evite errores en los registros.

#### Acceptance Criteria

1. THE Sistema SHALL use Laravel FormRequest classes for validation of all user input
2. WHEN invalid data is submitted, THE Sistema SHALL return validation errors with descriptive messages in Spanish
3. THE Sistema SHALL validate that fecha is a valid date in format Y-m-d
4. WHEN fecha_pago is provided, THE Sistema SHALL validate that it is a valid date in format Y-m-d
5. WHEN fecha_pago is provided, THE Sistema SHALL validate that fecha_pago is greater than or equal to fecha
6. THE Sistema SHALL validate that all monetary amounts (total_consultas, monto) are non-negative decimal numbers
7. THE Sistema SHALL validate that cantidad for Examen selection is a positive integer greater than zero
8. THE Sistema SHALL validate that clinica_id references an existing Clínica record
9. THE Sistema SHALL validate that examen_id references an existing Examen record
10. THE Sistema SHALL validate that tipo_precio is either "sin_nota" or "con_nota"
11. THE Sistema SHALL validate that estado is either "pendiente" or "pagado"
12. THE Sistema SHALL validate that Gasto tipo is one of: "doctor", "tecnico", "laudos", "gasolina", "extra"

### Requirement 15: Relaciones de Base de Datos

**User Story:** Como desarrollador, quiero que las relaciones Eloquent estén correctamente definidas, para que el código sea mantenible y eficiente.

#### Acceptance Criteria

1. THE Sistema SHALL define that Repase belongsTo Clínica
2. THE Sistema SHALL define that Repase hasMany repase_examenes
3. THE Sistema SHALL define that Repase hasMany gastos
4. THE Sistema SHALL define that repase_examenes belongsTo Repase
5. THE Sistema SHALL define that repase_examenes belongsTo examen
6. THE Sistema SHALL define that Gasto belongsTo Repase
7. THE Sistema SHALL use eager loading to prevent N+1 query problems

### Requirement 16: Interfaz de Usuario

**User Story:** Como usuario, quiero una interfaz limpia y profesional, para que pueda trabajar cómodamente con el sistema.

#### Acceptance Criteria

1. THE Sistema SHALL use Tailwind CSS for styling
2. THE Sistema SHALL implement a minimalist design approach
3. THE Sistema SHALL display flash messages for user actions
4. THE Sistema SHALL provide responsive design for mobile devices
5. THE Sistema SHALL use consistent color scheme throughout the application
6. THE Sistema SHALL provide clear navigation between modules

### Requirement 17: Arquitectura Preparada para SaaS

**User Story:** Como desarrollador, quiero que el código esté preparado para multiusuario, para que pueda escalar a un modelo SaaS en el futuro.

#### Acceptance Criteria

1. THE Sistema SHALL structure code to support future multi-tenancy
2. THE Sistema SHALL use proper separation of concerns in MVC architecture
3. THE Sistema SHALL implement RESTful controllers
4. THE Sistema SHALL use service classes for complex business logic
5. THE Sistema SHALL include comprehensive code comments in Spanish
6. THE Sistema SHALL follow Laravel best practices and conventions

### Requirement 18: Migraciones y Seeders

**User Story:** Como desarrollador, quiero migraciones completas y seeders, para que pueda configurar la base de datos fácilmente.

#### Acceptance Criteria

1. THE Sistema SHALL provide migrations for all database tables: users, clinicas, examenes, repases, repase_examenes, gastos
2. THE Sistema SHALL provide a seeder that creates exactly seven Examen records with predefined data
3. THE Sistema SHALL define foreign key constraints with appropriate cascade actions in migrations
4. THE Sistema SHALL define indexes on foreign keys and frequently queried columns for performance optimization
5. THE Sistema SHALL include created_at and updated_at timestamp columns in all tables
6. THE Sistema SHALL include deleted_at column in repases table for soft delete functionality
7. THE Sistema SHALL define decimal columns with precision (10,2) for all monetary fields

### Requirement 19: Integridad Transaccional

**User Story:** Como usuario, quiero que mis datos sean consistentes, para que no pierda información si ocurre un error durante el guardado.

#### Acceptance Criteria

1. WHEN creating a Repase with related Examen and Gasto records, THE Sistema SHALL wrap all database operations in a single transaction
2. WHEN updating a Repase with related records, THE Sistema SHALL wrap all database operations in a single transaction
3. IF any database operation fails within a transaction, THE Sistema SHALL rollback all changes
4. WHEN a transaction is rolled back, THE Sistema SHALL display an error message to the Usuario
5. THE Sistema SHALL log transaction failures for debugging purposes
