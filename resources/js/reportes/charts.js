/**
 * Módulo de Chart.js para visualizaciones de reportes avanzados
 * 
 * Este módulo proporciona funciones para crear y configurar gráficos
 * usando Chart.js para los diferentes tipos de reportes.
 */

/**
 * Esquema de colores consistente para todos los gráficos
 */
const COLORES = {
    positivo: 'rgba(34, 197, 94, 0.7)',      // verde
    positivoBorde: 'rgba(34, 197, 94, 1)',
    negativo: 'rgba(239, 68, 68, 0.7)',      // rojo
    negativoBorde: 'rgba(239, 68, 68, 1)',
    primario: 'rgba(59, 130, 246, 0.7)',     // azul
    primarioBorde: 'rgba(59, 130, 246, 1)',
    secundario: 'rgba(168, 85, 247, 0.7)',   // púrpura
    secundarioBorde: 'rgba(168, 85, 247, 1)',
    paleta: [
        'rgba(59, 130, 246, 0.7)',   // azul
        'rgba(168, 85, 247, 0.7)',   // púrpura
        'rgba(236, 72, 153, 0.7)',   // rosa
        'rgba(251, 146, 60, 0.7)',   // naranja
        'rgba(34, 197, 94, 0.7)',    // verde
        'rgba(14, 165, 233, 0.7)',   // cyan
        'rgba(234, 179, 8, 0.7)',    // amarillo
        'rgba(239, 68, 68, 0.7)',    // rojo
    ],
    paletaBorde: [
        'rgba(59, 130, 246, 1)',
        'rgba(168, 85, 247, 1)',
        'rgba(236, 72, 153, 1)',
        'rgba(251, 146, 60, 1)',
        'rgba(34, 197, 94, 1)',
        'rgba(14, 165, 233, 1)',
        'rgba(234, 179, 8, 1)',
        'rgba(239, 68, 68, 1)',
    ]
};

/**
 * Formatea un número como moneda con separadores de miles
 * @param {number} valor - El valor a formatear
 * @returns {string} Valor formateado con símbolo de moneda
 */
function formatearMoneda(valor) {
    return '$' + valor.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Crea un gráfico de barras para rentabilidad por clínica
 * 
 * @param {string} elementId - ID del elemento canvas
 * @param {Array} datos - Array de objetos con {nombre_clinica, ganancia_neta}
 * @param {Object} opciones - Opciones adicionales para el gráfico
 * @returns {Chart} Instancia del gráfico creado
 */
export function crearGraficoBarras(elementId, datos, opciones = {}) {
    const ctx = document.getElementById(elementId);
    if (!ctx) {
        console.error(`Elemento con ID "${elementId}" no encontrado`);
        return null;
    }

    const labels = datos.map(item => item.nombre_clinica);
    const valores = datos.map(item => parseFloat(item.ganancia_neta));

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: opciones.label || 'Ganancia Neta ($)',
                data: valores,
                backgroundColor: valores.map(valor => 
                    valor >= 0 ? COLORES.positivo : COLORES.negativo
                ),
                borderColor: valores.map(valor => 
                    valor >= 0 ? COLORES.positivoBorde : COLORES.negativoBorde
                ),
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return (context.dataset.label || '') + ': ' + formatearMoneda(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return formatearMoneda(value);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12,
                            weight: 'bold'
                        },
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            },
            ...opciones
        }
    });
}

/**
 * Crea un gráfico de pie para distribución de ingresos por examen
 * 
 * @param {string} elementId - ID del elemento canvas
 * @param {Array} datos - Array de objetos con {nombre_examen, total_ingresos}
 * @param {Object} opciones - Opciones adicionales para el gráfico
 * @returns {Chart} Instancia del gráfico creado
 */
export function crearGraficoPie(elementId, datos, opciones = {}) {
    const ctx = document.getElementById(elementId);
    if (!ctx) {
        console.error(`Elemento con ID "${elementId}" no encontrado`);
        return null;
    }

    const labels = datos.map(item => item.nombre_examen);
    const valores = datos.map(item => parseFloat(item.total_ingresos));

    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                label: opciones.label || 'Ingresos ($)',
                data: valores,
                backgroundColor: COLORES.paleta,
                borderColor: COLORES.paletaBorde,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        font: {
                            size: 12
                        },
                        padding: 15,
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
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${formatearMoneda(value)} (${percentage}%)`;
                        }
                    }
                }
            },
            ...opciones
        }
    });
}

/**
 * Crea un gráfico de líneas para tendencias comparativas
 * 
 * @param {string} elementId - ID del elemento canvas
 * @param {Object} datos - Objeto con {labels, datasets: [{label, data}]}
 * @param {Object} opciones - Opciones adicionales para el gráfico
 * @returns {Chart} Instancia del gráfico creado
 */
export function crearGraficoLineas(elementId, datos, opciones = {}) {
    const ctx = document.getElementById(elementId);
    if (!ctx) {
        console.error(`Elemento con ID "${elementId}" no encontrado`);
        return null;
    }

    const datasets = datos.datasets.map((dataset, index) => ({
        label: dataset.label,
        data: dataset.data,
        borderColor: index === 0 ? COLORES.primarioBorde : COLORES.secundarioBorde,
        backgroundColor: index === 0 ? COLORES.primario : COLORES.secundario,
        borderWidth: 3,
        fill: false,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 7,
        pointBackgroundColor: index === 0 ? COLORES.primarioBorde : COLORES.secundarioBorde,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
    }));

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: datos.labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatearMoneda(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return formatearMoneda(value);
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                }
            },
            ...opciones
        }
    });
}

/**
 * Crea un gráfico de barras horizontales para productividad
 * 
 * @param {string} elementId - ID del elemento canvas
 * @param {Array} datos - Array de objetos con {nombre, cantidad_total}
 * @param {Object} opciones - Opciones adicionales para el gráfico
 * @returns {Chart} Instancia del gráfico creado
 */
export function crearGraficoBarrasHorizontales(elementId, datos, opciones = {}) {
    const ctx = document.getElementById(elementId);
    if (!ctx) {
        console.error(`Elemento con ID "${elementId}" no encontrado`);
        return null;
    }

    const labels = datos.map(item => item.nombre || item.nombre_examen || item.nombre_clinica);
    const valores = datos.map(item => parseInt(item.cantidad_total));

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: opciones.label || 'Cantidad de Exámenes',
                data: valores,
                backgroundColor: COLORES.paleta,
                borderColor: COLORES.paletaBorde,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.x.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                },
                y: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                }
            },
            ...opciones
        }
    });
}

// Exportar funciones para uso global si no se usan módulos ES6
if (typeof window !== 'undefined') {
    window.ReporteCharts = {
        crearGraficoBarras,
        crearGraficoPie,
        crearGraficoLineas,
        crearGraficoBarrasHorizontales
    };
}
