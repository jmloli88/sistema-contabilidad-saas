<?php

namespace App\Contracts\Predictive;

interface ExportServiceInterface
{
    /**
     * Exporta predicciones a Excel
     *
     * @param array $data
     * @param array $options
     * @return string Ruta del archivo generado
     */
    public function exportToExcel(array $data, array $options = []): string;

    /**
     * Exporta predicciones a PDF
     *
     * @param array $data
     * @param array $options
     * @return string Ruta del archivo generado
     */
    public function exportToPdf(array $data, array $options = []): string;

    /**
     * Genera nombre único para archivo
     *
     * @param string $type
     * @param string $format
     * @return string
     */
    public function generateUniqueFilename(string $type, string $format): string;
}