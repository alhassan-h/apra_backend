<?php

namespace App\Jobs;

use App\Contracts\PrescriptionExtractionServiceInterface;
use App\Models\DrugDictionary;
use App\Models\Prescription;
use App\Models\PrescriptionExtraction;
use App\Notifications\ExtractionCompleteNotification;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPrescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly Prescription $prescription,
    ) {}

    public function handle(
        PrescriptionExtractionServiceInterface $extractionService,
        AuditService $auditService,
    ): void {
        $this->prescription->update(['status' => 'PROCESSING']);

        $auditService->log(
            'AI_EXTRACTION_STARTED',
            entity: $this->prescription,
            metadata: ['model' => $extractionService->getModelName()],
        );

        $result = $extractionService->extract($this->prescription->image_url);

        // Normalize drug name against the dictionary
        $normalizedDrugName = $result->drugName;
        if ($result->drugName) {
            $drug = DrugDictionary::findByName($result->drugName);
            if ($drug) {
                $normalizedDrugName = $drug->generic_name;
            }
        }

        PrescriptionExtraction::create([
            'prescription_id'  => $this->prescription->id,
            'model_name'       => $result->modelName,
            'model_version'    => $result->modelVersion,
            'raw_response'     => $result->rawResponse,
            'drug_name'        => $normalizedDrugName,
            'dosage'           => $result->dosage,
            'frequency'        => $result->frequency,
            'duration'         => $result->duration,
            'route'            => $result->route,
            'confidence_score' => $result->confidenceScore,
        ]);

        $this->prescription->update(['status' => 'EXTRACTED']);

        $uploader = $this->prescription->uploader;
        if ($uploader) {
            $uploader->notify(new ExtractionCompleteNotification($this->prescription));
        }

        $auditService->log(
            'AI_EXTRACTION_COMPLETED',
            user: $uploader,
            entity: $this->prescription,
            metadata: [
                'model'      => $result->modelName,
                'confidence' => $result->confidenceScore,
                'drug_found' => isset($drug),
            ],
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessPrescriptionJob failed', [
            'prescription_id' => $this->prescription->id,
            'error' => $exception->getMessage(),
        ]);

        $this->prescription->update(['status' => 'UPLOADED']);
    }
}
