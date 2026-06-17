# Guía de Optimización para Producción

Este documento describe las mejores prácticas y comandos de optimización para desplegar el Sistema de Contabilidad Médica en un entorno de producción.

## Tabla de Contenidos

1. [Comandos de Optimización](#comandos-de-optimización)
2. [Configuración de Entorno](#configuración-de-entorno)
3. [Optimización de Base de Datos](#optimización-de-base-de-datos)
4. [Optimización de Assets](#optimización-de-assets)
5. [Configuración de Queue (Futuro)](#configuración-de-queue-futuro)
6. [Monitoreo y Logs](#monitoreo-y-logs)
7. [Checklist de Despliegue](#checklist-de-despliegue)

---

## Comandos de Optimización

Laravel proporciona varios comandos para optimizar el rendimiento en producción mediante el cacheo de configuraciones, rutas y vistas.

### 1. Cachear Configuración

```bash
php artisan config:cache
```

**¿Qué hace?**
- Combina todos los archivos de configuración en un solo archivo cacheado
- Mejora significativamente el tiempo de carga de la aplicación
- Los cambios en archivos `.env` o `config/*.php` no se reflejarán hasta limpiar el caché

**Cuándo usar:**
- Después de cada despliegue en producción
- Después de cambiar variables de entorno
- Después de modificar archivos de configuración

**Limpiar caché de configuración:**
```bash
php artisan config:clear
```

### 2. Cachear Rutas

```bash
php artisan route:cache
```

**¿Qué hace?**
- Serializa todas las rutas registradas en un archivo cacheado
- Reduce drásticamente el tiempo de registro de rutas
- **IMPORTANTE**: No funciona con closures en rutas (todas las rutas deben usar controladores)

**Cuándo usar:**
- Después de cada despliegue en producción
- Después de modificar archivos de rutas (`routes/web.php`, `routes/api.php`)

**Limpiar caché de rutas:**
```bash
php artisan route:clear
```

**Verificar rutas cacheadas:**
```bash
php artisan route:list
```

### 3. Cachear Vistas

```bash
php artisan view:cache
```

**¿Qué hace?**
- Precompila todas las plantillas Blade
- Elimina el tiempo de compilación en la primera carga
- Las vistas se compilan bajo demanda si no están cacheadas

**Cuándo usar:**
- Después de cada despliegue en producción
- Después de modificar archivos Blade

**Limpiar caché de vistas:**
```bash
php artisan view:clear
```

### 4. Optimización Completa

Ejecutar todos los comandos de optimización de una vez:

```bash
php artisan optimize
```

Este comando ejecuta automáticamente:
- `config:cache`
- `route:cache`
- `view:cache`

### 5. Limpiar Todas las Cachés

Para desarrollo o cuando necesites limpiar todas las optimizaciones:

```bash
php artisan optimize:clear
```

Este comando ejecuta:
- `config:clear`
- `route:clear`
- `view:clear`
- `cache:clear`
- `event:clear`

---

## Configuración de Entorno

### Variables de Entorno para Producción

Asegúrate de configurar correctamente el archivo `.env` en producción:

```env
# Entorno
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Seguridad
APP_KEY=base64:TU_CLAVE_GENERADA_AQUI

# Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=contabilidad_medica_prod
DB_USERNAME=usuario_prod
DB_PASSWORD=contraseña_segura

# Caché
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis (recomendado para producción)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (configurar según proveedor)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME="${APP_NAME}"

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Configuraciones Importantes

#### 1. Deshabilitar Debug

```env
APP_DEBUG=false
```

**Crítico**: Nunca dejar `APP_DEBUG=true` en producción, ya que expone información sensible.

#### 2. Generar APP_KEY Segura

```bash
php artisan key:generate
```

#### 3. Configurar HTTPS

```env
APP_URL=https://tu-dominio.com
```

Asegúrate de que tu servidor web esté configurado para forzar HTTPS.

---

## Optimización de Base de Datos

### 1. Índices

El sistema ya incluye índices en las migraciones para optimizar consultas:

- `clinicas`: índice en `nombre`
- `examenes`: índice en `nombre`
- `repases`: índices en `clinica_id`, `fecha`, `estado`, `deleted_at`
- `repase_examenes`: índices en `repase_id`, `examen_id`
- `gastos`: índices en `repase_id`, `tipo`

### 2. Eager Loading

El sistema utiliza eager loading para prevenir N+1 queries:

```php
// En RepaseController
$repases = Repase::with(['clinica', 'repaseExamenes.examen', 'gastos'])
    ->paginate(15);
```

### 3. Optimización de Consultas

Para monitorear queries en desarrollo:

```bash
# Instalar Laravel Debugbar (solo desarrollo)
composer require barryvdh/laravel-debugbar --dev
```

### 4. Configuración de MySQL

Recomendaciones para `my.cnf` o `my.ini`:

```ini
[mysqld]
# Aumentar pool de conexiones
max_connections = 200

# Optimizar buffer pool (ajustar según RAM disponible)
innodb_buffer_pool_size = 1G

# Optimizar logs
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2

# Query cache (MySQL 5.7 y anteriores)
query_cache_type = 1
query_cache_size = 64M
```

---

## Optimización de Assets

### 1. Compilar Assets para Producción

```bash
npm run build
```

Este comando:
- Minifica JavaScript y CSS
- Optimiza imágenes
- Genera hashes de archivos para cache busting
- Elimina código no utilizado (tree shaking)

### 2. Configuración de Vite

El archivo `vite.config.js` ya está configurado para producción:

```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
});
```

### 3. CDN para Librerías (Opcional)

Para reducir el tamaño del bundle, considera usar CDN para librerías grandes:

```html
<!-- En lugar de importar en JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
```

### 4. Compresión Gzip/Brotli

Configurar en el servidor web (Nginx ejemplo):

```nginx
# Gzip
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

# Brotli (si está disponible)
brotli on;
brotli_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;
```

---

## Configuración de Queue (Futuro)

Aunque el sistema actual no implementa colas, aquí está la configuración recomendada para cuando se necesite procesar tareas en segundo plano.

### ¿Cuándo Usar Queues?

Considera implementar queues para:
- Envío de emails (notificaciones de repases pagados)
- Generación de reportes PDF pesados
- Exportación de datos a Excel
- Procesamiento de cálculos complejos
- Sincronización con sistemas externos

### Configuración Básica

#### 1. Configurar Driver de Queue

En `.env`:

```env
QUEUE_CONNECTION=redis
```

Opciones de drivers:
- `sync`: Ejecuta inmediatamente (desarrollo)
- `database`: Usa tabla de base de datos
- `redis`: Recomendado para producción (requiere Redis)
- `sqs`: Amazon SQS (para AWS)

#### 2. Crear Tabla de Jobs (si usas database)

```bash
php artisan queue:table
php artisan migrate
```

#### 3. Crear un Job de Ejemplo

```bash
php artisan make:job ProcessRepaseNotification
```

```php
<?php

namespace App\Jobs;

use App\Models\Repase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRepaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Repase $repase
    ) {}

    public function handle(): void
    {
        // Lógica para enviar notificación
        // Mail::to($user)->send(new RepasePagadoMail($this->repase));
    }
}
```

#### 4. Despachar Jobs

```php
// En el controller
ProcessRepaseNotification::dispatch($repase);
```

#### 5. Ejecutar Queue Worker

```bash
# Modo desarrollo
php artisan queue:work

# Modo producción (con supervisor)
php artisan queue:work --tries=3 --timeout=60
```

### Configuración de Supervisor

Crear archivo `/etc/supervisor/conf.d/contabilidad-medica-worker.conf`:

```ini
[program:contabilidad-medica-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/al/proyecto/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/worker.log
stopwaitsecs=3600
```

Comandos de supervisor:

```bash
# Recargar configuración
sudo supervisorctl reread
sudo supervisorctl update

# Iniciar workers
sudo supervisorctl start contabilidad-medica-worker:*

# Ver estado
sudo supervisorctl status

# Reiniciar workers (después de despliegue)
sudo supervisorctl restart contabilidad-medica-worker:*
```

### Horizon (Alternativa Avanzada)

Para una interfaz visual de monitoreo de queues:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

Acceder a: `https://tu-dominio.com/horizon`

---

## Monitoreo y Logs

### 1. Configuración de Logs

El sistema está configurado para loguear en `storage/logs/laravel.log`.

En producción, considera usar un servicio de logging:

```env
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 2. Rotación de Logs

Configurar logrotate en `/etc/logrotate.d/contabilidad-medica`:

```
/ruta/al/proyecto/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 3. Monitoreo de Errores

Considera integrar servicios como:
- [Sentry](https://sentry.io) - Tracking de errores
- [Bugsnag](https://www.bugsnag.com) - Monitoreo de errores
- [New Relic](https://newrelic.com) - APM completo

Instalación de Sentry:

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=tu-dsn-aqui
```

### 4. Monitoreo de Performance

```bash
# Instalar Laravel Telescope (solo desarrollo)
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

---

## Checklist de Despliegue

### Pre-Despliegue

- [ ] Ejecutar todos los tests: `php artisan test`
- [ ] Verificar que `APP_DEBUG=false` en `.env`
- [ ] Verificar que `APP_ENV=production` en `.env`
- [ ] Generar `APP_KEY` si es nuevo servidor
- [ ] Configurar credenciales de base de datos
- [ ] Configurar credenciales de email
- [ ] Backup de base de datos actual (si aplica)

### Despliegue

- [ ] Subir código al servidor
- [ ] Ejecutar `composer install --optimize-autoloader --no-dev`
- [ ] Ejecutar `npm install && npm run build`
- [ ] Ejecutar migraciones: `php artisan migrate --force`
- [ ] Ejecutar seeders si es necesario: `php artisan db:seed --force`

### Post-Despliegue

- [ ] Ejecutar optimizaciones:
  ```bash
  php artisan optimize
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] Verificar permisos de directorios:
  ```bash
  chmod -R 755 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache
  ```
- [ ] Reiniciar servicios:
  ```bash
  sudo systemctl restart php8.2-fpm
  sudo systemctl restart nginx
  # Si usas queue workers:
  sudo supervisorctl restart contabilidad-medica-worker:*
  ```
- [ ] Verificar que la aplicación funciona correctamente
- [ ] Monitorear logs por errores: `tail -f storage/logs/laravel.log`

### Verificación

- [ ] Probar login
- [ ] Crear un repase de prueba
- [ ] Verificar dashboard
- [ ] Verificar calendario
- [ ] Verificar que los cálculos son correctos
- [ ] Probar filtros
- [ ] Verificar que los emails se envían (si aplica)

---

## Comandos Rápidos de Referencia

```bash
# Optimización completa
php artisan optimize

# Limpiar todas las cachés
php artisan optimize:clear

# Cachear configuración
php artisan config:cache

# Cachear rutas
php artisan route:cache

# Cachear vistas
php artisan view:cache

# Compilar assets
npm run build

# Ejecutar migraciones en producción
php artisan migrate --force

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Limpiar logs antiguos
php artisan log:clear

# Verificar estado de la aplicación
php artisan about
```

---

## Recursos Adicionales

- [Laravel Deployment Documentation](https://laravel.com/docs/12.x/deployment)
- [Laravel Optimization Guide](https://laravel.com/docs/12.x/deployment#optimization)
- [Laravel Queue Documentation](https://laravel.com/docs/12.x/queues)
- [Laravel Horizon Documentation](https://laravel.com/docs/12.x/horizon)

---

**Última actualización**: Diciembre 2024
