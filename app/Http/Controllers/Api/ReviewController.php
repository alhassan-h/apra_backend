<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\SubmitReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Prescription;
use App\Models\PrescriptionCorrection;
use App\Models\PrescriptionReview;
use App\Notifications\PrescriptionApprovedNotification;
use App\Notifications\PrescriptionRejectedNotification;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function show(Prescription $prescription): ReviewResource|JsonResponse
    {
        $this->authorize('view', $prescription);

        $review = $prescription->review()->with('reviewer')->first();

        if (! $review) {
            return response()->json(['message' => 'No review found for this prescription.'], 404);
        }

        return new ReviewResource($review);
    }

    public function store(SubmitReviewRequest $request, Prescription $prescription): JsonResponse
    {
        $this->authorize('review', $prescription);

        if ($prescription->review()->exists()) {
            return response()->json(['message' => 'A review already exists. Use PUT to update it.'], 409);
        }

        $user = $request->user();

        $review = PrescriptionReview::create([
            'prescription_id' => $prescription->id,
            'reviewed_by'     => $user->id,
            'drug_name'       => $request->drug_name,
            'dosage'          => $request->dosage,
            'frequency'       => $request->frequency,
            'duration'        => $request->duration,
            'route'           => $request->route,
            'review_status'   => $request->review_status,
            'review_notes'    => $request->review_notes,
        ]);

        $this->generateCorrections($prescription, $review, $user->id);

        $newStatus = match ($request->review_status) {
            'REJECTED' => 'REJECTED',
            default    => 'VERIFIED',
        };

        $prescription->update(['status' => $newStatus]);

        $prescription->uploader?->notify(
            $newStatus === 'VERIFIED'
                ? new PrescriptionApprovedNotification($prescription)
                : new PrescriptionRejectedNotification($prescription)
        );

        $this->auditService->log(
            $newStatus === 'VERIFIED' ? 'PRESCRIPTION_APPROVED' : 'PRESCRIPTION_REJECTED',
            user: $user,
            entity: $prescription,
            metadata: ['review_status' => $request->review_status],
        );

        return response()->json(new ReviewResource($review->load('reviewer')), 201);
    }

    public function update(SubmitReviewRequest $request, Prescription $prescription): ReviewResource|JsonResponse
    {
        $this->authorize('review', $prescription);

        $review = $prescription->review;

        if (! $review) {
            return response()->json(['message' => 'No review to update. Use POST to create one.'], 404);
        }

        $user = $request->user();

        $review->update([
            'reviewed_by'   => $user->id,
            'drug_name'     => $request->drug_name,
            'dosage'        => $request->dosage,
            'frequency'     => $request->frequency,
            'duration'      => $request->duration,
            'route'         => $request->route,
            'review_status' => $request->review_status,
            'review_notes'  => $request->review_notes,
        ]);

        $prescription->corrections()->delete();
        $this->generateCorrections($prescription, $review, $user->id);

        $newStatus = match ($request->review_status) {
            'REJECTED' => 'REJECTED',
            default    => 'VERIFIED',
        };

        $prescription->update(['status' => $newStatus]);

        $this->auditService->log(
            'PRESCRIPTION_REVIEW_UPDATED',
            user: $user,
            entity: $prescription,
            metadata: ['review_status' => $request->review_status],
        );

        return new ReviewResource($review->fresh()->load('reviewer'));
    }

    private function generateCorrections(Prescription $prescription, PrescriptionReview $review, string $userId): void
    {
        $extraction = $prescription->extraction;

        if (! $extraction) {
            return;
        }

        $fields = ['drug_name', 'dosage', 'frequency', 'duration', 'route'];

        foreach ($fields as $field) {
            $predicted = $extraction->$field;
            $corrected = $review->$field;

            if ($predicted !== $corrected) {
                PrescriptionCorrection::create([
                    'prescription_id' => $prescription->id,
                    'field_name'      => $field,
                    'predicted_value' => $predicted,
                    'corrected_value' => $corrected,
                    'corrected_by'    => $userId,
                ]);
            }
        }
    }
}
