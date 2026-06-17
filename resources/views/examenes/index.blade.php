<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión de Precios de Exámenes
        </h2>
    </x-slot>

    <div class="py-12 min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Vista de Tabla (Desktop) -->
            <div class="hidden md:block bg-white/80 backdrop-blur-sm border border-gray-200 rounded-lg shadow-xl hover:shadow-2xl transition-shadow duration-300 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-bold">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Examen
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right font-bold">
                                    <div class="flex items-center justify-end gap-2">
                                        <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                        </svg>
                                        Precio Sin Nota
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-right font-bold">
                                    <div class="flex items-center justify-end gap-2">
                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                        Precio Con Nota
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-4 text-left font-bold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($examenes as $examen)
                                <tr class="bg-white/50 border-b border-gray-200 hover:bg-white/80 transition-colors duration-200">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $examen->nombre }}
                                        @if(($examen->overrides_count ?? 0) > 0)
                                            <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                {{ $examen->overrides_count }} {{ $examen->overrides_count === 1 ? 'clínica' : 'clínicas' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                        R${{ number_format($examen->precio_sin_nota, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-blue-900">
                                        R${{ number_format($examen->precio_con_nota, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 justify-start">
                                            <a href="{{ route('examenes.edit', $examen) }}" 
                                               class="inline-flex items-center justify-center w-9 h-9 text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 hover:scale-110 transition-all duration-200"
                                               title="Editar Precios">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vista de Tarjetas (Mobile) -->
            <div class="md:hidden space-y-4 px-4">
                @foreach($examenes as $examen)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-4">
                            <!-- Header de la tarjeta -->
                            <div class="mb-3">
                                <h3 class="text-base font-semibold text-gray-900">
                                    {{ $examen->nombre }}
                                    @if(($examen->overrides_count ?? 0) > 0)
                                        <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            {{ $examen->overrides_count }} {{ $examen->overrides_count === 1 ? 'clínica' : 'clínicas' }}
                                        </span>
                                    @endif
                                </h3>
                            </div>

                            <!-- Detalles de precios -->
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <span class="text-xs text-gray-500 block">Precio Sin Nota</span>
                                        <span class="text-lg font-bold text-gray-900">R${{ number_format($examen->precio_sin_nota, 2) }}</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                    <div>
                                        <span class="text-xs text-blue-600 block">Precio Con Nota</span>
                                        <span class="text-lg font-bold text-blue-900">R${{ number_format($examen->precio_con_nota, 2) }}</span>
                                    </div>
                                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Acciones -->
                            <div class="pt-3 border-t border-gray-200">
                                <a href="{{ route('examenes.edit', $examen) }}" class="block text-center text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-4 py-2 focus:outline-none">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                    </svg>
                                    Editar Precios
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
