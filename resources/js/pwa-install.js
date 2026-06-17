// PWA Install Prompt Handler
let deferredPrompt;
const installButton = document.getElementById('pwa-install-btn');

window.addEventListener('beforeinstallprompt', (e) => {
    // Prevenir que el navegador muestre el prompt automáticamente
    e.preventDefault();
    // Guardar el evento para usarlo después
    deferredPrompt = e;
    // Mostrar el botón de instalación
    if (installButton) {
        installButton.style.display = 'block';
    }
});

if (installButton) {
    installButton.addEventListener('click', async () => {
        if (!deferredPrompt) {
            return;
        }
        // Mostrar el prompt de instalación
        deferredPrompt.prompt();
        // Esperar la respuesta del usuario
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`Usuario ${outcome === 'accepted' ? 'aceptó' : 'rechazó'} la instalación`);
        // Limpiar el prompt
        deferredPrompt = null;
        // Ocultar el botón
        installButton.style.display = 'none';
    });
}

// Detectar si la app ya está instalada
window.addEventListener('appinstalled', () => {
    console.log('PWA instalada con éxito');
    if (installButton) {
        installButton.style.display = 'none';
    }
});

// Verificar si ya está en modo standalone (instalada)
if (window.matchMedia('(display-mode: standalone)').matches) {
    console.log('La aplicación está corriendo en modo standalone');
    if (installButton) {
        installButton.style.display = 'none';
    }
}
