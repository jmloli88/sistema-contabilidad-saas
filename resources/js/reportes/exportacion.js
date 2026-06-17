/**
 * Módulo de exportación con feedback visual
 * 
 * Este módulo proporciona funcionalidad para exportar reportes
 * con indicadores de carga y mensajes de éxito/error.
 */

/**
 * Muestra un indicador de carga
 * @param {string} mensaje - Mensaje a mostrar durante la carga
 * @returns {HTMLElement} Elemento del indicador de carga
 */
function mostrarIndicadorCarga(mensaje = 'Generando archivo...') {
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.id = 'export-loading-overlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;';
    
    // Crear contenedor del spinner
    const container = document.createElement('div');
    container.className = 'bg-white rounded-2xl shadow-2xl p-8 flex flex-col items-center space-y-4';
    container.style.cssText = 'background-color: white; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 2rem; display: flex; flex-direction: column; align-items: center;';
    
    // Crear spinner
    const spinner = document.createElement('div');
    spinner.className = 'animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600';
    spinner.style.cssText = 'width: 4rem; height: 4rem; border-radius: 9999px; border: 4px solid transparent; border-bottom-color: #2563eb; animation: spin 1s linear infinite;';
    
    // Crear texto
    const texto = document.createElement('p');
    texto.className = 'text-lg font-semibold text-gray-700';
    texto.style.cssText = 'font-size: 1.125rem; font-weight: 600; color: #374151; margin-top: 1rem;';
    texto.textContent = mensaje;
    
    // Agregar animación de spin si no existe
    if (!document.getElementById('spin-animation-style')) {
        const style = document.createElement('style');
        style.id = 'spin-animation-style';
        style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }
    
    container.appendChild(spinner);
    container.appendChild(texto);
    overlay.appendChild(container);
    document.body.appendChild(overlay);
    
    return overlay;
}

/**
 * Oculta el indicador de carga
 */
function ocultarIndicadorCarga() {
    const overlay = document.getElementById('export-loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Muestra un mensaje de éxito
 * @param {string} mensaje - Mensaje de éxito a mostrar
 */
function mostrarMensajeExito(mensaje) {
    mostrarNotificacion(mensaje, 'success');
}

/**
 * Muestra un mensaje de error
 * @param {string} mensaje - Mensaje de error a mostrar
 */
function mostrarMensajeError(mensaje) {
    mostrarNotificacion(mensaje, 'error');
}

/**
 * Muestra una notificación temporal
 * @param {string} mensaje - Mensaje a mostrar
 * @param {string} tipo - Tipo de notificación ('success' o 'error')
 */
function mostrarNotificacion(mensaje, tipo = 'success') {
    // Crear notificación
    const notificacion = document.createElement('div');
    notificacion.className = `fixed top-4 right-4 z-50 max-w-md transform transition-all duration-300 ease-in-out`;
    notificacion.style.cssText = 'position: fixed; top: 1rem; right: 1rem; z-index: 9999; max-width: 28rem;';
    
    const bgColor = tipo === 'success' ? '#10b981' : '#ef4444';
    const icon = tipo === 'success' 
        ? '<svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
        : '<svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
    
    notificacion.innerHTML = `
        <div class="rounded-2xl shadow-2xl p-4 flex items-center space-x-4" style="background-color: ${bgColor}; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 1rem; display: flex; align-items: center;">
            <div style="flex-shrink: 0;">
                ${icon}
            </div>
            <p class="text-white font-semibold" style="color: white; font-weight: 600; flex: 1;">${mensaje}</p>
            <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200 transition-colors" style="color: white; flex-shrink: 0;">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notificacion);
    
    // Animar entrada
    setTimeout(() => {
        notificacion.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        notificacion.style.transform = 'translateX(400px)';
        setTimeout(() => {
            notificacion.remove();
        }, 300);
    }, 5000);
}

/**
 * Maneja la exportación de un reporte
 * @param {HTMLFormElement} form - Formulario de exportación
 * @param {string} tipoArchivo - Tipo de archivo ('excel' o 'pdf')
 */
export async function manejarExportacion(form, tipoArchivo) {
    const mensajeCarga = tipoArchivo === 'excel' 
        ? 'Generando archivo Excel...' 
        : 'Generando archivo PDF...';
    
    const mensajeExito = tipoArchivo === 'excel'
        ? 'Archivo Excel generado exitosamente'
        : 'Archivo PDF generado exitosamente';
    
    // Mostrar indicador de carga
    const indicador = mostrarIndicadorCarga(mensajeCarga);
    
    try {
        // Crear FormData del formulario
        const formData = new FormData(form);
        
        // Realizar petición
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al generar el archivo');
        }
        
        // Obtener el blob del archivo
        const blob = await response.blob();
        
        // Obtener el nombre del archivo del header Content-Disposition
        const contentDisposition = response.headers.get('Content-Disposition');
        let filename = `reporte_${tipoArchivo}_${new Date().toISOString().split('T')[0]}.${tipoArchivo === 'excel' ? 'xlsx' : 'pdf'}`;
        
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
            if (filenameMatch) {
                filename = filenameMatch[1];
            }
        }
        
        // Crear enlace de descarga
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        
        // Limpiar
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Ocultar indicador y mostrar éxito
        ocultarIndicadorCarga();
        mostrarMensajeExito(mensajeExito);
        
    } catch (error) {
        console.error('Error al exportar:', error);
        ocultarIndicadorCarga();
        mostrarMensajeError('Error al generar el archivo. Por favor intenta de nuevo.');
    }
}

/**
 * Inicializa los manejadores de exportación en la página
 */
export function inicializarExportacion() {
    // Buscar todos los formularios de exportación
    const formsExcel = document.querySelectorAll('form[action*="export/excel"]');
    const formsPdf = document.querySelectorAll('form[action*="export/pdf"]');
    
    // Agregar manejadores a formularios Excel
    formsExcel.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await manejarExportacion(form, 'excel');
        });
    });
    
    // Agregar manejadores a formularios PDF
    formsPdf.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await manejarExportacion(form, 'pdf');
        });
    });
}

// Inicializar automáticamente cuando el DOM esté listo
if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarExportacion);
    } else {
        inicializarExportacion();
    }
    
    // Exportar funciones globalmente
    window.ReporteExportacion = {
        manejarExportacion,
        inicializarExportacion,
        mostrarMensajeExito,
        mostrarMensajeError
    };
}
