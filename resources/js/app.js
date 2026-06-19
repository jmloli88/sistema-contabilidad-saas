import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;

Alpine.plugin(collapse);

Alpine.start();

// Importar script del calendario
import './calendario.js';

// Importar script de modales de gráficos
import './chart-modal.js';

// Importar script de exportación de imagen de repase
import './repase-image-export.js';
