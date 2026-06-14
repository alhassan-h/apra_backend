<?php

namespace App\Contracts;

use App\Data\ExtractionResult;

interface PrescriptionExtractionServiceInterface
{
    /**
     * Extract prescription data from an image URL.
     */
    public function extract(string $imageUrl): ExtractionResult;

    public function getModelName(): string;

    public function getModelVersion(): string;
}
