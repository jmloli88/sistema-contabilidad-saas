# Sistema de Contabilidad Médica

Sistema web desarrollado en Laravel 12 para gestionar ingresos y gastos generados durante visitas médicas a clínicas. Permite registrar "repases médicos" que incluyen exámenes realizados, consultas y gastos operativos, proporcionando control financiero completo con visualizaciones y reportes.

## Características Principales

- 🔐 **Autenticación**: Sistema completo con Laravel Breeze (login, registro, recuperación de contraseña)
- 🏥 **Gestión de Clínicas**: CRUD completo para administrar establecimientos médicos
- 📋 **Catálogo de Exámenes**: 7 exámenes predefinidos con precios diferenciados (con/sin nota)
- 💰 **Gestión de Repases**: Registro de ingresos y gastos con cálculos automáticos
- 📊 **Dashboard**: Métricas financieras con filtros dinámicos
- 📈 **Visualizaciones**: Gráficos interactivos con Chart.js
- 📅 **Calendario**: Vista mensual de repases con FullCalendar
- 🔍 **Búsqueda y Filtrado**: Filtros por clínica, estado y rango de fechas
- ✅ **Validación Robusta**: Validación de datos en frontend y backend
- 🔄 **Integridad Transaccional**: Transacciones de base de datos para consistencia de datos

## Requisitos del Sistema

- **PHP**: 8.2 o superior
- **MySQL**: 8.0 o superior
- **Composer**: 2.x
- **Node.js**: 18.x o superior
- **NPM**: 9.x o superior

## Instalación

### 1. Clonar el Repositorio

```bash
git clone <url-del-repositorio>
cd contabilidad-medica
```

### 2. Instalar Dependencias de PHP

```bash
composer install
```

### 3. Instalar Dependencias de Node.js

```bash
npm install
```

### 4. Configurar Variables de Entorno

Copiar el archivo de ejemplo y configurar las variables:

```bash
cp .env.example .env
```

Editar el archivo `.env` y configurar las siguientes variables:

```env
APP_NAME="Contabilidad Médica"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=contabilidad_medica
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### 5. Generar Clave de Aplicación

```bash
php artisan key:generate
```

### 6. Crear Base de Datos

Crear la base de datos en MySQL:

```sql
CREATE DATABASE contabilidad_medica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Ejecutar Migraciones

```bash
php artisan migrate
```

### 8. Ejecutar Seeders

Esto creará los 7 exámenes predefinidos:

```bash
php artisan db:seed --class=ExamenSeeder
```

O ejecutar todas las migraciones y seeders desde cero:

```bash
php artisan migrate:fresh --seed
```

### 9. Compilar Assets Frontend

Para desarrollo:

```bash
npm run dev
```

Para producción:

```bash
npm run build
```

### 10. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

La aplicación estará disponible en: `http://localhost:8000`

## Comandos Útiles

### Desarrollo

```bash
# Iniciar servidor de desarrollo
php artisan serve

# Compilar assets en modo desarrollo (con hot reload)
npm run dev

# Ejecutar ambos simultáneamente
composer dev
```

### Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests con cobertura
php artisan test --coverage

# Ejecutar tests específicos
php artisan test --filter RepaseTest
```

### Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Resetear base de datos y ejecutar seeders
php artisan migrate:fresh --seed

# Ejecutar seeder específico
php artisan db:seed --class=ExamenSeeder
```

### Optimización (Producción)

```bash
# Optimización completa (ejecuta config:cache, route:cache, view:cache)
php artisan optimize

# Cachear configuración
php artisan config:cache

# Cachear rutas
php artisan route:cache

# Cachear vistas
php artisan view:cache

# Limpiar todas las cachés
php artisan optimize:clear
```

**📖 Para una guía completa de optimización y despliegue en producción, consulta:**
- [docs/PRODUCTION_OPTIMIZATION.md](docs/PRODUCTION_OPTIMIZATION.md) - Guía detallada de optimización, configuración de queue, monitoreo y checklist de despliegue

### Mantenimiento

```bash
# Limpiar logs
php artisan log:clear

# Ver logs en tiempo real
php artisan pail

# Formatear código (Laravel Pint)
./vendor/bin/pint
```

## Estructura del Proyecto

```
contabilidad-medica/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/              # Controladores de autenticación (Breeze)
│   │   │   ├── CalendarioController.php
│   │   │   ├── ClinicaController.php
│   │   │   ├── DashboardController.php
│   │   │   └── RepaseController.php
│   │   └── Requests/
│   │       ├── StoreClinicaRequest.php
│   │       ├── UpdateClinicaRequest.php
│   │       ├── StoreRepaseRequest.php
│   │       └── UpdateRepaseRequest.php
│   ├── Models/
│   │   ├── Clinica.php
│   │   ├── Examen.php
│   │   ├── Gasto.php
│   │   ├── Repase.php
│   │   ├── RepaseExamen.php
│   │   └── User.php
│   └── Services/
│       ├── DashboardService.php   # Lógica de negocio del dashboard
│       └── RepaseService.php      # Lógica de negocio de repases
├── database/
│   ├── factories/                 # Factories para testing
│   ├── migrations/                # Migraciones de base de datos
│   └── seeders/
│       └── ExamenSeeder.php       # Seeder de 7 exámenes predefinidos
├── resources/
│   ├── css/
│   │   └── app.css                # Estilos Tailwind CSS
│   ├── js/
│   │   ├── app.js                 # Punto de entrada JavaScript
│   │   ├── calendario.js          # Lógica de FullCalendar
│   │   └── dashboard-charts.js    # Lógica de Chart.js
│   └── views/
│       ├── auth/                  # Vistas de autenticación (Breeze)
│       ├── calendario/
│       │   └── index.blade.php
│       ├── clinicas/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── show.blade.php
│       ├── dashboard/
│       │   └── index.blade.php
│       ├── repases/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── show.blade.php
│       └── layouts/
│           ├── app.blade.php      # Layout principal
│           └── navigation.blade.php
├── routes/
│   ├── web.php                    # Rutas web
│   └── auth.php                   # Rutas de autenticación (Breeze)
└── tests/
    ├── Feature/                   # Tests de integración
    └── Unit/                      # Tests unitarios
```

## Stack Tecnológico

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Base de Datos**: MySQL 8.0+
- **Autenticación**: Laravel Breeze
- **ORM**: Eloquent

### Frontend
- **Template Engine**: Blade
- **CSS Framework**: Tailwind CSS 3.x
- **JavaScript**: Alpine.js 3.x
- **Gráficos**: Chart.js 4.x
- **Calendario**: FullCalendar 6.x
- **Build Tool**: Vite 7.x

## Módulos del Sistema

### 1. Autenticación
- Login con email y contraseña
- Registro de nuevos usuarios
- Recuperación de contraseña
- Protección de rutas con middleware

### 2. Gestión de Clínicas
- Crear, editar, ver y eliminar clínicas
- Campos: nombre, dirección, teléfono
- Listado paginado

### 3. Catálogo de Exámenes
7 exámenes predefinidos con precios diferenciados:
1. Electroencefalograma c/ mapeamento 3d + foto estimulo (200/220)
2. Electroencefalograma c/ mapa (120/140)
3. Electroencefalograma (100/120)
4. Electroneuromiografia MEMBROS unilateral (150/180)
5. Electroneuromiografia FACIAL unilateral (170/200)
6. Potencial evocado VISUAL unilateral (146/166)
7. Potencial evocado AUDITIVO unilateral (146/166)

### 4. Gestión de Repases
- Crear y editar repases médicos
- Selección múltiple de exámenes con cantidades
- Registro de consultas médicas
- Registro de gastos (doctor, técnico, laudos, gasolina, extra)
- Cálculos automáticos de totales
- Estados: pendiente/pagado
- Soft delete para repases pendientes
- Protección contra eliminación de repases pagados

### 5. Dashboard
- Métricas financieras:
  - Total de ingresos
  - Total de gastos
  - Total neto
  - Total pendiente
  - Total pagado
- Filtros dinámicos por clínica, estado y rango de fechas
- Gráficos interactivos:
  - Ingresos vs Gastos por mes
  - Totales por clínica
  - Pagados vs Pendientes

### 6. Calendario
- Vista mensual de repases
- Color coding: rojo (pendiente), verde (pagado)
- Filtro por clínica
- Click en evento para ver detalles

## Credenciales de Acceso

El sistema no incluye usuarios predefinidos. Para acceder:

1. Registrar un nuevo usuario en: `http://localhost:8000/register`
2. Completar el formulario con:
   - Nombre
   - Email
   - Contraseña (mínimo 8 caracteres)
   - Confirmación de contraseña

3. Iniciar sesión en: `http://localhost:8000/login`

## Cálculos Automáticos

El sistema realiza los siguientes cálculos automáticamente:

### Subtotal por Examen
```
subtotal = cantidad × precio_unitario

donde precio_unitario = 
  - precio_sin_nota (si tipo_precio = "sin_nota")
  - precio_con_nota (si tipo_precio = "con_nota")
```

### Total de Exámenes
```
total_examenes = Σ(subtotal de cada examen)
```

### Total de Gastos
```
total_gastos = Σ(monto de cada gasto)
```

### Total Neto
```
total_neto = (total_examenes + total_consultas) - total_gastos
```

### Estado del Repase
```
estado = fecha_pago != null ? "pagado" : "pendiente"
```

## Validaciones

El sistema implementa validaciones robustas en frontend y backend:

- Campos requeridos: clínica, fecha, tipo de precio, estado
- Fechas válidas en formato Y-m-d
- Fecha de pago debe ser posterior o igual a fecha del repase
- Cantidades de exámenes deben ser enteros positivos
- Montos deben ser números no negativos con máximo 2 decimales
- Tipos de gasto válidos: doctor, técnico, laudos, gasolina, extra
- Descripción requerida para gastos tipo "extra" (mínimo 3 caracteres)
- Referencias válidas a clínicas y exámenes existentes

## Integridad de Datos

- **Transacciones**: Todas las operaciones de creación/actualización de repases se ejecutan en transacciones
- **Soft Delete**: Los repases se eliminan con soft delete (mantienen histórico)
- **Cascade Delete**: Al eliminar un repase, se eliminan automáticamente sus exámenes y gastos relacionados
- **Foreign Keys**: Restricciones de integridad referencial en base de datos
- **Eager Loading**: Prevención de N+1 queries mediante carga anticipada de relaciones

## Arquitectura

El sistema sigue el patrón MVC de Laravel con una capa adicional de servicios:

- **Controllers**: Manejan requests HTTP y respuestas
- **Services**: Contienen lógica de negocio compleja y cálculos
- **Models**: Representan entidades y relaciones de base de datos
- **FormRequests**: Validan datos de entrada
- **Views**: Presentan información al usuario (Blade templates)

## Preparado para SaaS

El código está estructurado para facilitar la migración a un modelo multi-tenancy:

- Separación clara de responsabilidades (MVC + Services)
- Uso de RESTful controllers
- Validación centralizada en FormRequests
- Comentarios en español para facilitar mantenimiento
- Seguimiento de mejores prácticas de Laravel

## Soporte y Documentación

Para más información sobre las tecnologías utilizadas:

- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Chart.js Documentation](https://www.chartjs.org/docs/latest/)
- [FullCalendar Documentation](https://fullcalendar.io/docs)
- [Alpine.js Documentation](https://alpinejs.dev/start-here)

## Licencia

Este proyecto es de código propietario. Todos los derechos reservados.

## Contribución

Para contribuir al proyecto:

1. Seguir las convenciones de código de Laravel
2. Escribir tests para nuevas funcionalidades
3. Mantener comentarios en español
4. Ejecutar `./vendor/bin/pint` antes de commit
5. Asegurar que todos los tests pasen: `php artisan test`

---

**Desarrollado con Laravel 12 + Tailwind CSS + Chart.js + FullCalendar**
