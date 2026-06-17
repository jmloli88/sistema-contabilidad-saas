<table>
    <thead>
        <tr>
            <th>Métrica</th>
            <th class="text-right">Período Anterior<br>
                <small style="font-weight: normal;">
                    {{ date('d/m/Y', strtotime($datos['periodo_anterior']['fecha_inicio'])) }} - 
                    {{ date('d/m/Y', strtotime($datos['periodo_anterior']['fecha_fin'])) }}
                </small>
            </th>
            <th class="text-right">Período Actual<br>
                <small style="font-weight: normal;">
                    {{ date('d/m/Y', strtotime($datos['periodo_actual']['fecha_inicio'])) }} - 
                    {{ date('d/m/Y', strtotime($datos['periodo_actual']['fecha_fin'])) }}
                </small>
            </th>
            <th class="text-right">Variación (%)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Total Ingresos</td>
            <td class="text-right currency">${{ number_format($datos['periodo_anterior']['total_ingresos'], 2) }}</td>
            <td class="text-right currency">${{ number_format($datos['periodo_actual']['total_ingresos'], 2) }}</td>
            <td class="text-right {{ $datos['variaciones']['ingresos_variacion'] !== null && $datos['variaciones']['ingresos_variacion'] >= 0 ? 'positive' : 'negative' }}">
                @if($datos['variaciones']['ingresos_variacion'] !== null)
                    {{ number_format($datos['variaciones']['ingresos_variacion'], 2) }}%
                @else
                    N/A
                @endif
            </td>
        </tr>
        <tr>
            <td>Total Gastos</td>
            <td class="text-right currency">${{ number_format($datos['periodo_anterior']['total_gastos'], 2) }}</td>
            <td class="text-right currency">${{ number_format($datos['periodo_actual']['total_gastos'], 2) }}</td>
            <td class="text-right {{ $datos['variaciones']['gastos_variacion'] !== null && $datos['variaciones']['gastos_variacion'] < 0 ? 'positive' : 'negative' }}">
                @if($datos['variaciones']['gastos_variacion'] !== null)
                    {{ number_format($datos['variaciones']['gastos_variacion'], 2) }}%
                @else
                    N/A
                @endif
            </td>
        </tr>
        <tr>
            <td>Ganancia Neta</td>
            <td class="text-right currency">${{ number_format($datos['periodo_anterior']['ganancia_neta'], 2) }}</td>
            <td class="text-right currency">${{ number_format($datos['periodo_actual']['ganancia_neta'], 2) }}</td>
            <td class="text-right {{ $datos['variaciones']['ganancia_variacion'] !== null && $datos['variaciones']['ganancia_variacion'] >= 0 ? 'positive' : 'negative' }}">
                @if($datos['variaciones']['ganancia_variacion'] !== null)
                    {{ number_format($datos['variaciones']['ganancia_variacion'], 2) }}%
                @else
                    N/A
                @endif
            </td>
        </tr>
    </tbody>
</table>
