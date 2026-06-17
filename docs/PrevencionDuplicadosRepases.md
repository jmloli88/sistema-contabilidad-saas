# Prevención de Repases Duplicados

## Problema Reportado

El usuario reportó que ocasionalmente aparecen 2 repases con:
- Misma fecha
- Misma clínica
- Mismos exámenes
- Diferentes totales netos y subtotales

**Contexto:** El usuario utiliza Safari en Mac y iPhone.

## Análisis del Problema

### Causas Potenciales

1. **Doble Clic en Safari:**
   - Safari puede procesar múltiples envíos si el usuario hace doble clic rápidamente
   - La protección JavaScript puede no ser suficiente en algunos casos

2. **Problemas de Red:**
   - Conexión lenta puede hacer que el usuario piense que el formulario no se envió
   - Usuario hace clic nuevamente antes de recibir respuesta

3. **Problemas de Sesión:**
   - Safari tiene comportamiento diferente con cookies y sesiones
   - Puede causar problemas con tokens CSRF en algunos casos

4. **Diferentes Totales:**
   - Si los cálculos se hacen en el cliente y el usuario modifica datos entre envíos
   - Race conditions en cálculos automáticos

## Soluciones Implementadas

### 1. Token Único de Envío (Frontend)

**Archivos modificados:**
- `resources/views/repases/create.blade.php`
- `resources/views/repases/edit.blade.php`

**Implementación:**
```javascript
// Generar token único al cargar el formulario
submissionToken: '',

init() {
    this.submissionToken = this.generateUniqueToken();
    // ...
}

generateUniqueToken() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
}
```

**Campo oculto en formulario:**
```html
<input type="hidden" name="_submission_token" x-model="submissionToken">
```

### 2. Middleware de Prevención de Duplicados (Backend)

**Archivo creado:**
- `app/Http/Middleware/PreventDuplicateSubmissions.php`

**Funcionalidad:**
- Valida el token único de envío
- Usa caché para marcar tokens como usados
- Previene envíos duplicados en ventana de 5 minutos
- Compatible con formularios antiguos (sin token)

**Cómo funciona:**
1. Usuario carga formulario → Se genera token único
2. Usuario envía formulario → Token se envía al servidor
3. Servidor verifica si token ya fue usado
4. Si es nuevo → Procesa y marca como usado
5. Si ya fue usado → Rechaza con mensaje de error

### 3. Validación de Duplicados en Controlador

**Archivo modificado:**
- `app/Http/Controllers/RepaseController.php`

**Implementación:**
```php
// Verificar si existe un repase muy similar creado recientemente (últimos 30 segundos)
$posibleDuplicado = Repase::where('clinica_id', $request->clinica_id)
    ->where('fecha', $request->fecha)
    ->where('created_at', '>=', now()->subSeconds(30))
    ->first();

if ($posibleDuplicado) {
    // Redirigir al repase existente en lugar de crear duplicado
    return redirect()
        ->route('repases.show', $posibleDuplicado)
        ->with('warning', 'Ya existe un repase para esta clínica y fecha...');
}
```

**Ventajas:**
- Última línea de defensa contra duplicados
- Detecta intentos de duplicación en ventana de 30 segundos
- Redirige al repase existente en lugar de crear duplicado
- Registra intentos en logs para análisis

### 4. Protección JavaScript Mejorada

**Ya implementado en ambos formularios:**
- Variable `isSubmitting` para controlar estado
- Validación en `validateForm()` para prevenir múltiples envíos
- Botón deshabilitado con spinner de carga
- Mensaje de consola para debugging

### 5. Comando de Detección de Duplicados

**Archivo creado:**
- `app/Console/Commands/DetectarRepasesDuplicados.php`

**Uso:**
```bash
# Detectar duplicados
php artisan repases:detectar-repases-duplicados

# Ver detalles completos
php artisan repases:detectar-repases-duplicados --show-details
```

**Funcionalidad:**
- Agrupa repases por fecha y clínica
- Identifica grupos con múltiples repases
- Analiza tiempo entre creaciones
- Compara exámenes para determinar si son idénticos
- Proporciona recomendaciones de acción

## Configuración Necesaria

### 1. Registrar Middleware (Opcional)

Si desea aplicar el middleware globalmente, edite `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\PreventDuplicateSubmissions::class);
})
```

O aplicarlo solo a rutas específicas en `routes/web.php`:

```php
Route::middleware(['auth', 'prevent.duplicate.submissions'])->group(function () {
    Route::post('/repases', [RepaseController::class, 'store'])->name('repases.store');
    Route::put('/repases/{repase}', [RepaseController::class, 'update'])->name('repases.update');
});
```

### 2. Configurar Caché

Asegúrese de que el sistema de caché esté configurado correctamente en `.env`:

```env
CACHE_DRIVER=redis  # o file, database, memcached
```

Para desarrollo local, `file` es suficiente. Para producción, se recomienda `redis`.

### 3. Configuración de Sesiones para Safari

En `.env`, asegúrese de tener:

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true  # Si usa HTTPS
SESSION_SAME_SITE=lax
```

## Capas de Protección

El sistema ahora tiene **4 capas de protección** contra duplicados:

1. **Capa 1 - JavaScript (Frontend):**
   - Variable `isSubmitting`
   - Botón deshabilitado
   - Validación antes de envío

2. **Capa 2 - Token Único (Frontend + Backend):**
   - Token generado en cliente
   - Validado en servidor vía middleware
   - Marcado como usado en caché

3. **Capa 3 - Validación de Tiempo (Backend):**
   - Verifica repases similares en últimos 30 segundos
   - Redirige al existente si encuentra duplicado

4. **Capa 4 - Transacción de Base de Datos:**
   - Todo el proceso de creación en transacción
   - Rollback automático si hay error

## Testing

### Probar Prevención de Duplicados

1. **Test Manual - Doble Clic:**
   ```
   1. Abrir formulario de crear repase
   2. Llenar datos
   3. Hacer doble clic rápido en "Guardar Repase"
   4. Verificar que solo se crea un repase
   5. Verificar mensaje en consola del navegador
   ```

2. **Test Manual - Envío Rápido:**
   ```
   1. Abrir formulario de crear repase
   2. Llenar datos y guardar
   3. Inmediatamente usar botón "Atrás" del navegador
   4. Intentar guardar nuevamente
   5. Verificar que se muestra mensaje de error o redirige al existente
   ```

3. **Test en Safari:**
   ```
   1. Probar en Safari Mac
   2. Probar en Safari iPhone
   3. Verificar comportamiento con conexión lenta (throttling)
   ```

### Detectar Duplicados Existentes

```bash
# Ver resumen
php artisan repases:detectar-repases-duplicados

# Ver detalles completos
php artisan repases:detectar-repases-duplicados --show-details
```

## Manejo de Duplicados Existentes

Si el comando detecta duplicados existentes:

### Caso 1: Ambos Pendientes
```bash
# Eliminar el duplicado más reciente
php artisan tinker
>>> $repase = Repase::find(ID_DUPLICADO);
>>> $repase->delete();
```

### Caso 2: Uno Pagado, Uno Pendiente
- Mantener el pagado
- Eliminar el pendiente
- Verificar con el usuario si es necesario

### Caso 3: Ambos Pagados
- **NO ELIMINAR**
- Contactar al usuario para confirmar
- Puede ser pago legítimo duplicado

## Logs y Monitoreo

### Logs de Intentos de Duplicación

Los intentos de crear duplicados se registran en `storage/logs/laravel.log`:

```
[warning] Intento de crear repase duplicado detectado
{
    "user_id": 1,
    "clinica_id": 5,
    "fecha": "2026-04-15",
    "repase_existente_id": 123
}
```

### Monitorear Duplicados

Ejecutar periódicamente:
```bash
php artisan repases:detectar-repases-duplicados
```

O configurar en cron para alertas automáticas.

## Consideraciones Especiales para Safari

### Problemas Conocidos de Safari

1. **Cookies de Terceros:**
   - Safari bloquea cookies de terceros por defecto
   - Puede afectar sesiones en subdominios

2. **Intelligent Tracking Prevention (ITP):**
   - Puede limpiar cookies después de 7 días
   - Afecta sesiones de larga duración

3. **Comportamiento de Caché:**
   - Safari cachea agresivamente
   - Puede mostrar formularios con datos antiguos

### Soluciones Aplicadas

1. **Token Único por Formulario:**
   - Se genera nuevo token cada vez que se carga el formulario
   - No depende de cookies o sesiones

2. **Validación en Servidor:**
   - No confía solo en JavaScript
   - Múltiples capas de validación

3. **Mensajes Claros:**
   - Usuario recibe feedback inmediato
   - Spinner de carga visible

## Archivos Modificados/Creados

### Modificados
1. `resources/views/repases/create.blade.php`
   - Agregado token único
   - Mejorada protección JavaScript

2. `resources/views/repases/edit.blade.php`
   - Agregado token único
   - Mejorada protección JavaScript

3. `app/Http/Controllers/RepaseController.php`
   - Agregada validación de duplicados en método `store()`

### Creados
1. `app/Http/Middleware/PreventDuplicateSubmissions.php`
   - Middleware para validar tokens únicos

2. `app/Console/Commands/DetectarRepasesDuplicados.php`
   - Comando para detectar duplicados existentes

3. `docs/PrevencionDuplicadosRepases.md`
   - Esta documentación

## Próximos Pasos

1. **Registrar el middleware** en la aplicación
2. **Ejecutar comando de detección** para identificar duplicados existentes
3. **Limpiar duplicados** encontrados según las recomendaciones
4. **Monitorear logs** para verificar que no hay más intentos de duplicación
5. **Probar en Safari** (Mac e iPhone) para confirmar que funciona correctamente

## Soporte

Si continúan apareciendo duplicados después de implementar estas soluciones:

1. Revisar logs en `storage/logs/laravel.log`
2. Ejecutar comando de detección para analizar patrones
3. Verificar configuración de caché y sesiones
4. Considerar agregar índice único en base de datos (con precaución)

## Notas Técnicas

### Por qué No Usar Índice Único en BD

No se recomienda agregar un índice único en `(clinica_id, fecha)` porque:
- Puede haber múltiples repases legítimos para la misma clínica y fecha
- El usuario podría necesitar corregir un repase y crear uno nuevo
- La validación por tiempo (30 segundos) es más flexible

### Ventana de Tiempo de 30 Segundos

Se eligió 30 segundos porque:
- Es suficiente para capturar dobles clics
- Es suficiente para capturar reenvíos por conexión lenta
- No es tan largo como para bloquear repases legítimos
- Permite al usuario corregir errores rápidamente

### Token vs CSRF

El token único es diferente del token CSRF:
- **CSRF:** Protege contra ataques de sitios cruzados
- **Token Único:** Protege contra envíos duplicados del mismo usuario
- Ambos son necesarios y complementarios
