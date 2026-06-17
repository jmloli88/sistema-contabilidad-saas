<?php

namespace App\Contracts\Predictive;

use App\DTOs\Predictive\ExpenseForecast;

interface ExpenseForecasterInterface
{
    /**
     * Predice gastos futuros por categoría
     *
     * @param array $filters
     * @param int $months
     * @return ExpenseForecast
     */
    public function forecastExpenses(array $filters, int $months): ExpenseForecast;

    /**
     * Calcula correlación de Pearson entre ingresos y gastos
     *
     * @param array $incomes
     * @param array $expenses
     * @return float
     */
    public function calculateCorrelation(array $incomes, array $expenses): float;

    /**
     * Verifica alertas de umbral
     *
     * @param ExpenseForecast $forecast
     * @return array
     */
    public function checkThresholdAlerts(ExpenseForecast $forecast): array;
}