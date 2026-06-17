<?php

namespace App\DTOs\Predictive;

class ComparisonResult
{
    public function __construct(
        public array $deviations,
        public float $overallChange,
        public array $significantChanges,
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'deviations' => $this->deviations,
            'overall_change' => $this->overallChange,
            'significant_changes' => $this->significantChanges,
            'metadata' => $this->metadata,
        ];
    }
}