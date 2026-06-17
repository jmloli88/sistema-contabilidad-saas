<h2 style="margin-bottom: 15px;">Resumen General</h2>
<table style="margin-bottom: 30px;">
    <thead>
        <tr>
            <th>Métrica</th>
            <th class="text-right">Valor</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Total Exámenes Realizados</td>
            <td class="text-right">{{ number_format($datos['total_examenes_realizados']) }}</td>
        </tr>
        <tr>
            <td>Exámenes por Día</td>
            <td class="text-right">{{ number_format($datos['examenes_por_dia'], 2) }}</td>
        </tr>
        <tr>
            <td>Total Repases</td>
            <td class="text-right">{{ number_format($datos['total_repases']) }}</td>
        </tr>
        <tr>
            <td>Exámenes por Repase</td>
            <td class="text-right">{{ number_format($datos['examenes_por_repase'], 2) }}</td>
        </tr>
    </tbody>
</table>

<h2 style="margin-bottom: 15px;">Desglose por Tipo de Examen</h2>
<table style="margin-bottom: 30px;">
    <thead>
        <tr>
            <th>Examen</th>
            <th class="text-right">Cantidad Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datos['por_examen'] as $item)
        <tr>
            <td>{{ $item->nombre_examen }}</td>
            <td class="text-right">{{ number_format($item->cantidad_total) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" class="text-center">No se encontraron datos</td>
        </tr>
        @endforelse
    </tbody>
</table>

<h2 style="margin-bottom: 15px;">Desglose por Clínica</h2>
<table>
    <thead>
        <tr>
            <th>Clínica</th>
            <th class="text-right">Cantidad Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datos['por_clinica'] as $item)
        <tr>
            <td>{{ $item->nombre_clinica }}</td>
            <td class="text-right">{{ number_format($item->cantidad_total) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" class="text-center">No se encontraron datos</td>
        </tr>
        @endforelse
    </tbody>
</table>
