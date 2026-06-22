<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Calendario de Agendas') }}
            </h2>
            <div class="flex flex-wrap gap-2 w-full sm:w-auto" x-data="googleCalendarSync">
                <template x-if="connected">
                    <form method="POST" action="{{ route('google-calendar.sync') }}" class="inline" x-data="{ syncing: false }" @submit="syncing = true">
                        @csrf
                        <button type="submit" :disabled="syncing"
                                class="flex-1 sm:flex-none bg-indigo-500 hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-wait text-white font-bold py-2 px-4 rounded-xl text-sm inline-flex items-center gap-1"
                                title="Sincronizar agendas con Google Calendar">
                            <span class="material-symbols-outlined text-base" x-text="syncing ? 'hourglass_top' : 'sync'"></span>
                            <span x-text="syncing ? 'Sincronizando...' : 'Sincronizar'"></span>
                        </button>
                    </form>
                </template>
                <button onclick="openExportModal()" class="flex-1 sm:flex-none bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-xl text-sm">
                    Exportar
                </button>
                <button onclick="openCreateModal()" class="flex-1 sm:flex-none bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl text-sm">
                    Nueva Agenda
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100">
                <div class="p-3 sm:p-6">
                    <!-- Filtro de clínica -->
                    <div class="mb-4">
                        <label for="filtro-clinica" class="block text-sm font-medium text-gray-700 mb-2">
                            Filtrar por clínica:
                        </label>
                        <select id="filtro-clinica" class="mt-1 block w-full sm:w-64 rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm">
                            <option value="">Todas las clínicas</option>
                            @foreach($clinicas as $clinica)
                                <option value="{{ $clinica->id }}">{{ $clinica->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Calendario -->
                    <div id="calendario" class="agenda-calendar relative"></div>

                    <!-- Custom tooltip (replaces native title) -->
                    <div id="agenda-tooltip" class="hidden absolute z-[9999] pointer-events-none transition-opacity duration-150 ease-out opacity-0"
                         style="left: 0; top: 0;">
                        <div class="bg-gray-900 text-white text-xs rounded-xl shadow-2xl shadow-gray-900/20 ring-1 ring-white/10">
                            <div class="px-3.5 py-2.5 space-y-1.5">
                                <div class="flex items-center gap-2 text-sm font-semibold text-indigo-300" id="tt-clinica"></div>
                                <div class="flex items-center gap-2" id="tt-horario"><span class="text-gray-400 w-4 text-center">🕐</span><span></span></div>
                                <div class="flex items-center gap-2" id="tt-doctor"><span class="text-gray-400 w-4 text-center">👨‍⚕️</span><span></span></div>
                                <div class="flex items-center gap-2" id="tt-repeticion"><span class="text-gray-400 w-4 text-center">🔁</span><span></span></div>
                                <div class="flex items-center gap-2" id="tt-sync"><span class="text-gray-400 w-4 text-center"></span><span></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Estilos para hacer el calendario más compacto y responsive */
        .agenda-calendar {
            font-size: 0.875rem;
        }

        .agenda-calendar .fc-toolbar {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .agenda-calendar .fc-toolbar-title {
            font-size: 1.125rem;
        }

        .agenda-calendar .fc-button {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .agenda-calendar .fc-daygrid-day-number {
            font-size: 0.875rem;
            padding: 0.25rem;
        }

        .agenda-calendar .fc-event {
            font-size: 0.75rem;
            padding: 2px 4px;
            margin-bottom: 2px;
            border: none;
        }

        .agenda-calendar .fc-daygrid-event {
            white-space: normal;
        }

        .agenda-calendar .fc-event-main {
            padding: 2px;
        }

        /* Estilos para badges de agenda en móvil */
        .agenda-badge {
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            line-height: 1.2;
        }

        .agenda-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .agenda-badge:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        /* Responsive para móviles */
        @media (max-width: 640px) {
            .agenda-calendar {
                font-size: 0.75rem;
            }

            .agenda-calendar .fc-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .agenda-calendar .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
                margin-bottom: 0.5rem;
            }

            .agenda-calendar .fc-toolbar-title {
                font-size: 1rem;
                text-align: center;
            }

            .agenda-calendar .fc-button {
                padding: 0.375rem 0.5rem;
                font-size: 0.75rem;
            }

            .agenda-calendar .fc-button-group {
                display: flex;
            }

            .agenda-calendar .fc-daygrid-day-number {
                font-size: 0.75rem;
                padding: 0.125rem;
            }

            .agenda-calendar .fc-col-header-cell {
                font-size: 0.75rem;
                padding: 0.25rem 0;
            }

            .agenda-calendar .fc-event {
                font-size: 0.625rem;
                padding: 0;
                margin-bottom: 1px;
                background: transparent !important;
            }

            .agenda-calendar .fc-event-main {
                padding: 0.5px 1px;
            }

            .agenda-calendar .fc-daygrid-day-frame {
                min-height: 65px;
            }

            .agenda-calendar .fc-daygrid-event-harness {
                margin: 1px 0.5px;
            }

            .agenda-calendar .fc-daygrid-day-events {
                margin-top: 1px;
            }

            /* Ajustar badges en móvil */
            .agenda-badge {
                font-size: 8px !important;
                padding: 1.5px 6px !important;
                border-radius: 10px !important;
            }

            /* Ocultar vista de semana y día en móviles */
            .agenda-calendar .fc-timeGridWeek-button,
            .agenda-calendar .fc-timeGridDay-button {
                display: none;
            }
        }

        /* Tablets */
        @media (min-width: 641px) and (max-width: 1024px) {
            .agenda-calendar .fc-daygrid-day-frame {
                min-height: 80px;
            }
        }

        /* Desktop */
        @media (min-width: 1025px) {
            .agenda-calendar .fc-daygrid-day-frame {
                min-height: 100px;
            }
        }
    </style>

    <!-- Modal Crear/Editar Agenda -->
    <div id="agendaModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-4 sm:top-20 mx-auto p-4 sm:p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-base sm:text-lg font-medium leading-6 text-gray-900 mb-4">Nueva Agenda</h3>
                <form id="agendaForm">
                    <input type="hidden" id="agendaId">
                    
                    <div class="mb-3 sm:mb-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Clínica *</label>
                        <select id="clinica_id" required class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm">
                            <option value="">Seleccione una clínica</option>
                            @foreach($clinicas as $clinica)
                                <option value="{{ $clinica->id }}">{{ $clinica->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 sm:mb-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Fecha *</label>
                        <input type="date" id="fecha" required class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm">
                    </div>

                    <div class="mb-3 sm:mb-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Doctor *</label>
                        <input type="text" id="doctor" required class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm" placeholder="Nombre del doctor">
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:gap-4 mb-3 sm:mb-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Hora Inicio *</label>
                            <input type="time" id="hora_inicio" required class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Hora Fin *</label>
                            <input type="time" id="hora_fin" required class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm">
                        </div>
                    </div>

                    <div id="tipoRepeticionContainer" class="mb-3 sm:mb-4">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Tipo de Agenda *</label>
                        <select id="tipo_repeticion" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm">
                            <option value="unica">Única</option>
                            <option value="repetitiva">Repetitiva</option>
                        </select>
                    </div>

                    <div id="diasRepeticionContainer" class="mb-3 sm:mb-4 hidden">
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Frecuencia de Repetición *</label>
                        <select id="frecuencia_repeticion" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 text-sm mb-3">
                            <option value="7">Semanal (cada 7 días)</option>
                            <option value="14">Quincenal (cada 14 días)</option>
                            <option value="mensual">Mensual (mismo día de la semana)</option>
                            <option value="personalizado">Personalizado</option>
                        </select>
                        
                        <div id="diasPersonalizadosContainer" class="hidden">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Repetir cada (días) *</label>
                            <input type="number" id="dias_repeticion" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-indigo-500 text-sm" placeholder="Ej: 21 para cada 3 semanas">
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-2">Las agendas se crearán hasta diciembre del año actual</p>
                    </div>

                    <div id="aplicarTodasContainer" class="mb-3 sm:mb-4 hidden">
                        <label class="flex items-center">
                            <input type="checkbox" id="aplicar_a_todas" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-xs sm:text-sm text-gray-700">Aplicar cambios a todas las agendas del grupo</span>
                        </label>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-2 mt-4 sm:mt-6">
                        <button type="button" id="deleteBtn" onclick="openDeleteModalFromEdit()" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 hidden text-sm">
                            Eliminar
                        </button>
                        <div class="flex gap-2 sm:ml-auto">
                            <button type="button" onclick="closeModal()" class="flex-1 sm:flex-none px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 text-sm">
                                Cancelar
                            </button>
                            <button type="submit" class="flex-1 sm:flex-none px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm">
                                Guardar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-4 sm:top-20 mx-auto p-4 sm:p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
            <div class="mt-3">
                <h3 class="text-base sm:text-lg font-medium leading-6 text-gray-900 mb-4">Eliminar Agenda</h3>
                <p class="text-xs sm:text-sm text-gray-500 mb-4">¿Está seguro que desea eliminar esta agenda?</p>
                
                <div id="eliminarTodasContainer" class="mb-3 sm:mb-4 hidden">
                    <label class="flex items-center">
                        <input type="checkbox" id="eliminar_todas" class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-500 focus:ring-red-500">
                        <span class="ml-2 text-xs sm:text-sm text-gray-700">Eliminar todas las agendas del grupo</span>
                    </label>
                </div>

                <div class="flex gap-2 mt-4 sm:mt-6">
                    <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 text-sm">
                        Cancelar
                    </button>
                    <button type="button" onclick="confirmDelete()" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 text-sm">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Exportar -->
    <div id="exportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-4 sm:top-20 mx-auto p-4 sm:p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
            <div class="mt-3">
                <h3 class="text-base sm:text-lg font-medium leading-6 text-gray-900 mb-4">Exportar Calendario</h3>
                
                <div class="mb-3 sm:mb-4">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Seleccione el mes a exportar:</label>
                    <input type="month" id="export_month" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                </div>

                <div class="flex gap-2 mt-4 sm:mt-6">
                    <button type="button" onclick="closeExportModal()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 text-sm">
                        Cancelar
                    </button>
                    <button type="button" id="exportBtn" onclick="exportCalendar()" class="flex-1 px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm">
                        Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Google Calendar sync button — only shown when connected
        document.addEventListener('alpine:init', () => {
            Alpine.data('googleCalendarSync', () => ({
                connected: false,

                async init() {
                    try {
                        const res = await fetch('{{ route('google-calendar.status') }}', {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.connected = data.connected;
                        }
                    } catch (e) { /* offline or unreachable — hide button */ }
                },
            }));
        });
    </script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/es.global.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        let calendar;
        let currentAgendaId = null;

        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendario');
            
            // Detectar si es móvil
            const isMobile = window.innerWidth < 640;
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: isMobile ? 'dayGridMonth' : 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                contentHeight: 'auto',
                aspectRatio: isMobile ? 1 : 1.8,
                fixedWeekCount: false,
                showNonCurrentDates: false,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: isMobile ? 'dayGridMonth' : 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día'
                },
                dayMaxEvents: isMobile ? 2 : 3,
                moreLinkText: function(num) {
                    return '+' + num + ' más';
                },
                dateClick: function(info) {
                    // Click en un día vacío — abre el modal con la fecha pre-llenada
                    openCreateModal(info.dateStr);
                },
                events: function(info, successCallback, failureCallback) {
                    const clinicaId = document.getElementById('filtro-clinica').value;
                    const url = new URL('{{ route("agendas.events") }}');
                    url.searchParams.append('start', info.startStr);
                    url.searchParams.append('end', info.endStr);
                    if (clinicaId) {
                        url.searchParams.append('clinica_id', clinicaId);
                    }

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            // Agrupar eventos por día y asignar índices
                            const eventsByDay = {};
                            data.forEach(event => {
                                const dayKey = event.start.split('T')[0];
                                if (!eventsByDay[dayKey]) {
                                    eventsByDay[dayKey] = [];
                                }
                                eventsByDay[dayKey].push(event);
                            });
                            
                            // Asignar índice a cada evento del día
                            Object.keys(eventsByDay).forEach(day => {
                                eventsByDay[day].forEach((event, index) => {
                                    event.dayIndex = index + 1;
                                });
                            });
                            
                            successCallback(data);
                        })
                        .catch(error => failureCallback(error));
                },
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    openEditModal(info.event);
                },
                eventContent: function(arg) {
                    const props = arg.event.extendedProps;
                    const isMobile = window.innerWidth < 640;
                    
                    // Google Calendar sync indicator
                    const syncIcon = props.google_synced
                        ? '<span class="inline-flex items-center" title="Sincronizado con Google Calendar">🔄</span>'
                        : '';
                    
                    // Repetición badge
                    const repBadge = props.repetitiva
                        ? '<span class="inline-flex items-center text-[10px] opacity-70" title="Agenda repetitiva">🔁</span>'
                        : '';
                    
                    if (isMobile) {
                        // En móvil, mostrar badge numerado con color de clínica
                        const eventIndex = arg.event.extendedProps.dayIndex || 1;
                        return {
                            html: `<span class="agenda-badge" style="
                                background-color: ${props.color}20;
                                color: ${props.color};
                                border: 1px solid ${props.color}40;
                                padding: 2px 8px;
                                border-radius: 12px;
                                font-size: 9px;
                                font-weight: 600;
                                display: inline-block;
                                white-space: nowrap;
                            ">Agenda ${eventIndex} ${syncIcon}${repBadge}</span>`
                        };
                    } else {
                        // Desktop: mostrar línea por línea con sync + repetición
                        const titleHtml = arg.event.title.replace(/\n/g, '<br>');
                        return {
                            html: `<div class="p-1 text-xs leading-tight">
                                ${titleHtml}
                                <div class="flex items-center gap-1 mt-0.5">${syncIcon}${repBadge}</div>
                            </div>`
                        };
                    }
                },
                eventDidMount: function(info) {
                    info.el.style.cursor = 'pointer';
                },
                eventMouseEnter: function(info) {
                    const props = info.event.extendedProps;
                    const tooltip = document.getElementById('agenda-tooltip');
                    if (!tooltip) return;

                    // Fill tooltip content
                    document.getElementById('tt-clinica').innerHTML = `<span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color:${props.color}"></span> ${props.clinica}`;
                    document.getElementById('tt-horario').lastChild.textContent = `${props.hora_inicio} → ${props.hora_fin}`;
                    document.getElementById('tt-doctor').lastChild.textContent = `Dr. ${props.doctor}`;
                    
                    const repEl = document.getElementById('tt-repeticion');
                    repEl.querySelector('span').textContent = props.repetitiva ? '🔄' : '📌';
                    repEl.lastChild.textContent = props.repetitiva ? 'Agenda repetitiva' : 'Agenda única';
                    
                    const syncEl = document.getElementById('tt-sync');
                    syncEl.querySelector('span').textContent = props.google_synced ? '✅' : '⏳';
                    syncEl.lastChild.textContent = props.google_synced ? 'Sincronizado con Google Calendar' : 'Pendiente de sincronizar';

                    // Position tooltip near the event element
                    const rect = info.el.getBoundingClientRect();
                    const calendarRect = document.getElementById('calendario').getBoundingClientRect();
                    
                    let left = rect.right - calendarRect.left + 10;
                    let top = rect.top - calendarRect.top;
                    
                    // Flip left if it would overflow
                    if (left + 220 > calendarRect.width) {
                        left = rect.left - calendarRect.left - 230;
                    }
                    // Clamp top
                    top = Math.max(0, Math.min(top, calendarRect.height - 150));

                    tooltip.style.left = left + 'px';
                    tooltip.style.top = top + 'px';
                    tooltip.classList.remove('hidden');
                    requestAnimationFrame(() => tooltip.classList.add('opacity-100'));
                },
                eventMouseLeave: function() {
                    const tooltip = document.getElementById('agenda-tooltip');
                    if (!tooltip) return;
                    tooltip.classList.remove('opacity-100');
                    setTimeout(() => tooltip.classList.add('hidden'), 150);
                },
                windowResize: function(view) {
                    // Ajustar cuando cambia el tamaño de la ventana
                    const newIsMobile = window.innerWidth < 640;
                    if (newIsMobile) {
                        calendar.setOption('aspectRatio', 1);
                        calendar.setOption('dayMaxEvents', 2);
                    } else {
                        calendar.setOption('aspectRatio', 1.8);
                        calendar.setOption('dayMaxEvents', 3);
                    }
                }
            });

            calendar.render();

            // Filtro de clínica
            document.getElementById('filtro-clinica').addEventListener('change', function() {
                calendar.refetchEvents();
            });

            // Tipo de repetición
            document.getElementById('tipo_repeticion').addEventListener('change', function() {
                const diasContainer = document.getElementById('diasRepeticionContainer');
                if (this.value === 'repetitiva') {
                    diasContainer.classList.remove('hidden');
                    document.getElementById('frecuencia_repeticion').required = true;
                } else {
                    diasContainer.classList.add('hidden');
                    document.getElementById('frecuencia_repeticion').required = false;
                    document.getElementById('dias_repeticion').required = false;
                }
            });

            // Frecuencia de repetición
            document.getElementById('frecuencia_repeticion').addEventListener('change', function() {
                const diasPersonalizadosContainer = document.getElementById('diasPersonalizadosContainer');
                if (this.value === 'personalizado') {
                    diasPersonalizadosContainer.classList.remove('hidden');
                    document.getElementById('dias_repeticion').required = true;
                } else {
                    diasPersonalizadosContainer.classList.add('hidden');
                    document.getElementById('dias_repeticion').required = false;
                }
            });

            // Submit form
            document.getElementById('agendaForm').addEventListener('submit', function(e) {
                e.preventDefault();
                saveAgenda();
            });
        });

        function openCreateModal(dateStr = null) {
            document.getElementById('modalTitle').textContent = 'Nueva Agenda';
            document.getElementById('agendaForm').reset();
            document.getElementById('agendaId').value = '';
            document.getElementById('tipoRepeticionContainer').classList.remove('hidden');
            document.getElementById('diasRepeticionContainer').classList.add('hidden');
            document.getElementById('aplicarTodasContainer').classList.add('hidden');
            document.getElementById('deleteBtn').classList.add('hidden');
            
            // Pre-fill date if a day was clicked
            if (dateStr) {
                document.getElementById('fecha').value = dateStr;
            }
            
            document.getElementById('agendaModal').classList.remove('hidden');
        }

        function openEditModal(event) {
            const props = event.extendedProps;
            
            // Función para formatear hora a H:i (sin segundos)
            const formatTime = (time) => {
                if (!time) return '';
                // Si ya está en formato H:i, devolverlo tal cual
                if (time.match(/^\d{1,2}:\d{2}$/)) return time;
                // Si tiene segundos, quitarlos
                return time.substring(0, 5);
            };
            
            document.getElementById('modalTitle').textContent = 'Editar Agenda';
            document.getElementById('agendaId').value = event.id;
            document.getElementById('clinica_id').value = props.clinica_id;
            document.getElementById('fecha').value = event.startStr;
            document.getElementById('hora_inicio').value = formatTime(props.hora_inicio);
            document.getElementById('hora_fin').value = formatTime(props.hora_fin);
            document.getElementById('doctor').value = props.doctor;
            
            document.getElementById('tipoRepeticionContainer').classList.add('hidden');
            document.getElementById('diasRepeticionContainer').classList.add('hidden');
            document.getElementById('deleteBtn').classList.remove('hidden');
            
            if (props.grupo_repeticion) {
                document.getElementById('aplicarTodasContainer').classList.remove('hidden');
                document.getElementById('eliminarTodasContainer').classList.remove('hidden');
            } else {
                document.getElementById('aplicarTodasContainer').classList.add('hidden');
                document.getElementById('eliminarTodasContainer').classList.add('hidden');
            }
            
            document.getElementById('agendaModal').classList.remove('hidden');
            
            // Guardar para eliminar
            currentAgendaId = event.id;
        }

        function openDeleteModalFromEdit() {
            openDeleteModal(currentAgendaId);
        }

        function closeModal() {
            document.getElementById('agendaModal').classList.add('hidden');
        }

        function saveAgenda() {
            const agendaId = document.getElementById('agendaId').value;
            const isEdit = agendaId !== '';
            
            const data = {
                clinica_id: document.getElementById('clinica_id').value,
                fecha: document.getElementById('fecha').value,
                hora_inicio: document.getElementById('hora_inicio').value,
                hora_fin: document.getElementById('hora_fin').value,
                doctor: document.getElementById('doctor').value,
            };

            if (isEdit) {
                data.aplicar_a_todas = document.getElementById('aplicar_a_todas').checked;
            } else {
                data.tipo_repeticion = document.getElementById('tipo_repeticion').value;
                if (data.tipo_repeticion === 'repetitiva') {
                    const frecuencia = document.getElementById('frecuencia_repeticion').value;
                    if (frecuencia === 'personalizado') {
                        data.dias_repeticion = document.getElementById('dias_repeticion').value;
                    } else if (frecuencia === 'mensual') {
                        data.frecuencia_mensual = true;
                    } else {
                        data.dias_repeticion = frecuencia; // 7 o 14
                    }
                }
            }

            const url = isEdit 
                ? `/agendas/${agendaId}` 
                : '{{ route("agendas.store") }}';
            
            const method = isEdit ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Server response:', text);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    let mensaje = data.message;
                    
                    if (data.conflictos && data.conflictos.length > 0) {
                        mensaje += '\n\nConflictos encontrados:\n';
                        data.conflictos.slice(0, 5).forEach(conflicto => {
                            mensaje += '- ' + conflicto.mensaje + '\n';
                        });
                        if (data.conflictos.length > 5) {
                            mensaje += `... y ${data.conflictos.length - 5} más`;
                        }
                    }
                    
                    alert(mensaje);
                    closeModal();
                    calendar.refetchEvents();
                } else {
                    alert(data.message || 'Error al guardar la agenda');
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                alert('Error al guardar la agenda: ' + error.message);
            });
        }

        function openDeleteModal(agendaId) {
            currentAgendaId = agendaId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function confirmDelete() {
            if (!currentAgendaId) return;

            const eliminarTodas = document.getElementById('eliminar_todas').checked;

            fetch(`/agendas/${currentAgendaId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    eliminar_todas: eliminarTodas
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeDeleteModal();
                    closeModal();
                    calendar.refetchEvents();
                } else {
                    alert(data.message || 'Error al eliminar la agenda');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar la agenda');
            });
        }

        // Agregar botón de eliminar en el modal de edición
        // Ya no es necesario este código

        function openExportModal() {
            // Establecer el mes actual por defecto
            const now = new Date();
            const currentMonth = now.toISOString().slice(0, 7);
            document.getElementById('export_month').value = currentMonth;
            document.getElementById('exportModal').classList.remove('hidden');
        }

        function closeExportModal() {
            document.getElementById('exportModal').classList.add('hidden');
        }

        function exportCalendar() {
            const monthInput = document.getElementById('export_month').value;
            if (!monthInput) {
                alert('Por favor seleccione un mes');
                return;
            }

            // Parsear el mes seleccionado
            const [year, month] = monthInput.split('-');
            const selectedDate = new Date(year, month - 1, 1);

            // Mostrar mensaje de procesamiento
            const exportBtn = document.getElementById('exportBtn');
            const originalText = exportBtn.textContent;
            exportBtn.textContent = 'Generando...';
            exportBtn.disabled = true;

            // Crear un contenedor temporal para la exportación
            const exportContainer = document.createElement('div');
            exportContainer.style.cssText = `
                position: fixed;
                left: -9999px;
                top: 0;
                width: 1200px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 40px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            `;

            // Crear el contenido del export
            const monthName = selectedDate.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
            const monthNameCapitalized = monthName.charAt(0).toUpperCase() + monthName.slice(1);
            
            exportContainer.innerHTML = `
                <div style="background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                    <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #667eea;">
                        <h1 style="margin: 0; font-size: 36px; color: #1a202c; font-weight: 700; letter-spacing: -0.5px;">
                            Calendario de Agendas
                        </h1>
                        <p style="margin: 10px 0 0 0; font-size: 24px; color: #667eea; font-weight: 600;">
                            ${monthNameCapitalized}
                        </p>
                    </div>
                    <div id="calendar-export-content" style="font-size: 14px;"></div>
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0; text-align: center; color: #718096; font-size: 14px;">
                        <p style="margin: 0;">Generado el ${new Date().toLocaleDateString('es-ES', { 
                            day: '2-digit', 
                            month: '2-digit', 
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</p>
                    </div>
                </div>
            `;

            document.body.appendChild(exportContainer);

            // Crear un nuevo calendario temporal para exportación
            const exportCalendarEl = document.getElementById('calendar-export-content');
            
            const clinicaId = document.getElementById('filtro-clinica').value;
            
            const exportCalendar = new FullCalendar.Calendar(exportCalendarEl, {
                initialView: 'dayGridMonth',
                initialDate: selectedDate,
                locale: 'es',
                height: 'auto',
                fixedWeekCount: false,
                showNonCurrentDates: false,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes'
                },
                events: function(info, successCallback, failureCallback) {
                    const url = new URL('{{ route("agendas.events") }}');
                    url.searchParams.append('start', info.startStr);
                    url.searchParams.append('end', info.endStr);
                    if (clinicaId) {
                        url.searchParams.append('clinica_id', clinicaId);
                    }

                    fetch(url)
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(error => failureCallback(error));
                },
                eventContent: function(arg) {
                    // Siempre mostrar en formato desktop (texto completo)
                    return {
                        html: '<div style="padding: 2px 4px; font-size: 11px; line-height: 1.3;">' + arg.event.title.replace(/\n/g, '<br>') + '</div>'
                    };
                },
                eventDidMount: function(info) {
                    // Asegurar que los eventos tengan el estilo correcto
                    info.el.style.marginBottom = '2px';
                }
            });

            exportCalendar.render();

            // Esperar a que el calendario se renderice completamente
            setTimeout(() => {
                html2canvas(exportContainer, {
                    scale: 2,
                    backgroundColor: null,
                    logging: false,
                    useCORS: true,
                    windowWidth: 1200,
                    windowHeight: exportContainer.scrollHeight
                }).then(canvas => {
                    // Convertir a imagen JPG
                    canvas.toBlob(function(blob) {
                        // Crear enlace de descarga
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.download = `calendario-agendas-${monthName.replace(/\s+/g, '-')}.jpg`;
                        link.href = url;
                        link.click();
                        
                        // Limpiar
                        URL.revokeObjectURL(url);
                        exportCalendar.destroy();
                        document.body.removeChild(exportContainer);
                        
                        // Restaurar botón
                        exportBtn.textContent = originalText;
                        exportBtn.disabled = false;
                        
                        // Cerrar modal
                        closeExportModal();
                        
                        alert('Calendario exportado exitosamente');
                    }, 'image/jpeg', 0.95);
                }).catch(error => {
                    console.error('Error al exportar:', error);
                    alert('Error al exportar el calendario');
                    exportCalendar.destroy();
                    document.body.removeChild(exportContainer);
                    exportBtn.textContent = originalText;
                    exportBtn.disabled = false;
                });
            }, 1500);
        }
    </script>
    @endpush
</x-app-layout>
