/**
 * Componente Alpine.js para manejo de filtros de reportes
 * 
 * Este componente proporciona funcionalidad interactiva para aplicar
 * filtros sin recargar la página usando AJAX.
 */

/**
 * Crea un componente Alpine.js para manejo de filtros
 * 
 * @param {string} routeUrl - URL del endpoint del reporte
 * @param {Object} filtrosIniciales - Valores iniciales de los filtros
 * @returns {Object} Objeto de datos de Alpine.js
 */
export function filtrosReporte(routeUrl, filtrosIniciales = {}) {
    return {
        // Estado de los filtros
        fechaInicio: filtrosIniciales.fecha_inicio || '',
        fechaFin: filtrosIniciales.fecha_fin || '',
        clinicaId: filtrosIniciales.clinica_id || '',
        examenId: filtrosIniciales.examen_id || '',
        
        // Estado de la UI
        cargando: false,
        error: null,
        
        /**
         * Inicializa el componente
         */
        init() {
            // Establecer fecha mínima para fecha_fin cuando cambia fecha_inicio
            this.$watch('fechaInicio', value => {
                const fechaFinInput = document.getElementById('fecha_fin');
                if (fechaFinInput) {
                    fechaFinInput.min = value;
                }
            });
        },
        
        /**
         * Aplica los filtros seleccionados con llamada AJAX
         */
        async aplicarFiltros() {
            // Validar fechas
            if (!this.fechaInicio || !this.fechaFin) {
                this.mostrarError('Por favor selecciona ambas fechas');
                return;
            }
            
            if (this.fechaInicio > this.fechaFin) {
                this.mostrarError('La fecha de inicio debe ser anterior o igual a la fecha de fin');
                return;
            }
            
            this.cargando = true;
            this.error = null;
            
            try {
                // Construir parámetros de consulta
                const params = new URLSearchParams({
                    fecha_inicio: this.fechaInicio,
                    fecha_fin: this.fechaFin,
                });
                
                if (this.clinicaId) {
                    params.append('clinica_id', this.clinicaId);
                }
                
                if (this.examenId) {
                    params.append('examen_id', this.examenId);
                }
                
                // Realizar petición AJAX
                const response = await fetch(`${routeUrl}?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Error al cargar los datos');
                }
                
                const data = await response.json();
                
                // Actualizar la página con los nuevos datos
                this.actualizarContenido(data);
                
            } catch (error) {
                console.error('Error al aplicar filtros:', error);
                this.mostrarError('Error al cargar los datos. Por favor intenta de nuevo.');
            } finally {
                this.cargando = false;
            }
        },
        
        /**
         * Limpia todos los filtros y resetea a valores por defecto
         */
        limpiarFiltros() {
            // Resetear valores
            this.fechaInicio = '';
            this.fechaFin = '';
            this.clinicaId = '';
            this.examenId = '';
            this.error = null;
            
            // Recargar la página sin parámetros
            window.location.href = routeUrl;
        },
        
        /**
         * Actualiza el contenido de la página con los nuevos datos
         * @param {Object} data - Datos recibidos del servidor
         */
        actualizarContenido(data) {
            // Si el servidor devuelve HTML completo, recargar la página
            if (typeof data === 'string' || data.html) {
                window.location.reload();
                return;
            }
            
            // Si el servidor devuelve datos JSON, actualizar dinámicamente
            if (data.datos) {
                this.actualizarTabla(data.datos);
                this.actualizarGrafico(data.datos);
            }
        },
        
        /**
         * Actualiza la tabla con nuevos datos
         * @param {Array} datos - Nuevos datos para la tabla
         */
        actualizarTabla(datos) {
            // Esta función se puede personalizar según la estructura de cada reporte
            // Por ahora, simplemente recargamos la página
            window.location.reload();
        },
        
        /**
         * Actualiza el gráfico con nuevos datos
         * @param {Array} datos - Nuevos datos para el gráfico
         */
        actualizarGrafico(datos) {
            // Esta función se puede personalizar según el tipo de gráfico
            // Por ahora, simplemente recargamos la página
            window.location.reload();
        },
        
        /**
         * Muestra un mensaje de error
         * @param {string} mensaje - Mensaje de error a mostrar
         */
        mostrarError(mensaje) {
            this.error = mensaje;
            
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                this.error = null;
            }, 5000);
        },
        
        /**
         * Obtiene el texto del botón de aplicar filtros
         * @returns {string} Texto del botón
         */
        get textoBotonAplicar() {
            return this.cargando ? 'Cargando...' : 'Aplicar Filtros';
        },
        
        /**
         * Verifica si el formulario es válido
         * @returns {boolean} True si el formulario es válido
         */
        get formularioValido() {
            return this.fechaInicio && this.fechaFin && this.fechaInicio <= this.fechaFin;
        }
    };
}

// Registrar componente Alpine.js globalmente si Alpine está disponible
if (typeof window !== 'undefined' && window.Alpine) {
    window.Alpine.data('filtrosReporte', filtrosReporte);
}

// Exportar para uso como módulo
export default filtrosReporte;
