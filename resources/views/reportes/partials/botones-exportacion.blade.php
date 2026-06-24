{{--
    Componente reutilizable de botones de exportación para reportes
    
    Parámetros:
    - $tipo: Tipo de reporte (rentabilidad-clinica, rentabilidad-examen, productividad, comparativo)
    - $filtros: Array con valores actuales de filtros para pasar a los endpoints de exportación
--}}

<div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0 transition-all duration-300 hover:shadow-2xl">
    <div class="p-6 sm:p-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-2.5">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Exportar Reporte</h3>
            </div>
            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-3 py-1 rounded-full">Descarga tus datos</span>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4">
            {{-- Botón Exportar a Excel --}}
            <form method="POST" action="{{ route('reportes.export.excel') }}" class="w-full sm:flex-1">
                @csrf
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                @foreach($filtros as $key => $value)
                    @if($value !== null && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                
                <button type="submit" class="w-full text-white bg-gradient-to-r from-emerald-400 to-teal-600 hover:from-emerald-500 hover:to-teal-700 focus:ring-4 focus:ring-green-300 font-semibold rounded-xl text-sm px-6 py-3.5 min-h-[44px] focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path>
                    </svg>
                    Exportar a Excel
                </button>
            </form>

            {{-- Botón Exportar a PDF --}}
            <form method="POST" action="{{ route('reportes.export.pdf') }}" class="w-full sm:flex-1">
                @csrf
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                @foreach($filtros as $key => $value)
                    @if($value !== null && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                
                <button type="submit" class="w-full text-white bg-gradient-to-r from-rose-400 to-red-600 hover:from-rose-500 hover:to-red-700 focus:ring-4 focus:ring-red-300 font-semibold rounded-xl text-sm px-6 py-3.5 min-h-[44px] focus:outline-none transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd"></path>
                    </svg>
                    Exportar a PDF
                </button>
            </form>
        </div>
    </div>
</div>
