import html2canvas from 'html2canvas';

/**
 * Genera y descarga una imagen del repase con información básica y exámenes
 */
export function generarImagenRepase() {
    const button = document.getElementById('btn-generar-imagen');
    const content = document.getElementById('repase-image-content');
    
    if (!content) {
        console.error('No se encontró el contenido para generar la imagen');
        return;
    }
    
    // Deshabilitar botón y mostrar estado de carga
    if (button) {
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Generando imagen...';
    }
    
    // Hacer visible temporalmente el contenido para captura
    const originalStyle = content.style.cssText;
    content.style.cssText = 'position: fixed; left: 0; top: 0; width: 800px; z-index: 9999; background: white;';
    
    // Configuración para html2canvas
    const options = {
        scale: 2, // Mayor calidad
        backgroundColor: '#ffffff',
        logging: false,
        useCORS: true,
        allowTaint: true,
        width: 800,
        windowWidth: 800
    };
    
    // Pequeño delay para asegurar que el contenido se renderice
    setTimeout(() => {
        // Generar la imagen
        html2canvas(content, options).then(canvas => {
            // Restaurar estilo original inmediatamente después de capturar
            content.style.cssText = originalStyle;
            
            // Convertir canvas a blob
            canvas.toBlob(blob => {
                // Crear URL temporal
                const url = URL.createObjectURL(blob);
                
                // Crear elemento de descarga
                const link = document.createElement('a');
                const fecha = new Date().toISOString().split('T')[0];
                const clinica = content.dataset.clinica || 'repase';
                link.download = `repase_${clinica}_${fecha}.png`;
                link.href = url;
                
                // Simular click para descargar
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Liberar URL temporal
                URL.revokeObjectURL(url);
                
                // Restaurar botón
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>Descargar Imagen';
                }
            }, 'image/png');
        }).catch(error => {
            console.error('Error al generar la imagen:', error);
            alert('Hubo un error al generar la imagen. Por favor, intente nuevamente.');
            
            // Restaurar estilo original en caso de error
            content.style.cssText = originalStyle;
            
            // Restaurar botón en caso de error
            if (button) {
                button.disabled = false;
                button.innerHTML = '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>Descargar Imagen';
            }
        });
    }, 100); // 100ms delay para asegurar renderizado
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('btn-generar-imagen');
    if (button) {
        button.addEventListener('click', generarImagenRepase);
    }
});
