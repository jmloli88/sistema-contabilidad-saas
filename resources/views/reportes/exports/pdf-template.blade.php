<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        @page {
            size: A4;
            margin: 2cm 4cm 2cm 4cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.4;
            padding: 0 15px;
        }

        .header {
            background-color: #1e40af;
            color: #ffffff;
            padding: 25px 30px;
            margin: 10px 0 25px 0;
            border-radius: 8px;
            border-bottom: 4px solid #3b82f6;
        }

        .header h1 {
            font-size: 18pt;
            margin: 0;
            display: inline-block;
            color: #ffffff;
        }

        .header .info {
            font-size: 9pt;
            color: #e0e7ff;
        }

        .filtros {
            background-color: #f3f4f6;
            padding: 20px 25px;
            margin: 0 0 25px 0;
            border-left: 4px solid #1e40af;
            border-radius: 6px;
        }

        .filtros h2 {
            font-size: 11pt;
            margin-bottom: 8px;
            color: #1e40af;
        }

        .filtros .filtro-item {
            margin-bottom: 5px;
        }

        .filtros .filtro-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 25px 0;
        }

        table thead {
            background-color: #e5e7eb;
        }

        table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #9ca3af;
            color: #1f2937;
            background-color: #f3f4f6;
        }

        table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .currency {
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .positive {
            color: #059669;
        }

        .negative {
            color: #dc2626;
        }

        .warning {
            color: #d97706;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #6b7280;
            padding: 10px;
            border-top: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }

        .page-break {
            page-break-after: always;
        }

        .chart-container {
            margin: 25px 0;
            text-align: center;
        }

        .chart-container img {
            max-width: 100%;
            height: auto;
        }

        h2 {
            color: #1e40af;
            font-size: 13pt;
            margin-top: 25px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>
    {{-- Encabezado --}}
    <div class="header">
        <div style="margin-bottom: 10px;">
            <h1 style="margin: 0; color: #ffffff;">{{ $titulo }}</h1>
            <div style="font-size: 8pt; margin-top: 5px; color: #e0e7ff;">Sistema de Contabilidad Médica</div>
        </div>
        <div class="info">
            Generado el: {{ $fecha_generacion }}
        </div>
    </div>

    {{-- Filtros aplicados --}}
    @if(!empty($filtros))
    <div class="filtros">
        <h2>Filtros Aplicados</h2>
        @foreach($filtros as $filtro)
        <div class="filtro-item">
            <span class="filtro-label">{{ $filtro['label'] }}:</span>
            <span>{{ $filtro['valor'] }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Contenido según tipo de reporte --}}
    @if($tipo_reporte === 'rentabilidad-clinica')
        @include('reportes.exports.partials.rentabilidad-clinica-table', ['datos' => $datos])
    @elseif($tipo_reporte === 'rentabilidad-examen')
        @include('reportes.exports.partials.rentabilidad-examen-table', ['datos' => $datos])
    @elseif($tipo_reporte === 'productividad')
        @include('reportes.exports.partials.productividad-table', ['datos' => $datos])
    @elseif($tipo_reporte === 'comparativo')
        @include('reportes.exports.partials.comparativo-table', ['datos' => $datos])
    @elseif($tipo_reporte === 'analisis-consultas')
        @include('reportes.exports.partials.analisis-consultas-table', ['datos' => $datos])
    @endif

    {{-- Gráficos (si se proporcionan) --}}
    @if(!empty($graficos))
    <div class="page-break"></div>
    <h2 style="margin-bottom: 15px;">Visualizaciones</h2>
    @foreach($graficos as $grafico)
    <div class="chart-container">
        <img src="{{ $grafico }}" alt="Gráfico">
    </div>
    @endforeach
    @endif

    {{-- Pie de página --}}
    <div class="footer">
        Sistema de Contabilidad Médica - Página <span class="pagenum"></span>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>
