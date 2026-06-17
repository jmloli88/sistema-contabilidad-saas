import Chart from 'chart.js/auto';

/**
 * Script para inicializar y renderizar los gráficos del Dashboard
 * usando Chart.js con estilos modernos de Flowbite
 * 
 * Este script espera que las siguientes variables globales estén definidas
 * en la vista Blade:
 * - ingresosVsGastosData: Datos para el gráfico de barras
 * - totalesPorClinicaData: Datos para el gráfico de pastel
 * - pagadosVsPendientesData: Datos para el gráfico de dona
 */

// Configuración de colores modernos
const colors = {
    primary: '#3b82f6',      // blue-500
    success: '#10b981',      // green-500
    danger: '#ef4444',       // red-500
    warning: '#f59e0b',      // amber-500
    info: '#06b6d4',         // cyan-500
    purple: '#8b5cf6',       // violet-500
    pink: '#ec4899',         // pink-500
    indigo: '#6366f1',       // indigo-500
};

// Paleta de colores para gráficos de pastel/dona
const chartColors = [
    '#3b82f6', // blue
    '#10b981', // green
    '#f59e0b', // amber
    '#ef4444', // red
    '#8b5cf6', // violet
    '#ec4899', // pink
    '#06b6d4', // cyan
    '#6366f1', // indigo
];

/**
 * Crea un gráfico de barras comparando Ingresos vs Gastos por mes
 * 
 * @param {Object} data - Objeto con labels y datasets
 * @param {Array} data.labels - Array de meses en formato YYYY-MM
 * @param {Array} data.datasets - Array con dos datasets: Ingresos y Gastos
 */
function createIngresosVsGastosChart(data) {
    const ctx = document.getElementById('ingresosVsGastosChart');
    
    if (!ctx) {
        console.warn('Canvas element "ingresosVsGastosChart" not found');
        return;
    }
    
    // Personalizar colores de los datasets
    if (data.datasets && data.datasets.length >= 2) {
        data.datasets[0].backgroundColor = colors.success;
        data.datasets[0].borderColor = colors.success;
        data.datasets[0].borderWidth = 2;
        data.datasets[0].borderRadius = 8;
        
        data.datasets[1].backgroundColor = colors.danger;
        data.datasets[1].borderColor = colors.danger;
        data.datasets[1].borderWidth = 2;
        data.datasets[1].borderRadius = 8;
    }
    
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif",
                        }
                    }
                },
                title: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'R$ ' + context.parsed.y.toFixed(2);
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            size: 11,
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f3f4f6',
                    },
                    ticks: {
                        font: {
                            size: 11,
                        },
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Crea un gráfico de pastel mostrando Totales por Clínica
 * 
 * @param {Object} data - Objeto con labels y datasets
 * @param {Array} data.labels - Array de nombres de clínicas
 * @param {Array} data.datasets - Array con un dataset conteniendo los totales
 */
function createTotalesPorClinicaChart(data) {
    const ctx = document.getElementById('totalesPorClinicaChart');
    
    if (!ctx) {
        console.warn('Canvas element "totalesPorClinicaChart" not found');
        return;
    }
    
    // Aplicar colores modernos
    if (data.datasets && data.datasets.length > 0) {
        data.datasets[0].backgroundColor = chartColors;
        data.datasets[0].borderColor = '#ffffff';
        data.datasets[0].borderWidth = 2;
    }
    
    new Chart(ctx, {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 11,
                            family: "'Inter', sans-serif",
                        },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return {
                                        text: `${label} (${percentage}%)`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                title: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'R$ ' + context.parsed.toFixed(2);
                            
                            // Calcular porcentaje
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            label += ' (' + percentage + '%)';
                            
                            return label;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Crea un gráfico de dona comparando Pagados vs Pendientes
 * 
 * @param {Object} data - Objeto con labels y datasets
 * @param {Array} data.labels - Array con estados: ['Pagado', 'Pendiente']
 * @param {Array} data.datasets - Array con un dataset conteniendo los totales
 */
function createPagadosVsPendientesChart(data) {
    const ctx = document.getElementById('pagadosVsPendientesChart');
    
    if (!ctx) {
        console.warn('Canvas element "pagadosVsPendientesChart" not found');
        return;
    }
    
    // Aplicar colores: verde para pagado, amarillo para pendiente
    if (data.datasets && data.datasets.length > 0) {
        data.datasets[0].backgroundColor = [colors.success, colors.warning];
        data.datasets[0].borderColor = '#ffffff';
        data.datasets[0].borderWidth = 3;
    }
    
    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 13,
                            family: "'Inter', sans-serif",
                            weight: '500',
                        },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return {
                                        text: `${label}: ${percentage}%`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                title: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'R$ ' + context.parsed.toFixed(2);
                            
                            // Calcular porcentaje
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            label += ' (' + percentage + '%)';
                            
                            return label;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Inicializa todos los gráficos del dashboard
 * 
 * Esta función se ejecuta cuando el DOM está completamente cargado
 * y verifica que las variables globales con los datos estén disponibles
 */
function initDashboardCharts() {
    // Verificar que las variables globales existan
    if (typeof ingresosVsGastosData !== 'undefined') {
        createIngresosVsGastosChart(ingresosVsGastosData);
    } else {
        console.warn('Variable "ingresosVsGastosData" not found');
    }
    
    if (typeof totalesPorClinicaData !== 'undefined') {
        createTotalesPorClinicaChart(totalesPorClinicaData);
    } else {
        console.warn('Variable "totalesPorClinicaData" not found');
    }
    
    if (typeof pagadosVsPendientesData !== 'undefined') {
        createPagadosVsPendientesChart(pagadosVsPendientesData);
    } else {
        console.warn('Variable "pagadosVsPendientesData" not found');
    }
    
    // Nuevos gráficos
    if (typeof gastosPorCategoriaData !== 'undefined') {
        createGastosPorCategoriaChart(gastosPorCategoriaData);
    } else {
        console.warn('Variable "gastosPorCategoriaData" not found');
    }
    
    if (typeof topExamenesData !== 'undefined') {
        createTopExamenesChart(topExamenesData);
    } else {
        console.warn('Variable "topExamenesData" not found');
    }
    
    if (typeof evolucionIngresosNetosData !== 'undefined') {
        createEvolucionIngresosNetosChart(evolucionIngresosNetosData);
    } else {
        console.warn('Variable "evolucionIngresosNetosData" not found');
    }
    
    if (typeof diasCobroPorClinicaData !== 'undefined') {
        createDiasCobroPorClinicaChart(diasCobroPorClinicaData);
    } else {
        console.warn('Variable "diasCobroPorClinicaData" not found');
    }
    
    if (typeof margenGananciaPorClinicaData !== 'undefined') {
        createMargenGananciaPorClinicaChart(margenGananciaPorClinicaData);
    } else {
        console.warn('Variable "margenGananciaPorClinicaData" not found');
    }
    
    if (typeof topClinicasConsultasData !== 'undefined') {
        createTopClinicasConsultasChart(topClinicasConsultasData);
    } else {
        console.warn('Variable "topClinicasConsultasData" not found');
    }
}

// Exportar funciones para uso externo
export {
    initDashboardCharts,
    createIngresosVsGastosChart,
    createTotalesPorClinicaChart,
    createPagadosVsPendientesChart,
    createGastosPorCategoriaChart,
    createTopExamenesChart,
    createEvolucionIngresosNetosChart,
    createDiasCobroPorClinicaChart,
    createMargenGananciaPorClinicaChart,
    createTopClinicasConsultasChart
};


/**
 * Crea un gráfico de pastel para Desglose de Gastos por Categoría
 * 
 * @param {Object} data - Objeto con labels y datasets
 */
function createGastosPorCategoriaChart(data) {
    const ctx = document.getElementById('gastosPorCategoriaChart');
    
    if (!ctx) {
        console.warn('Canvas element "gastosPorCategoriaChart" not found');
        return;
    }
    
    new Chart(ctx, {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 11,
                            family: "'Inter', sans-serif",
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'R$ ' + context.parsed.toFixed(2);
                            
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            label += ' (' + percentage + '%)';
                            
                            return label;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Crea un gráfico de barras horizontales para Top 5 Exámenes Más Rentables
 * 
 * @param {Object} data - Objeto con labels y datasets
 */
function createTopExamenesChart(data) {
    const ctx = document.getElementById('topExamenesChart');
    
    if (!ctx) {
        console.warn('Canvas element "topExamenesChart" not found');
        return;
    }
    
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return 'R$ ' + context.parsed.x.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: '#f3f4f6',
                    },
                    ticks: {
                        font: {
                            size: 11,
                        },
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(0);
                        }
                    }
                },
                y: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            size: 11,
                        }
                    }
                }
            }
        }
    });
}

/**
 * Crea un gráfico de línea para Evolución de Ingresos Netos
 * 
 * @param {Object} data - Objeto con labels y datasets
 */
function createEvolucionIngresosNetosChart(data) {
    const ctx = document.getElementById('evolucionIngresosNetosChart');
    
    if (!ctx) {
        console.warn('Canvas element "evolucionIngresosNetosChart" not found');
        return;
    }
    
    new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return 'R$ ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            size: 11,
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f3f4f6',
                    },
                    ticks: {
                        font: {
                            size: 11,
                        },
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Crea un gráfico de barras para Días Promedio de Cobro por Clínica
 * 
 * @param {Object} data - Objeto con labels y datasets
 */
function createDiasCobroPorClinicaChart(data) {
    const ctx = document.getElementById('diasCobroPorClinicaChart');
    
    if (!ctx) {
        console.warn('Canvas element "diasCobroPorClinicaChart" not found');
        return;
    }
    
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toFixed(1) + ' días';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            size: 11,
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f3f4f6',
                    },
                    ticks: {
                        font: {
                            size: 11,
                        },
                        callback: function(value) {
                            return value + ' días';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Crea un gráfico de barras para Margen de Ganancia por Clínica
 * 
 * @param {Object} data - Objeto con labels y datasets
 */
function createMargenGananciaPorClinicaChart(data) {
    const ctx = document.getElementById('margenGananciaPorClinicaChart');
    
    if (!ctx) {
        console.warn('Canvas element "margenGananciaPorClinicaChart" not found');
        return;
    }
    
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toFixed(2) + '%';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            size: 11,
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f3f4f6',
                    },
                    ticks: {
                        font: {
                            size: 11,
                        },
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

// Inicializar gráficos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initDashboardCharts();
});

/**
 * Crea un gráfico de barras para Top 5 Clínicas con Mayor Cantidad de Consultas
 * 
 * @param {Object} data - Objeto con labels y datasets
 */
function createTopClinicasConsultasChart(data) {
    const ctx = document.getElementById('topClinicasConsultasChart');
    
    if (!ctx) {
        console.warn('Canvas element "topClinicasConsultasChart" not found');
        return;
    }
    
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' consultas';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            size: 11,
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f3f4f6',
                    },
                    ticks: {
                        font: {
                            size: 11,
                        },
                        callback: function(value) {
                            return value + ' consultas';
                        }
                    }
                }
            }
        }
    });
}
