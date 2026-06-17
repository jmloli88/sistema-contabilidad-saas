<?php

namespace App\DTOs\Predictive;

class ExpenseForecast
{
    public function __construct(
        public array $projections,
        public array $categoryBreakdown,
        public float $correlation,
        public array $alerts = [],
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'projections' => $this->projections,
            'category_breakdown' => $this->categoryBreakdown,
            'correlation' => $this->correlation,
            'alerts' => $this->alerts,
            'metadata' => $this->metadata,
        ];
    }
}