<?php

namespace App\Contracts\Predictive;

use App\DTOs\Predictive\PredictionResult;

interface IncomePredictorInterface
{
    /**
     * Predice ingresos futuros usando múltiples algoritmos
     *
     * @param array $filters Filtros de consulta (clinica_id, fecha_inicio, fecha_fin)
     * @param int $months Número de meses a predecir (3, 6, 12)
     * @return PredictionResult
     */
    public function predictIncome(array $filters, int $months): PredictionResult;

    /**
     * Obtiene los algoritmos disponibles para predicción
     *
     * @return array
     */
    public function getAvailableAlgorithms(): array;

    /**
     * Calcula la precisión de un algoritmo específico
     *
     * @param string $algorithm
     * @param array $historicalData
     * @return float
     */
    public function calculateAccuracy(string $algorithm, array $historicalData): float;
}