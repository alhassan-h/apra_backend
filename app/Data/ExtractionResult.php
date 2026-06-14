<?php

namespace App\Data;

readonly class ExtractionResult
{
    public function __construct(
        /** @var array<int, array{drug_name: ?string, dosage: ?string, frequency: ?string, duration: ?string, route: ?string}> */
        public array $drugs,
        public float $confidenceScore,
        public array $rawResponse,
        public string $modelName,
        public string $modelVersion,
    ) {}

    public function toArray(): array
    {
        return [
            'drugs'           => $this->drugs,
            'confidence_score' => $this->confidenceScore,
            'model_name'       => $this->modelName,
            'model_version'    => $this->modelVersion,
            'raw_response'     => $this->rawResponse,
        ];
    }
}
