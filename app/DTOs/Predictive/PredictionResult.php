<?php

namespace App\DTOs\Predictive;

class PredictionResult
{
    public function __construct(
        public array $projections,
        public string $algorithm,
        public array $metadata = [],
        public ?float $accuracy = null
    ) {}

    public function getProjection(string $period): ?float
    {
        return $this->projections[$period] ?? null;
    }

    public function getProjections(): array
    {
        return $this->projections;
    }

    public function toArray(): array
    {
        return [
            'projections' => $this->projections,
            'algorithm' => $this->algorithm,
            'metadata' => $this->metadata,
            'accuracy' => $this->accuracy,
        ];
    }
}