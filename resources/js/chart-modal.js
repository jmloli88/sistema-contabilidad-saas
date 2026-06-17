// Importar Chart.js
import Chart from 'chart.js/auto';

// Funcionalidad para modales de gráficos
let modalChart = null;
let currentChartId = null;

window.openChartModal = function(chartId, chartTitle) {
    const modal = document.getElementById('chartModal');
    const modalTitle = document.getElementById('modalChartTitle');
    const modalCanvas = document.getElementById('modalChartCanvas');
    
    // Establecer título
    modalTitle.textContent = chartTitle;
    
    // Guardar el ID del gráfico actual
    currentChartId = chartId;
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    
    // Pequeño delay para permitir que el modal se renderice
    setTimeout(() => {
        renderModalChart(chartId);
    }, 100);
}

window.closeChartModal = function() {
    const modal = document.getElementById('chartModal');
    
    // Destruir el gráfico del modal si existe
    if (modalChart) {
        modalChart.destroy();
        modalChart = null;
    }
    
    // Ocultar modal
    modal.classList.add('hidden');
    modal.style.display = 'none';
    
    currentChartId = null;
}

function renderModalChart(chartId) {
    // Obtener el gráfico original
    const originalCanvas = document.getElementById(chartId);
    if (!originalCanvas) {
        console.error('Canvas no encontrado:', chartId);
        return;
    }
    
    const originalChart = Chart.getChart(originalCanvas);
    if (!originalChart) {
        console.error('Gráfico no encontrado para canvas:', chartId);
        return;
    }
    
    // Destruir gráfico anterior del modal si existe
    if (modalChart) {
        modalChart.destroy();
    }
    
    // Obtener el canvas del modal
    const modalCanvas = document.getElementById('modalChartCanvas');
    const ctx = modalCanvas.getContext('2d');
    
    // Crear una copia profunda de la configuración
    const originalConfig = originalChart.config;
    
    // Clonar los datos
    const clonedData = {
        labels: [...(originalConfig.data.labels || [])],
        datasets: originalConfig.data.datasets.map(dataset => ({
            ...dataset,
            data: [...dataset.data]
        }))
    };
    
    // Clonar las opciones básicas
    const clonedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: originalConfig.options?.indexAxis || 'x', // Preservar orientación (horizontal/vertical)
        plugins: {
            legend: {
                display: originalConfig.options?.plugins?.legend?.display !== false,
                position: originalConfig.options?.plugins?.legend?.position || 'top',
                labels: {
                    font: {
                        size: 14
                    },
                    color: originalConfig.options?.plugins?.legend?.labels?.color || '#374151'
                }
            },
            tooltip: originalConfig.options?.plugins?.tooltip || {}
        }
    };
    
    // Copiar escalas si existen
    if (originalConfig.options?.scales) {
        clonedOptions.scales = {};
        Object.keys(originalConfig.options.scales).forEach(scaleKey => {
            const originalScale = originalConfig.options.scales[scaleKey];
            clonedOptions.scales[scaleKey] = {
                ...originalScale,
                ticks: {
                    ...originalScale.ticks,
                    font: {
                        size: 12
                    }
                }
            };
            
            // Preservar beginAtZero si existe
            if (originalScale.beginAtZero !== undefined) {
                clonedOptions.scales[scaleKey].beginAtZero = originalScale.beginAtZero;
            }
        });
    }
    
    // Crear la configuración del nuevo gráfico
    const newConfig = {
        type: originalConfig.type,
        data: clonedData,
        options: clonedOptions
    };
    
    // Crear nuevo gráfico en el modal
    try {
        modalChart = new Chart(ctx, newConfig);
    } catch (error) {
        console.error('Error al crear gráfico en modal:', error);
    }
}

// Cerrar modal al hacer clic fuera del contenido
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('chartModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                window.closeChartModal();
            }
        });
    }
    
    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            window.closeChartModal();
        }
    });
});
