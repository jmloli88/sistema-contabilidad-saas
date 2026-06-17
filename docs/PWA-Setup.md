# Configuración de PWA (Progressive Web App)

## ¿Qué es una PWA?

Una Progressive Web App (PWA) es una aplicación web que puede instalarse en dispositivos móviles y de escritorio, funcionando como una aplicación nativa con capacidades offline.

## Características Implementadas

✅ **Instalable**: Los usuarios pueden instalar la app en sus dispositivos
✅ **Funciona Offline**: Caché de recursos para funcionamiento sin conexión
✅ **Responsive**: Se adapta a cualquier tamaño de pantalla
✅ **Rápida**: Carga instantánea con Service Workers
✅ **Segura**: Requiere HTTPS en producción

## Archivos Creados

### 1. Manifest (`public/manifest.json`)
Define la configuración de la PWA:
- Nombre de la aplicación
- Iconos en diferentes tamaños
- Colores del tema
- Modo de visualización

### 2. Service Worker (`public/sw.js`)
Maneja el caché y funcionamiento offline:
- Estrategia Network First
- Fallback a caché cuando no hay conexión
- Página offline personalizada

### 3. Página Offline (`public/offline.html`)
Página mostrada cuando no hay conexión a internet

### 4. Componente de Instalación (`resources/views/components/pwa-install-button.blade.php`)
Botón flotante para instalar la PWA

## Pasos para Completar la Configuración

### 1. Generar Iconos

Opción A - Usar el script Python:
```bash
# Instalar Pillow si no lo tienes
pip install Pillow

# Ejecutar el script
python generate-pwa-icons.py
```

Opción B - Usar herramienta online:
1. Ve a https://realfavicongenerator.net/
2. Sube tu logo (mínimo 512x512px)
3. Descarga los iconos generados
4. Colócalos en `public/images/icons/`

### 2. Compilar Assets

```bash
npm run build
```

### 3. Agregar el Botón de Instalación

En tu layout principal (`resources/views/layouts/app.blade.php`), antes del cierre de `</body>`:

```blade
<x-pwa-install-button />
```

### 4. Configurar HTTPS (Producción)

Las PWA requieren HTTPS. Asegúrate de que tu servidor tenga SSL configurado.

Para desarrollo local, puedes usar:
```bash
php artisan serve --host=localhost --port=8000
```

## Personalización

### Cambiar Colores del Tema

Edita `public/manifest.json`:
```json
{
  "theme_color": "#4f46e5",
  "background_color": "#ffffff"
}
```

### Cambiar Nombre de la App

Edita `public/manifest.json`:
```json
{
  "name": "Tu Nombre Completo",
  "short_name": "Nombre Corto"
}
```

### Modificar Estrategia de Caché

Edita `public/sw.js` para cambiar qué recursos se cachean y cómo.

Estrategias disponibles:
- **Network First**: Intenta red primero, luego caché (actual)
- **Cache First**: Intenta caché primero, luego red
- **Network Only**: Solo red, sin caché
- **Cache Only**: Solo caché, sin red

## Probar la PWA

### En Chrome/Edge (Desktop):
1. Abre la aplicación en el navegador
2. Busca el icono de instalación en la barra de direcciones
3. Haz clic en "Instalar"

### En Chrome (Android):
1. Abre la aplicación en Chrome
2. Toca el menú (⋮)
3. Selecciona "Instalar aplicación" o "Agregar a pantalla de inicio"

### En Safari (iOS):
1. Abre la aplicación en Safari
2. Toca el botón de compartir
3. Selecciona "Agregar a pantalla de inicio"

## Verificar Instalación

### Chrome DevTools:
1. Abre DevTools (F12)
2. Ve a la pestaña "Application"
3. Revisa:
   - Manifest
   - Service Workers
   - Cache Storage

### Lighthouse:
1. Abre DevTools (F12)
2. Ve a la pestaña "Lighthouse"
3. Selecciona "Progressive Web App"
4. Haz clic en "Generate report"

## Actualizar la PWA

Cuando hagas cambios:

1. Incrementa la versión del caché en `public/sw.js`:
```javascript
const CACHE_NAME = 'contamed-v2'; // Cambiar versión
```

2. Recompila los assets:
```bash
npm run build
```

3. Los usuarios recibirán la actualización automáticamente

## Desinstalar la PWA

### Desktop:
- Chrome: chrome://apps → clic derecho → "Desinstalar"
- Edge: edge://apps → clic derecho → "Desinstalar"

### Mobile:
- Android: Mantén presionado el icono → "Desinstalar"
- iOS: Mantén presionado el icono → "Eliminar app"

## Recursos Adicionales

- [MDN - Progressive Web Apps](https://developer.mozilla.org/es/docs/Web/Progressive_web_apps)
- [web.dev - PWA](https://web.dev/progressive-web-apps/)
- [PWA Builder](https://www.pwabuilder.com/)

## Solución de Problemas

### La PWA no se puede instalar
- Verifica que estés usando HTTPS (o localhost)
- Revisa que el manifest.json sea válido
- Asegúrate de tener todos los iconos necesarios

### El Service Worker no se registra
- Verifica la consola del navegador para errores
- Asegúrate de que `sw.js` esté en la raíz de `public/`
- Limpia el caché del navegador

### Los cambios no se reflejan
- Incrementa la versión del caché en `sw.js`
- Limpia el caché del navegador
- Desregistra el Service Worker en DevTools

## Soporte

Para más ayuda, consulta la documentación oficial de Laravel y PWA.
