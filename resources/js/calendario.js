/**
 * Script para el Calendario de Repases
 * 
 * Inicializa FullCalendar con configuración en español,
 * muestra eventos con color coding según estado,
 * y permite filtrar por clínica.
 */

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

// Inicializar calendario solo si el elemento existe en la página
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    if (!calendarEl) {
        return; // No estamos en la página del calendario
    }

    // Obtener el filtro de clínica
    const clinicaFilter = document.getElementById('clinica_filter');

    // Función para obtener la URL de eventos con filtros
    function getEventsUrl() {
        const clinicaId = clinicaFilter ? clinicaFilter.value : '';
        const baseUrl = '/calendario/events';
        
        if (clinicaId) {
            return `${baseUrl}?clinica_id=${clinicaId}`;
        }
        
        return baseUrl;
    }

    // Inicializar FullCalendar
    const calendar = new Calendar(calendarEl, {
        // Plugins necesarios
        plugins: [dayGridPlugin, interactionPlugin],
        
        // Configuración de idioma español
        locale: esLocale,
        
        // Vista inicial: mensual
        initialView: 'dayGridMonth',
        
        // Configuración de encabezado
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth'
        },
        
        // Textos en español
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        
        // Altura del calendario
        height: 'auto',
        
        // Ocultar días de meses adyacentes
        fixedWeekCount: false,
        showNonCurrentDates: false,
        
        // Personalizar el día actual
        dayCellClassNames: function(arg) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const cellDate = new Date(arg.date);
            cellDate.setHours(0, 0, 0, 0);
            
            if (cellDate.getTime() === today.getTime()) {
                return ['fc-day-today-custom'];
            }
            return [];
        },
        
        // Personalizar el contenido del día actual
        dayCellContent: function(arg) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const cellDate = new Date(arg.date);
            cellDate.setHours(0, 0, 0, 0);
            
            if (cellDate.getTime() === today.getTime()) {
                return {
                    html: `<div class="fc-daygrid-day-number" style="
                        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                        color: white;
                        font-weight: bold;
                        border-radius: 50%;
                        width: 32px;
                        height: 32px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 4px auto;
                        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
                    ">${arg.dayNumberText}</div>`
                };
            }
            return { html: arg.dayNumberText };
        },
        
        // Source de eventos apuntando a la ruta /calendario/events
        events: function(info, successCallback, failureCallback) {
            fetch(getEventsUrl())
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al cargar eventos');
                    }
                    return response.json();
                })
                .then(data => {
                    successCallback(data);
                })
                .catch(error => {
                    console.error('Error al cargar eventos del calendario:', error);
                    failureCallback(error);
                });
        },
        
        // Click en evento: redireccionar a detalle del repase
        eventClick: function(info) {
            // Prevenir el comportamiento por defecto
            info.jsEvent.preventDefault();
            
            // Si el evento tiene URL, redireccionar
            if (info.event.url) {
                window.location.href = info.event.url;
            }
        },
        
        // Mostrar tooltip al pasar el mouse sobre un evento
        eventMouseEnter: function(info) {
            const props = info.event.extendedProps;
            
            // Crear tooltip con información del repase
            const tooltip = document.createElement('div');
            tooltip.className = 'calendar-tooltip';
            tooltip.style.cssText = `
                position: absolute;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 8px 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                z-index: 1000;
                font-size: 12px;
                max-width: 250px;
            `;
            
            tooltip.innerHTML = `
                <div style="font-weight: bold; margin-bottom: 4px;">${props.clinica}</div>
                <div style="color: #666;">Estado: <span style="color: ${info.event.backgroundColor}">${props.estado}</span></div>
                <div style="color: #666;">Total Exámenes: $${parseFloat(props.total_examenes).toFixed(2)}</div>
                <div style="color: #666;">Total Consultas: $${parseFloat(props.total_consultas).toFixed(2)}</div>
                <div style="color: #666;">Total Gastos: $${parseFloat(props.total_gastos).toFixed(2)}</div>
                <div style="font-weight: bold; margin-top: 4px; padding-top: 4px; border-top: 1px solid #eee;">
                    Total Neto: $${parseFloat(props.total_neto).toFixed(2)}
                </div>
            `;
            
            document.body.appendChild(tooltip);
            
            // Posicionar tooltip cerca del mouse
            const rect = info.el.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.bottom + 5) + 'px';
            
            // Guardar referencia para poder eliminarlo después
            info.el._tooltip = tooltip;
        },
        
        // Ocultar tooltip al salir del evento
        eventMouseLeave: function(info) {
            if (info.el._tooltip) {
                info.el._tooltip.remove();
                info.el._tooltip = null;
            }
        },
        
        // Configuración adicional
        firstDay: 1, // Lunes como primer día de la semana
        weekNumbers: false,
        navLinks: false,
        editable: false,
        dayMaxEvents: true, // Mostrar "más" cuando hay muchos eventos
    });

    // Renderizar el calendario
    calendar.render();

    // Implementar filtro por clínica que recargue eventos
    if (clinicaFilter) {
        clinicaFilter.addEventListener('change', function() {
            // Recargar eventos del calendario con el nuevo filtro
            calendar.refetchEvents();
        });
    }
});
