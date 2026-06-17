<?php

namespace App\Contracts\Predictive;

use App\DTOs\Predictive\SeasonalAnalysis;
use App\DTOs\Predictive\ComparisonResult;

interface TrendDetectorInterface
{
    /**
     * Detecta patrones estacionales en los datos
     *
     * @param array $data
     * @param int $minMonths Mínimo de meses requeridos (default: 24)
     * @return SeasonalAnalysis
     */
    public function detectSeasonalPatterns(array $data, int $minMonths = 24): SeasonalAnalysis;

    /**
     * Calcula la fuerza de la tendencia
     *
     * @param array $data
     * @return float
     */
    public function calculateTrendStrength(array $data): float;

    /**
     * Compara año actual vs año anterior
     *
     * @param array $currentYear
     * @param array $previousYear
     * @return ComparisonResult
     */
    public function compareYearOverYear(array $currentYear, array $previousYear): ComparisonResult;
}