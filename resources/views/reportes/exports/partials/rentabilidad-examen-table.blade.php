<table>
    <thead>
        <tr>
            <th>Examen</th>
            <th class="text-center">Cantidad Total</th>
            <th class="text-right">Total Ingresos</th>
            <th class="text-right">Ingreso Promedio</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datos as $registro)
        <tr>
            <td>{{ $registro->nombre_examen }}</td>
            <td class="text-center">{{ $registro->cantidad_total }}</td>
            <td class="text-right currency">${{ number_format($registro->total_ingresos, 2) }}</td>
            <td class="text-right currency">
                @if($registro->ingreso_promedio !== null)
                    ${{ number_format($registro->ingreso_promedio, 2) }}
                @else
                    N/A
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center">No se encontraron datos</td>
        </tr>
        @endforelse
    </tbody>
</table>
