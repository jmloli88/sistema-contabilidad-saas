<?php

namespace App\DTOs\Predictive;

class SeasonalAnalysis
{
    public function __construct(
        public array $monthlyPatterns,
        public float $seasonalStrength,
        public array $confidenceIntervals,
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'monthly_patterns' => $this->monthlyPatterns,
            'seasonal_strength' => $this->seasonalStrength,
            'confidence_intervals' => $this->confidenceIntervals,
            'metadata' => $this->metadata,
        ];
    }
}