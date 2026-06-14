<?php

namespace App\Data;

readonly class ExtractionResult
{
    public function __construct(
        public ?string $drugName,
        public ?string $dosage,
        public ?string $frequency,
        public ?string $duration,
        public ?string $route,
        public float $confidenceScore,
        public array $rawResponse,
        public string $modelName,
        public string $modelVersion,
    ) {}

    public function toArray(): array
    {
        return [
            'drug_name' => $this->drugName,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'route' => $this->route,
            'confidence_score' => $this->confidenceScore,
            'model_name' => $this->modelName,
            'model_version' => $this->modelVersion,
            'raw_response' => $this->rawResponse,
        ];
    }
}
