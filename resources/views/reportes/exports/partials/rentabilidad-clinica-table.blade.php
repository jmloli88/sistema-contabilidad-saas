<table>
    <thead>
        <tr>
            <th>Clínica</th>
            <th class="text-right">Total Ingresos</th>
            <th class="text-right">Total Gastos</th>
            <th class="text-right">Ganancia Neta</th>
            <th class="text-right">Margen (%)</th>
            <th class="text-center">Repases</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datos as $registro)
        <tr>
            <td>{{ $registro->nombre_clinica }}</td>
            <td class="text-right currency">${{ number_format($registro->total_ingresos, 2) }}</td>
            <td class="text-right currency">${{ number_format($registro->total_gastos, 2) }}</td>
            <td class="text-right currency">${{ number_format($registro->ganancia_neta, 2) }}</td>
            <td class="text-right
                @if($registro->margen_ganancia !== null)
                    @if($registro->margen_ganancia > 50) positive
                    @elseif($registro->margen_ganancia >= 20) warning
                    @else negative
                    @endif
                @endif
            ">
                @if($registro->margen_ganancia !== null)
                    {{ number_format($registro->margen_ganancia, 2) }}%
                @else
                    N/A
                @endif
            </td>
            <td class="text-center">{{ $registro->cantidad_repases }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center">No se encontraron datos</td>
        </tr>
        @endforelse
    </tbody>
</table>
