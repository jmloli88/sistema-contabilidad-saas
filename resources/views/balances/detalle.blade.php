<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Detalle del Período {{ ucfirst($periodo) }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">Año {{ $anio }} @if($periodo !== 'anual') - {{ $periodoIndex }}{{ $periodo === 'mensual' ? 'er' : 'º' }} {{ ucfirst($periodo) }} @endif</p>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <nav class="flex mb-8 mx-4 sm:mx-0" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors duration-200"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>Dashboard</a>
                        </li>
                        <li><div class="flex items-center"><svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg><a href="{{ route('balances.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 transition-colors duration-200">Balances</a></div></li>
                        <li><div class="flex items-center"><svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg><span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Detalle {{ ucfirst($periodo) }}</span></div></li>
                    </ol>
                </nav>

                <div class="bg-white/80 backdrop-blur-sm border-0 rounded-2xl shadow-xl mb-8 mx-4 sm:mx-0">
                    <div class="p-4 sm:p-6 md:p-8">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-2.5">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path></svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Repases del Período</h3>
                            </div>
                        </div>

                        <div class="overflow-x-auto -mx-4 sm:mx-0">
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden">
                                    <table class="min-w-full text-sm text-left text-gray-700">
                                        <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-100 to-gray-50">
                                            <tr>
                                                <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold whitespace-nowrap">Fecha</th>
                                                <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold whitespace-nowrap">Clínica</th>
                                                <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Ingresos</th>
                                                <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Gastos</th>
                                                <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right whitespace-nowrap">Neto</th>
                                                <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-center whitespace-nowrap">Estado</th>
                                                <th class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-center whitespace-nowrap">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @forelse($repases as $repase)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $repase->fecha->format('d/m/Y') }}</td>
                                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">{{ $repase->clinica->nombre }}</td>
                                                <td class="px-4 sm:px-6 py-4 text-right text-green-600 font-medium whitespace-nowrap">R$ {{ number_format($repase->total_examenes, 2) }}</td>
                                                <td class="px-4 sm:px-6 py-4 text-right text-red-600 font-medium whitespace-nowrap">R$ {{ number_format($repase->total_gastos, 2) }}</td>
                                                <td class="px-4 sm:px-6 py-4 text-right font-semibold whitespace-nowrap {{ $repase->total_neto >= 0 ? 'text-gray-900' : 'text-red-600' }}">R$ {{ number_format($repase->total_neto, 2) }}</td>
                                                <td class="px-4 sm:px-6 py-4 text-center whitespace-nowrap">
                                                    @if($repase->estado === 'pagado')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Pagado</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-center whitespace-nowrap">
                                                    <a href="{{ route('repases.show', $repase) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-xs font-medium">Ver</a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="7" class="px-4 sm:px-6 py-8 text-center text-gray-500">No hay repases en este período.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        @if($repases->hasPages())
                        <div class="mt-6">
                            {{ $repases->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
