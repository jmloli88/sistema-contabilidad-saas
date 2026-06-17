<?php

namespace App\Contracts\Predictive;

use App\DTOs\Predictive\CapacityAnalysis;
use Carbon\Carbon;

interface CapacityAnalyzerInterface
{
    /**
     * Analiza la capacidad operativa actual
     *
     * @param array $filters
     * @return CapacityAnalysis
     */
    public function analyzeCurrentCapacity(array $filters): CapacityAnalysis;

    /**
     * Proyecta la fecha de saturación
     *
     * @param array $filters
     * @return Carbon|null
     */
    public function projectSaturationDate(array $filters): ?Carbon;

    /**
     * Recomienda acciones basadas en el análisis
     *
     * @param CapacityAnalysis $analysis
     * @return array
     */
    public function recommendActions(CapacityAnalysis $analysis): array;
}