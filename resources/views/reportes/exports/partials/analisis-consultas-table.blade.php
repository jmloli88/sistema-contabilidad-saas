{{-- Resumen de Métricas --}}
<h2>Resumen de Métricas</h2>
<table>
    <thead>
        <tr>
            <th>Métrica</th>
            <th class="text-right">Valor</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Total de Consultas</td>
            <td class="text-right">{{ number_format($datos['total_consultas'], 0) }}</td>
        </tr>
        <tr>
            <td>Consultas por Repase</td>
            <td class="text-right">{{ number_format($datos['consultas_por_repase'], 2) }}</td>
        </tr>
        <tr>
            <td>Total de Repases</td>
            <td class="text-right">{{ number_format($datos['total_repases'], 0) }}</td>
        </tr>
        <tr>
            <td>Total de Exámenes</td>
            <td class="text-right">{{ number_format($datos['total_examenes'], 0) }}</td>
        </tr>
        <tr>
            <td>Ratio Exámenes/Consultas</td>
            <td class="text-right">
                @if($datos['ratio_examenes_consultas'])
                    {{ number_format($datos['ratio_examenes_consultas'], 2) }}
                @else
                    N/A
                @endif
            </td>
        </tr>
    </tbody>
</table>

{{-- Ranking por Clínica --}}
<h2>Ranking por Clínica</h2>
<table>
    <thead>
        <tr>
            <th class="text-center" style="width: 60px;">#</th>
            <th>Clínica</th>
            <th class="text-right">Total Consultas</th>
            <th class="text-right">Cantidad Repases</th>
            <th class="text-right">Consultas por Repase</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos['por_clinica'] as $index => $clinica)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $clinica->nombre_clinica }}</td>
            <td class="text-right">{{ number_format($clinica->total_consultas, 0) }}</td>
            <td class="text-right">{{ number_format($clinica->cantidad_repases, 0) }}</td>
            <td class="text-right">{{ number_format($clinica->consultas_por_repase, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Evolución Mensual --}}
<h2>Evolución Mensual de Consultas</h2>
<table>
    <thead>
        <tr>
            <th>Mes</th>
            <th class="text-right">Total Consultas</th>
            <th class="text-right">Cantidad Repases</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos['por_mes'] as $mes)
        <tr>
            <td>{{ $mes->mes }}</td>
            <td class="text-right">{{ number_format($mes->total_consultas, 0) }}</td>
            <td class="text-right">{{ number_format($mes->cantidad_repases, 0) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
