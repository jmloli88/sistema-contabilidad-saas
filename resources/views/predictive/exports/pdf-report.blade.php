<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Análisis Predictivo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2563eb;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        
        .header .subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        
        .metadata {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .metadata h2 {
            color: #374151;
            font-size: 16px;
            margin: 0 0 15px 0;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 5px;
        }
        
        .metadata-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .metadata-item {
            display: flex;
            justify-content: space-between;
        }
        
        .metadata-label {
            font-weight: bold;
            color: #4b5563;
        }
        
        .section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .section h2 {
            color: #2563eb;
            font-size: 18px;
            margin: 0 0 20px 0;
            border-bottom: 1px solid #2563eb;
            padding-bottom: 5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            text-align: left;
        }
        
        .data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .chart-placeholder {
            background-color: #f3f4f6;
            border: 2px dashed #9ca3af;
            padding: 40px;
            text-align: center;
            color: #6b7280;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .alert {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .alert.warning {
            background-color: #fffbeb;
            border-color: #fed7aa;
            color: #d97706;
        }
        
        .alert.info {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #2563eb;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #d1d5db;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .number {
            text-align: right;
        }
        
        .percentage {
            text-align: right;
        }
        
        .percentage::after {
            content: '%';
        }
        
        .currency::before {
            content: '€ ';
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Análisis Predictivo</h1>
        <div class="subtitle">Sistema de Contabilidad Médica</div>
    </div>

    <div class="metadata">
        <h2>Información del Reporte</h2>
        <div class="metadata-grid">
            <div class="metadata-item">
                <span class="metadata-label">Fecha de Generación:</span>
                <span>{{ $metadata['generation_date'] }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Período Analizado:</span>
                <span>{{ $metadata['period_analyzed'] }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Tipo de Reporte:</span>
                <span>{{ $metadata['report_type'] }}</span>
            </div>
            <div class="metadata-item">
                <span class="metadata-label">Total de Registros:</span>
                <span>{{ number_format($metadata['total_records']) }}</span>
            </div>
        </div>
        
        @if(!empty($metadata['parameters']))
        <div style="margin-top: 15px;">
            <div class="metadata-label">Parámetros:</div>
            <div style="margin-top: 5px; font-size: 11px;">
                @foreach($metadata['parameters'] as $key => $value)
                    <div>{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @if(isset($data['income_predictions']))
    <div class="section">
        <h2>Predicción de Ingresos</h2>
        
        @if(isset($charts['income']))
        <div class="chart-placeholder">
            Gráfico: {{ $charts['income']['title'] ?? 'Proyección de Ingresos' }}
            <br><small>Tipo: {{ ucfirst($charts['income']['type'] ?? 'line') }}</small>
        </div>
        @endif
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Proyección</th>
                    <th>Algoritmo</th>
                    <th>Precisión</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['income_predictions']->getProjections() as $period => $value)
                <tr>
                    <td>{{ $period }}</td>
                    <td class="number currency">{{ number_format($value, 2) }}</td>
                    <td>{{ $data['income_predictions']->algorithm }}</td>
                    <td class="percentage">{{ $data['income_predictions']->accuracy ? number_format($data['income_predictions']->accuracy * 100, 1) : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($data['expense_forecasts']))
    <div class="section">
        <h2>Pronóstico de Gastos</h2>
        
        @if(isset($charts['expenses']))
        <div class="chart-placeholder">
            Gráfico: {{ $charts['expenses']['title'] ?? 'Pronóstico de Gastos' }}
            <br><small>Tipo: {{ ucfirst($charts['expenses']['type'] ?? 'bar') }}</small>
        </div>
        @endif
        
        <div style="margin-bottom: 20px;">
            <strong>Correlación Ingresos-Gastos:</strong> 
            {{ number_format($data['expense_forecasts']->correlation, 4) }}
        </div>
        
        @if(!empty($data['expense_forecasts']->alerts))
        <div class="alert warning">
            <strong>Alertas:</strong>
            <ul style="margin: 5px 0 0 20px;">
                @foreach($data['expense_forecasts']->alerts as $alert)
                <li>{{ $alert }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <h3>Proyecciones por Período</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Proyección</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['expense_forecasts']->projections as $period => $value)
                <tr>
                    <td>{{ $period }}</td>
                    <td class="number currency">{{ number_format($value, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <h3>Desglose por Categoría</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Proyección</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['expense_forecasts']->categoryBreakdown as $category => $value)
                <tr>
                    <td>{{ ucfirst($category) }}</td>
                    <td class="number currency">{{ number_format($value, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($data['capacity_analysis']))
    <div class="section page-break">
        <h2>Análisis de Capacidad</h2>
        
        @if(isset($charts['capacity']))
        <div class="chart-placeholder">
            Gráfico: {{ $charts['capacity']['title'] ?? 'Utilización por Clínica' }}
            <br><small>Tipo: {{ ucfirst($charts['capacity']['type'] ?? 'pie') }}</small>
        </div>
        @endif
        
        <div style="margin-bottom: 20px;">
            <div class="alert info">
                <strong>Utilización General:</strong> 
                {{ number_format($data['capacity_analysis']->currentUtilization, 1) }}%
            </div>
            
            @if($data['capacity_analysis']->projectedSaturationDate)
            <div class="alert warning">
                <strong>Fecha Proyectada de Saturación:</strong> 
                {{ $data['capacity_analysis']->projectedSaturationDate->format('d/m/Y') }}
            </div>
            @endif
        </div>
        
        <h3>Utilización por Clínica</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Clínica</th>
                    <th>Utilización</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['capacity_analysis']->clinicUtilization as $clinic => $utilization)
                <tr>
                    <td>{{ $clinic }}</td>
                    <td class="percentage">{{ number_format($utilization, 1) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @if(!empty($data['capacity_analysis']->bottlenecks))
        <h3>Cuellos de Botella Identificados</h3>
        <ul>
            @foreach($data['capacity_analysis']->bottlenecks as $bottleneck)
            <li>{{ $bottleneck }}</li>
            @endforeach
        </ul>
        @endif
        
        @if(!empty($data['capacity_analysis']->recommendations))
        <h3>Recomendaciones</h3>
        <ul>
            @foreach($data['capacity_analysis']->recommendations as $recommendation)
            <li>{{ $recommendation }}</li>
            @endforeach
        </ul>
        @endif
    </div>
    @endif

    @if(isset($data['seasonal_analysis']))
    <div class="section">
        <h2>Análisis Estacional</h2>
        
        @if(isset($charts['seasonal']))
        <div class="chart-placeholder">
            Gráfico: {{ $charts['seasonal']['title'] ?? 'Patrones Estacionales' }}
            <br><small>Tipo: {{ ucfirst($charts['seasonal']['type'] ?? 'line') }}</small>
        </div>
        @endif
        
        <div style="margin-bottom: 20px;">
            <strong>Fuerza Estacional:</strong> 
            {{ number_format($data['seasonal_analysis']->seasonalStrength, 4) }}
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Patrón</th>
                    <th>Intervalo de Confianza</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['seasonal_analysis']->monthlyPatterns as $month => $pattern)
                <tr>
                    <td>{{ $month }}</td>
                    <td class="number">{{ number_format($pattern, 4) }}</td>
                    <td class="number">
                        {{ isset($data['seasonal_analysis']->confidenceIntervals[$month]) 
                           ? number_format($data['seasonal_analysis']->confidenceIntervals[$month], 4) 
                           : 'N/A' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <div>Sistema de Contabilidad Médica - Módulo de Análisis Predictivo</div>
        <div>Generado el {{ $metadata['generation_date'] }} | Página {PAGE_NUM} de {PAGE_COUNT}</div>
    </div>
</body>
</html>