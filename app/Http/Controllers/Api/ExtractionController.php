<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExtractionResource;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;

class ExtractionController extends Controller
{
    public function show(Prescription $prescription): ExtractionResource|JsonResponse
    {
        $this->authorize('view', $prescription);

        $extraction = $prescription->extraction;

        if (! $extraction) {
            return response()->json([
                'message' => 'Extraction not yet available. Current status: ' . $prescription->status,
            ], 404);
        }

        return new ExtractionResource($extraction);
    }
}
