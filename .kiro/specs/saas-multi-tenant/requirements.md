# Requirements Document

## Introduction

Este documento define los requerimientos para transformar el sistema médico/clínico actual en una plataforma SaaS multi-tenant. El sistema actual es una aplicación Laravel monolítica que gestiona clínicas médicas con módulos de agendas, repases, exámenes, reportes y análisis predictivo. La transformación permitirá que múltiples empresas/clínicas utilicen el sistema de forma aislada, con gestión de suscripciones y administración centralizada.

## Glossary

- **Tenant**: Una organización o empresa cliente que utiliza el sistema SaaS (una clínica o grupo de clínicas)
- **Tenant_Context**: El contexto de ejecución que identifica qué tenant está realizando una operación
- **Tenant_Isolation**: Mecanismo que garantiza que los datos de un tenant no sean accesibles por otros tenants
- **Subscription**: Plan de servicio contratado por un tenant con características y límites específicos
- **Subscription_Manager**: Componente que gestiona planes, límites y facturación de suscripciones
- **Super_Admin**: Usuario con acceso global al sistema para gestionar todos los tenants
- **Tenant_Admin**: Usuario administrador dentro de un tenant específico
- **Tenant_User**: Usuario regular dentro de un tenant específico
- **Tenant_Database**: Base de datos o esquema dedicado a un tenant específico
- **Shared_Database**: Base de datos compartida con columna tenant_id para aislamiento lógico
- **Tenant_Resolver**: Componente que identifica el tenant actual basado en dominio, subdominio o token
- **Onboarding_Process**: Proceso de registro y configuración inicial de un nuevo tenant
- **Usage_Metrics**: Métricas de uso del sistema por tenant para facturación y análisis
- **Feature_Flag**: Configuración que habilita o deshabilita funcionalidades según el plan de suscripción
- **Tenant_Migration**: Proceso de migración de datos de un tenant entre entornos o planes
- **Cross_Tenant_Query**: Consulta que accede a datos de múltiples tenants (solo para super admins)

## Requirements

### Requirement 1: Identificación y Resolución de Tenants

**User Story:** Como desarrollador del sistema, quiero que cada request HTTP identifique automáticamente el tenant correspondiente, para que todas las operaciones subsecuentes operen en el contexto correcto.

#### Acceptance Criteria

1. WHEN un request HTTP llega al sistema, THE Tenant_Resolver SHALL identificar el tenant basado en el subdominio de la URL
2. WHEN un request HTTP llega al sistema sin subdominio válido, THE Tenant_Resolver SHALL identificar el tenant basado en un token de autenticación
3. WHEN el Tenant_Resolver no puede identificar un tenant válido, THE System SHALL retornar un error HTTP 404 con mensaje descriptivo
4. THE Tenant_Context SHALL estar disponible globalmente durante toda la ejecución del request
5. WHEN un usuario autenticado realiza un request, THE System SHALL validar que el usuario pertenece al tenant identificado
6. THE Tenant_Resolver SHALL cachear la información del tenant durante el request para optimizar rendimiento

### Requirement 2: Aislamiento de Datos por Tenant

**User Story:** Como tenant, quiero que mis datos estén completamente aislados de otros tenants, para garantizar privacidad y seguridad de la información médica.

#### Acceptance Criteria

1. THE System SHALL implementar aislamiento de datos mediante columna tenant_id en todas las tablas de datos de negocio
2. WHEN se ejecuta una consulta Eloquent, THE System SHALL aplicar automáticamente un scope global que filtre por tenant_id del Tenant_Context
3. WHEN se crea un nuevo registro, THE System SHALL asignar automáticamente el tenant_id del Tenant_Context
4. THE System SHALL prevenir la modificación manual del campo tenant_id en operaciones de usuario
5. WHEN un Super_Admin ejecuta una Cross_Tenant_Query, THE System SHALL permitir deshabilitar temporalmente el scope global de tenant
6. THE System SHALL validar que todas las relaciones Eloquent respeten el aislamiento de tenant
7. WHEN se ejecuta una operación de eliminación, THE System SHALL verificar que el registro pertenece al tenant actual

### Requirement 3: Gestión de Modelos Multi-Tenant

**User Story:** Como desarrollador, quiero que todos los modelos Eloquent soporten automáticamente multi-tenancy, para mantener consistencia en el aislamiento de datos.

#### Acceptance Criteria

1. THE System SHALL proveer un trait TenantScoped que aplique scopes globales de tenant_id
2. WHEN un modelo usa el trait TenantScoped, THE System SHALL aplicar automáticamente filtros de tenant en todas las consultas
3. THE System SHALL proveer un trait TenantOwned para modelos que requieren validación explícita de propiedad
4. WHEN se accede a relaciones entre modelos, THE System SHALL mantener el contexto de tenant en consultas relacionadas
5. THE System SHALL proveer métodos para consultas sin scope de tenant (withoutTenantScope) solo para Super_Admin
6. THE System SHALL validar en tiempo de ejecución que modelos críticos implementen el trait TenantScoped

### Requirement 4: Migración de Esquema Multi-Tenant

**User Story:** Como administrador del sistema, quiero que las migraciones de base de datos se apliquen correctamente en un entorno multi-tenant, para mantener la integridad del esquema.

#### Acceptance Criteria

1. THE System SHALL agregar columna tenant_id (UUID) a todas las tablas de datos de negocio existentes
2. THE System SHALL crear índices compuestos (tenant_id, id) en tablas principales para optimizar consultas
3. THE System SHALL crear índices compuestos (tenant_id, foreign_key) en tablas con relaciones para optimizar joins
4. THE System SHALL mantener tablas globales sin tenant_id (users, tenants, subscriptions, system_config)
5. WHEN se ejecuta una migración nueva, THE System SHALL incluir automáticamente tenant_id en tablas de negocio
6. THE System SHALL proveer una migración para poblar tenant_id en datos existentes durante la transición

### Requirement 5: Registro y Onboarding de Tenants

**User Story:** Como nuevo cliente, quiero registrarme en el sistema y configurar mi organización, para comenzar a utilizar la plataforma.

#### Acceptance Criteria

1. WHEN un nuevo cliente se registra, THE Onboarding_Process SHALL crear un registro de tenant con identificador único
2. THE Onboarding_Process SHALL asignar un subdominio único al tenant basado en el nombre de la organización
3. THE Onboarding_Process SHALL crear el primer usuario Tenant_Admin para el tenant
4. THE Onboarding_Process SHALL asignar un plan de suscripción inicial (trial o seleccionado)
5. THE Onboarding_Process SHALL inicializar datos de configuración predeterminados para el tenant
6. WHEN el onboarding se completa, THE System SHALL enviar un email de bienvenida con credenciales e instrucciones
7. THE Onboarding_Process SHALL validar que el subdominio solicitado no esté en uso
8. THE Onboarding_Process SHALL validar que el email del administrador no esté registrado en otro tenant

### Requirement 6: Gestión de Suscripciones y Planes

**User Story:** Como administrador del sistema, quiero definir planes de suscripción con diferentes características y límites, para monetizar el servicio según el valor entregado.

#### Acceptance Criteria

1. THE Subscription_Manager SHALL soportar múltiples planes de suscripción (Free, Basic, Professional, Enterprise)
2. WHEN se define un plan, THE Subscription_Manager SHALL permitir configurar límites de uso (usuarios, clínicas, repases mensuales, almacenamiento)
3. THE Subscription_Manager SHALL permitir configurar Feature_Flags por plan (análisis predictivo, reportes avanzados, API access)
4. WHEN un tenant alcanza un límite de su plan, THE System SHALL prevenir operaciones adicionales y mostrar mensaje informativo
5. THE Subscription_Manager SHALL permitir upgrades y downgrades de planes con aplicación inmediata o al final del período
6. THE Subscription_Manager SHALL calcular prorrateo de costos en cambios de plan durante el período de facturación
7. THE Subscription_Manager SHALL registrar historial de cambios de suscripción para auditoría

### Requirement 7: Control de Límites y Cuotas

**User Story:** Como sistema, quiero validar que los tenants no excedan los límites de su plan de suscripción, para garantizar uso justo de recursos.

#### Acceptance Criteria

1. WHEN un tenant intenta crear un usuario, THE System SHALL validar que no exceda el límite de usuarios de su plan
2. WHEN un tenant intenta crear una clínica, THE System SHALL validar que no exceda el límite de clínicas de su plan
3. WHEN un tenant intenta crear un repase, THE System SHALL validar que no exceda el límite mensual de repases de su plan
4. WHEN un tenant intenta subir un archivo, THE System SHALL validar que no exceda el límite de almacenamiento de su plan
5. THE System SHALL proveer un dashboard de uso actual vs límites del plan para Tenant_Admin
6. WHEN un tenant se acerca al 80% de un límite, THE System SHALL enviar notificación al Tenant_Admin
7. THE System SHALL permitir a Super_Admin ajustar límites temporalmente para casos especiales

### Requirement 8: Sistema de Autenticación Multi-Tenant

**User Story:** Como usuario, quiero autenticarme en mi organización específica, para acceder a los datos de mi tenant.

#### Acceptance Criteria

1. WHEN un usuario inicia sesión, THE System SHALL validar credenciales y verificar que el usuario pertenece al tenant del subdominio
2. THE System SHALL prevenir que un usuario de un tenant acceda a otro tenant incluso con credenciales válidas
3. THE System SHALL mantener tres niveles de roles: Super_Admin (global), Tenant_Admin (por tenant), Tenant_User (por tenant)
4. WHEN un Super_Admin inicia sesión, THE System SHALL permitir acceso a un panel de administración global
5. THE System SHALL generar tokens JWT que incluyan tenant_id para APIs stateless
6. WHEN un token JWT es validado, THE System SHALL verificar que el tenant_id del token coincida con el tenant del request
7. THE System SHALL implementar rate limiting por tenant para prevenir abuso

### Requirement 9: Panel de Administración de Super Admin

**User Story:** Como Super_Admin, quiero gestionar todos los tenants del sistema, para administrar la plataforma SaaS.

#### Acceptance Criteria

1. THE System SHALL proveer un panel de administración accesible solo para Super_Admin
2. THE Super_Admin SHALL poder listar todos los tenants con información de suscripción y uso
3. THE Super_Admin SHALL poder crear, editar, suspender y eliminar tenants
4. THE Super_Admin SHALL poder ver métricas agregadas de uso del sistema (usuarios totales, repases totales, ingresos)
5. THE Super_Admin SHALL poder impersonar un Tenant_Admin para soporte técnico
6. WHEN un Super_Admin impersona un usuario, THE System SHALL registrar la acción en un log de auditoría
7. THE Super_Admin SHALL poder ejecutar Cross_Tenant_Query para reportes y análisis globales
8. THE Super_Admin SHALL poder ajustar manualmente suscripciones y límites de tenants

### Requirement 10: Métricas de Uso y Facturación

**User Story:** Como sistema, quiero registrar métricas de uso por tenant, para soportar facturación y análisis de consumo.

#### Acceptance Criteria

1. THE System SHALL registrar Usage_Metrics diarias por tenant (usuarios activos, repases creados, almacenamiento usado, API calls)
2. THE System SHALL calcular costos mensuales basados en el plan de suscripción y uso excedente
3. WHEN finaliza un período de facturación, THE System SHALL generar una factura con detalle de uso y costos
4. THE System SHALL proveer un endpoint API para integración con sistemas de pago (Stripe, PayPal)
5. THE Subscription_Manager SHALL marcar tenants como morosos cuando el pago falla después de 3 intentos
6. WHEN un tenant está moroso, THE System SHALL restringir acceso a funcionalidades de escritura manteniendo acceso de lectura
7. THE System SHALL enviar recordatorios de pago 7, 3 y 1 días antes del vencimiento

### Requirement 11: Migración de Datos Existentes

**User Story:** Como administrador del sistema, quiero migrar los datos actuales del sistema monolítico al modelo multi-tenant, para preservar la información existente.

#### Acceptance Criteria

1. THE Tenant_Migration SHALL crear un tenant predeterminado para los datos existentes
2. THE Tenant_Migration SHALL asignar el tenant_id del tenant predeterminado a todos los registros existentes
3. THE Tenant_Migration SHALL validar integridad referencial después de asignar tenant_ids
4. THE Tenant_Migration SHALL crear usuarios del sistema actual como Tenant_Admin y Tenant_User del tenant predeterminado
5. THE Tenant_Migration SHALL preservar todas las relaciones entre entidades (repases, exámenes, clínicas)
6. THE Tenant_Migration SHALL generar un reporte de validación post-migración con estadísticas de registros migrados
7. IF la migración falla, THEN THE System SHALL revertir cambios y restaurar el estado anterior

### Requirement 12: Aislamiento de Archivos y Almacenamiento

**User Story:** Como tenant, quiero que mis archivos y documentos estén almacenados de forma aislada, para garantizar privacidad de información sensible.

#### Acceptance Criteria

1. THE System SHALL organizar archivos en directorios por tenant_id en el sistema de archivos
2. WHEN un tenant sube un archivo, THE System SHALL almacenarlo en storage/tenants/{tenant_id}/
3. WHEN un usuario solicita un archivo, THE System SHALL validar que el archivo pertenece al tenant del usuario
4. THE System SHALL calcular el uso de almacenamiento por tenant para control de cuotas
5. WHEN un tenant es eliminado, THE System SHALL eliminar todos sus archivos del almacenamiento
6. THE System SHALL soportar configuración de almacenamiento por tenant (local, S3, etc.)
7. THE System SHALL generar URLs firmadas temporales para acceso seguro a archivos

### Requirement 13: Configuración Personalizada por Tenant

**User Story:** Como Tenant_Admin, quiero personalizar la configuración de mi organización, para adaptar el sistema a mis necesidades específicas.

#### Acceptance Criteria

1. THE System SHALL permitir a cada tenant configurar su nombre, logo y colores de marca
2. THE System SHALL permitir a cada tenant configurar zona horaria y formato de fecha
3. THE System SHALL permitir a cada tenant configurar idioma predeterminado (español, inglés)
4. THE System SHALL permitir a cada tenant configurar notificaciones por email (frecuencia, tipos)
5. THE System SHALL permitir a cada tenant configurar parámetros del módulo predictivo según su plan
6. THE System SHALL almacenar configuraciones en tabla tenant_settings con clave-valor
7. WHEN se accede a una configuración no definida, THE System SHALL retornar un valor predeterminado del sistema

### Requirement 14: API Multi-Tenant

**User Story:** Como desarrollador externo, quiero consumir la API del sistema respetando el contexto multi-tenant, para integrar con sistemas de terceros.

#### Acceptance Criteria

1. THE System SHALL proveer endpoints API RESTful con autenticación mediante API tokens
2. WHEN se genera un API token, THE System SHALL asociarlo a un tenant y usuario específico
3. WHEN se recibe un request API, THE System SHALL identificar el tenant mediante el API token
4. THE System SHALL aplicar los mismos controles de aislamiento de datos en API que en la interfaz web
5. THE System SHALL aplicar rate limiting por tenant en endpoints API
6. THE System SHALL documentar la API usando OpenAPI/Swagger con ejemplos multi-tenant
7. WHERE el plan del tenant incluye API access, THE System SHALL permitir uso de endpoints API

### Requirement 15: Auditoría y Logs Multi-Tenant

**User Story:** Como Tenant_Admin, quiero ver un registro de actividades importantes en mi organización, para auditoría y seguridad.

#### Acceptance Criteria

1. THE System SHALL registrar eventos de auditoría por tenant (login, cambios de configuración, eliminaciones)
2. THE System SHALL almacenar logs de auditoría con tenant_id, user_id, acción, timestamp y datos relevantes
3. THE Tenant_Admin SHALL poder consultar logs de auditoría de su tenant
4. THE Super_Admin SHALL poder consultar logs de auditoría de todos los tenants
5. THE System SHALL retener logs de auditoría por 90 días para planes básicos y 365 días para planes enterprise
6. THE System SHALL permitir exportar logs de auditoría en formato CSV o JSON
7. THE System SHALL registrar intentos fallidos de acceso cross-tenant como eventos de seguridad

### Requirement 16: Backup y Recuperación por Tenant

**User Story:** Como Tenant_Admin, quiero poder respaldar y recuperar los datos de mi organización, para protegerme contra pérdida de información.

#### Acceptance Criteria

1. WHERE el plan del tenant incluye backups, THE System SHALL generar backups automáticos diarios de datos del tenant
2. THE System SHALL almacenar backups de forma aislada por tenant
3. THE Tenant_Admin SHALL poder descargar backups de su tenant en formato SQL o JSON
4. THE Tenant_Admin SHALL poder solicitar restauración de un backup específico
5. WHEN se solicita una restauración, THE System SHALL crear un snapshot del estado actual antes de restaurar
6. THE System SHALL notificar al Tenant_Admin cuando un backup se completa o falla
7. THE System SHALL retener backups según el plan (7 días para Basic, 30 días para Professional, 90 días para Enterprise)

### Requirement 17: Notificaciones Multi-Tenant

**User Story:** Como usuario, quiero recibir notificaciones relevantes de mi organización, para mantenerme informado de eventos importantes.

#### Acceptance Criteria

1. THE System SHALL enviar notificaciones por email respetando el contexto de tenant
2. THE System SHALL personalizar emails con el logo y colores del tenant
3. THE System SHALL incluir el subdominio del tenant en enlaces de emails
4. THE System SHALL permitir a cada tenant configurar plantillas de email personalizadas
5. THE System SHALL implementar cola de emails por tenant para evitar que un tenant afecte el envío de otros
6. THE System SHALL registrar métricas de emails enviados por tenant para control de cuotas
7. WHERE el plan del tenant incluye notificaciones SMS, THE System SHALL enviar notificaciones por SMS

### Requirement 18: Performance y Escalabilidad Multi-Tenant

**User Story:** Como usuario, quiero que el sistema responda rápidamente independientemente del número de tenants, para tener una experiencia fluida.

#### Acceptance Criteria

1. THE System SHALL cachear configuraciones de tenant para evitar consultas repetitivas
2. THE System SHALL implementar índices de base de datos optimizados para consultas multi-tenant
3. THE System SHALL particionar tablas grandes por tenant_id cuando sea beneficioso para performance
4. THE System SHALL implementar connection pooling para optimizar uso de conexiones de base de datos
5. THE System SHALL monitorear tiempos de respuesta por tenant para identificar problemas de performance
6. WHEN un tenant genera carga excesiva, THE System SHALL aplicar throttling para proteger a otros tenants
7. THE System SHALL soportar escalamiento horizontal agregando servidores de aplicación

### Requirement 19: Testing Multi-Tenant

**User Story:** Como desarrollador, quiero ejecutar tests automatizados en un entorno multi-tenant, para garantizar calidad del código.

#### Acceptance Criteria

1. THE System SHALL proveer factories y seeders que generen datos de múltiples tenants para testing
2. THE System SHALL proveer helpers de testing para cambiar contexto de tenant durante tests
3. THE System SHALL validar aislamiento de datos ejecutando tests que intenten acceso cross-tenant
4. THE System SHALL ejecutar suite de tests completa en modo multi-tenant en CI/CD
5. THE System SHALL proveer tests de integración que validen flujos completos multi-tenant
6. THE System SHALL proveer tests de performance que validen escalabilidad con múltiples tenants
7. THE System SHALL validar que migraciones funcionen correctamente en entorno multi-tenant

### Requirement 20: Documentación Multi-Tenant

**User Story:** Como desarrollador nuevo en el equipo, quiero documentación clara sobre la arquitectura multi-tenant, para entender y mantener el sistema.

#### Acceptance Criteria

1. THE System SHALL incluir documentación de arquitectura multi-tenant explicando decisiones de diseño
2. THE System SHALL documentar patrones de código para implementar nuevas funcionalidades multi-tenant
3. THE System SHALL documentar proceso de onboarding de nuevos tenants
4. THE System SHALL documentar proceso de migración de datos existentes
5. THE System SHALL documentar configuración de entornos de desarrollo multi-tenant
6. THE System SHALL documentar troubleshooting común de problemas multi-tenant
7. THE System SHALL mantener un changelog de cambios relacionados con multi-tenancy
