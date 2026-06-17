<?php

namespace App\DTOs\Predictive;

use Carbon\Carbon;

class CapacityAnalysis
{
    public function __construct(
        public float $currentUtilization,
        public array $clinicUtilization,
        public ?Carbon $projectedSaturationDate,
        public array $bottlenecks = [],
        public array $recommendations = [],
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'current_utilization' => $this->currentUtilization,
            'clinic_utilization' => $this->clinicUtilization,
            'projected_saturation_date' => $this->projectedSaturationDate?->toDateString(),
            'bottlenecks' => $this->bottlenecks,
            'recommendations' => $this->recommendations,
            'metadata' => $this->metadata,
        ];
    }
}