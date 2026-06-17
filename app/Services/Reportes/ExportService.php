<?php

namespace App\Services\Reportes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Servicio para exportación de reportes a Excel y PDF
 * 
 * Este servicio maneja la exportación de datos de reportes a diferentes formatos,
 * aplicando el formato apropiado para cada tipo de dato (moneda, porcentaje).
 */
class ExportService
{
    /**
     * Exporta datos a Excel
     * 
     * Genera un archivo Excel con los datos del reporte, aplicando formato
     * de celdas apropiado (moneda para valores monetarios, porcentaje para márgenes).
     * Incluye una hoja de resumen con información de filtros aplicados.
     * 
     * @param string $tipoReporte Tipo de reporte (rentabilidad-clinica, rentabilidad-examen, productividad, comparativo)
     * @param \Illuminate\Support\Collection|array $datos Datos del reporte a exportar
     * @param array $filtros Filtros aplicados al reporte
     * @return string Ruta del archivo generado
     */
    public function exportarExcel(
        string $tipoReporte,
        Collection|array $datos,
        array $filtros
    ): string {
        $nombreArchivo = $this->generarNombreArchivo($tipoReporte, 'xlsx');
        
        // Asegurar que el directorio existe
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $rutaArchivo = $tempDir . DIRECTORY_SEPARATOR . $nombreArchivo;

        // Crear el export según el tipo de reporte
        $export = $this->crearExportExcel($tipoReporte, $datos, $filtros);

        // Guardar el archivo usando el método download que retorna el contenido
        $contenido = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        file_put_contents($rutaArchivo, $contenido);

        return $rutaArchivo;
    }

    /**
     * Exporta datos a PDF
     * 
     * Genera un archivo PDF con los datos del reporte, incluyendo:
     * - Encabezado con título, fecha de generación y filtros aplicados
     * - Tabla con datos formateados
     * - Gráficos como imágenes (opcional)
     * - Pie de página con números de página
     * 
     * @param string $tipoReporte Tipo de reporte
     * @param \Illuminate\Support\Collection|array $datos Datos del reporte a exportar
     * @param array $filtros Filtros aplicados al reporte
     * @param array $graficos URLs de imágenes de gráficos (opcional)
     * @return string Ruta del archivo generado
     */
    public function exportarPdf(
        string $tipoReporte,
        Collection|array $datos,
        array $filtros,
        array $graficos = []
    ): string {
        $nombreArchivo = $this->generarNombreArchivo($tipoReporte, 'pdf');
        
        // Asegurar que el directorio existe
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $rutaArchivo = $tempDir . DIRECTORY_SEPARATOR . $nombreArchivo;

        // Preparar datos para la vista
        $datosVista = [
            'titulo' => $this->obtenerTituloReporte($tipoReporte),
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'filtros' => $this->formatearFiltrosParaPdf($filtros),
            'datos' => $datos,
            'tipo_reporte' => $tipoReporte,
            'graficos' => $graficos,
        ];

        // Generar PDF
        $pdf = Pdf::loadView('reportes.exports.pdf-template', $datosVista);
        $pdf->setPaper('a4', 'portrait');
        $pdf->save($rutaArchivo);

        return $rutaArchivo;
    }

    /**
     * Crea el objeto Export de Laravel Excel según el tipo de reporte
     * 
     * @param string $tipoReporte
     * @param \Illuminate\Support\Collection|array $datos
     * @param array $filtros
     * @return mixed
     */
    protected function crearExportExcel(string $tipoReporte, Collection|array $datos, array $filtros)
    {
        return match ($tipoReporte) {
            'rentabilidad-clinica' => new \App\Exports\RentabilidadClinicaExport($datos, $filtros),
            'rentabilidad-examen' => new \App\Exports\RentabilidadExamenExport($datos, $filtros),
            'productividad' => new \App\Exports\ProductividadExport($datos, $filtros),
            'comparativo' => new \App\Exports\ComparativoExport($datos, $filtros),
            'analisis-consultas' => new \App\Exports\AnalisisConsultasExport($datos, $filtros),
            'detalle-repases' => new \App\Exports\DetalleRepasesExport($datos, $filtros),
            default => throw new \InvalidArgumentException("Tipo de reporte no válido: {$tipoReporte}"),
        };
    }

    /**
     * Genera el nombre del archivo según el patrón reporte_{tipo}_{fecha}.{extension}
     * 
     * @param string $tipoReporte
     * @param string $extension
     * @return string
     */
    protected function generarNombreArchivo(string $tipoReporte, string $extension): string
    {
        $fecha = now()->format('Y-m-d');
        return "reporte_{$tipoReporte}_{$fecha}.{$extension}";
    }

    /**
     * Obtiene el título legible del reporte
     * 
     * @param string $tipoReporte
     * @return string
     */
    protected function obtenerTituloReporte(string $tipoReporte): string
    {
        return match ($tipoReporte) {
            'rentabilidad-clinica' => 'Reporte de Rentabilidad por Clínica',
            'rentabilidad-examen' => 'Reporte de Rentabilidad por Tipo de Examen',
            'productividad' => 'Reporte de Productividad',
            'comparativo' => 'Reporte Comparativo de Períodos',
            'analisis-consultas' => 'Análisis de Consultas Médicas',
            'detalle-repases' => 'Detalle de Repases con Gastos',
            default => 'Reporte',
        };
    }

    /**
     * Formatea los filtros para mostrar en el PDF
     * 
     * @param array $filtros
     * @return array
     */
    protected function formatearFiltrosParaPdf(array $filtros): array
    {
        $filtrosFormateados = [];

        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $filtrosFormateados[] = [
                'label' => 'Período',
                'valor' => date('d/m/Y', strtotime($filtros['fecha_inicio'])) . ' - ' . 
                          date('d/m/Y', strtotime($filtros['fecha_fin'])),
            ];
        }

        if (isset($filtros['clinica_nombre'])) {
            $filtrosFormateados[] = [
                'label' => 'Clínica',
                'valor' => $filtros['clinica_nombre'],
            ];
        }

        if (isset($filtros['examen_nombre'])) {
            $filtrosFormateados[] = [
                'label' => 'Examen',
                'valor' => $filtros['examen_nombre'],
            ];
        }

        return $filtrosFormateados;
    }
}
