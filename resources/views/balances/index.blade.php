<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-cyan-600 text-xl fill">account_balance_wallet</span>
                </div>
                <div>
                    <h2 class="font-bold text-xl text-gray-800 leading-tight">
                        Balances
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Resumen financiero por períodos</p>
                </div>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <div class="py-8 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <nav class="flex mb-8 mx-4 sm:mx-0 text-sm text-gray-500" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Balances</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <div class="mb-8 mx-4 sm:mx-0">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Selecciona un Tipo de Balance</h3>
                    <p class="text-gray-600">Visualiza el resumen financiero agrupado por período</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mx-4 sm:mx-0 mb-10">
                    <a href="{{ route('balances.mensual') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-blue-500 to-cyan-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">calendar_month</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors duration-200">Balance Mensual</h4>
                                <p class="text-sm text-gray-600 leading-relaxed">Resumen financiero mes a mes. Visualiza ingresos, gastos, neto y margen de ganancia de cada mes.</p>
                                <div class="mt-4 flex items-center text-blue-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver balance
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('balances.trimestral') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-green-500 to-emerald-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">view_week</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-green-600 transition-colors duration-200">Balance Trimestral</h4>
                                <p class="text-sm text-gray-600 leading-relaxed">Resumen financiero por trimestre. Analiza el desempeño del negocio en períodos de 3 meses.</p>
                                <div class="mt-4 flex items-center text-green-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver balance
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('balances.semestral') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-purple-500 to-pink-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">date_range</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-purple-600 transition-colors duration-200">Balance Semestral</h4>
                                <p class="text-sm text-gray-600 leading-relaxed">Resumen financiero por semestre. Evalúa el rendimiento del primer y segundo semestre del año.</p>
                                <div class="mt-4 flex items-center text-purple-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver balance
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('balances.anual') }}" class="group bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-orange-500 to-red-600 text-white">
                                    <span class="material-symbols-outlined text-2xl">calendar_today</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors duration-200">Balance Anual</h4>
                                <p class="text-sm text-gray-600 leading-relaxed">Resumen financiero año por año. Visualiza el desempeño global del negocio en cada ejercicio anual.</p>
                                <div class="mt-4 flex items-center text-orange-600 font-semibold text-sm group-hover:translate-x-2 transition-transform duration-200">
                                    Ver balance
                                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="mx-4 sm:mx-0">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Resumen Global</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                            <p class="text-sm font-medium text-gray-500 mb-1">Ingresos</p>
                            <p class="text-2xl font-bold text-green-600">R$ {{ number_format($resumen['total_ingresos'], 2) }}</p>
                        </div>
                        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                            <p class="text-sm font-medium text-gray-500 mb-1">Gastos</p>
                            <p class="text-2xl font-bold text-red-600">R$ {{ number_format($resumen['total_gastos'], 2) }}</p>
                        </div>
                        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                            <p class="text-sm font-medium text-gray-500 mb-1">Neto</p>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($resumen['total_neto'], 2) }}</p>
                        </div>
                        <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6">
                            <p class="text-sm font-medium text-gray-500 mb-1">Margen</p>
                            <p class="text-2xl font-bold {{ $resumen['margen_ganancia'] !== null && $resumen['margen_ganancia'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $resumen['margen_ganancia'] !== null ? number_format($resumen['margen_ganancia'], 1) . '%' : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>

                @if($balancesMensuales->isNotEmpty())
                <div class="mx-4 sm:mx-0">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Evolución Mensual (último año)</h3>
                    <div class="bg-white rounded-xl shadow-md border border-gray-100">
                        <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-700">
                                <thead class="text-xs text-gray-700 uppercase bg-gradient-to-r from-gray-100 to-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold">Mes</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right">Ingresos</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right">Gastos</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right">Neto</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right">Margen</th>
                                        <th scope="col" class="px-4 sm:px-6 py-3 sm:py-4 font-bold text-right">Repases</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($balancesMensuales as $balance)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-900">{{ $balance->period_label }}</td>
                                        <td class="px-4 sm:px-6 py-4 text-right text-green-600 font-medium">R$ {{ number_format($balance->total_ingresos, 2) }}</td>
                                        <td class="px-4 sm:px-6 py-4 text-right text-red-600 font-medium">R$ {{ number_format($balance->total_gastos, 2) }}</td>
                                        <td class="px-4 sm:px-6 py-4 text-right font-semibold {{ $balance->total_neto >= 0 ? 'text-gray-900' : 'text-red-600' }}">R$ {{ number_format($balance->total_neto, 2) }}</td>
                                        <td class="px-4 sm:px-6 py-4 text-right {{ $balance->margen_ganancia !== null && $balance->margen_ganancia >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $balance->margen_ganancia !== null ? number_format($balance->margen_ganancia, 1) . '%' : 'N/A' }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 text-right font-medium">{{ $balance->total_repases }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="mx-4 sm:mx-0">
                    <x-empty-state
                        icon="bar_chart"
                        title="No hay balances"
                        description="Aún no hay datos financieros para mostrar."
                    />
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
