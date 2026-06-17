/**
 * Módulo principal de reportes avanzados
 * 
 * Este archivo importa y exporta todos los módulos de reportes
 * para facilitar su uso en las vistas Blade.
 */

import {
    crearGraficoBarras,
    crearGraficoPie,
    crearGraficoLineas,
    crearGraficoBarrasHorizontales
} from './charts.js';

import filtrosReporte from './filtros.js';

import {
    manejarExportacion,
    inicializarExportacion,
    mostrarMensajeExito,
    mostrarMensajeError
} from './exportacion.js';

// Exportar todo para uso como módulo
export {
    // Charts
    crearGraficoBarras,
    crearGraficoPie,
    crearGraficoLineas,
    crearGraficoBarrasHorizontales,
    
    // Filtros
    filtrosReporte,
    
    // Exportación
    manejarExportacion,
    inicializarExportacion,
    mostrarMensajeExito,
    mostrarMensajeError
};

// Hacer disponible globalmente si no se usan módulos ES6
if (typeof window !== 'undefined') {
    window.Reportes = {
        Charts: {
            crearGraficoBarras,
            crearGraficoPie,
            crearGraficoLineas,
            crearGraficoBarrasHorizontales
        },
        filtrosReporte,
        Exportacion: {
            manejarExportacion,
            inicializarExportacion,
            mostrarMensajeExito,
            mostrarMensajeError
        }
    };
}
