<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionCorrection;
use App\Models\PrescriptionExtraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $base = Prescription::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('uploaded_by', $user->id));

        $totalPrescriptions  = (clone $base)->count();
        $verifiedCount       = (clone $base)->where('status', 'VERIFIED')->count();
        $rejectedCount       = (clone $base)->where('status', 'REJECTED')->count();
        $pendingReviewCount  = (clone $base)->whereIn('status', ['EXTRACTED', 'UNDER_REVIEW'])->count();
        $processingCount     = (clone $base)->whereIn('status', ['UPLOADED', 'PROCESSING'])->count();

        $avgConfidence = PrescriptionExtraction::whereHas(
            'prescription',
            fn ($q) => $q->when(! $user->isAdmin(), fn ($q) => $q->where('uploaded_by', $user->id))
        )->avg('confidence_score');

        $recentPrescriptions = (clone $base)
            ->with(['uploader', 'extraction'])
            ->latest('uploaded_at')
            ->limit(10)
            ->get();

        return response()->json([
            'total_prescriptions'  => $totalPrescriptions,
            'verified'             => $verifiedCount,
            'rejected'             => $rejectedCount,
            'pending_review'       => $pendingReviewCount,
            'processing'           => $processingCount,
            'avg_confidence_score' => round((float) $avgConfidence, 3),
            'recent_prescriptions' => $recentPrescriptions->map(fn ($p) => [
                'id'                  => $p->id,
                'prescription_number' => $p->prescription_number,
                'status'              => $p->status,
                'drug_name'           => $p->extraction?->drug_name,
                'confidence_score'    => $p->extraction?->confidence_score,
                'uploaded_at'         => $p->uploaded_at,
            ]),
        ]);
    }

    public function corrections(Request $request): JsonResponse
    {
        $user = $request->user();

        $mostCorrectedFields = PrescriptionCorrection::select('field_name', DB::raw('count(*) as correction_count'))
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas(
                'prescription',
                fn ($q) => $q->where('uploaded_by', $user->id)
            ))
            ->groupBy('field_name')
            ->orderByDesc('correction_count')
            ->get();

        $mostCorrectedDrugs = PrescriptionCorrection::select('predicted_value', DB::raw('count(*) as correction_count'))
            ->where('field_name', 'drug_name')
            ->whereNotNull('predicted_value')
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas(
                'prescription',
                fn ($q) => $q->where('uploaded_by', $user->id)
            ))
            ->groupBy('predicted_value')
            ->orderByDesc('correction_count')
            ->limit(10)
            ->get();

        $totalCorrections = PrescriptionCorrection::when(
            ! $user->isAdmin(),
            fn ($q) => $q->whereHas('prescription', fn ($q) => $q->where('uploaded_by', $user->id))
        )->count();

        $totalReviewed = Prescription::where('status', 'VERIFIED')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('uploaded_by', $user->id))
            ->count();

        return response()->json([
            'total_corrections'     => $totalCorrections,
            'total_reviewed'        => $totalReviewed,
            'correction_rate'       => $totalReviewed > 0
                ? round($totalCorrections / $totalReviewed, 3)
                : 0,
            'by_field'              => $mostCorrectedFields,
            'most_corrected_drugs'  => $mostCorrectedDrugs,
        ]);
    }

    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();

        $dailyVolume = Prescription::select(
            DB::raw('DATE(uploaded_at) as date'),
            DB::raw('count(*) as count')
        )
            ->when(! $user->isAdmin(), fn ($q) => $q->where('uploaded_by', $user->id))
            ->when($request->days, fn ($q, $days) => $q->where('uploaded_at', '>=', now()->subDays((int) $days)))
            ->groupBy(DB::raw('DATE(uploaded_at)'))
            ->orderBy('date')
            ->get();

        $monthlyVolume = Prescription::select(
            DB::raw("DATE_FORMAT(uploaded_at, '%Y-%m') as month"),
            DB::raw('count(*) as count')
        )
            ->when(! $user->isAdmin(), fn ($q) => $q->where('uploaded_by', $user->id))
            ->where('uploaded_at', '>=', now()->subMonths(12))
            ->groupBy(DB::raw("DATE_FORMAT(uploaded_at, '%Y-%m')"))
            ->orderBy('month')
            ->get();

        $mostCommonDrugs = PrescriptionExtraction::select('drug_name', DB::raw('count(*) as count'))
            ->whereNotNull('drug_name')
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas(
                'prescription',
                fn ($q) => $q->where('uploaded_by', $user->id)
            ))
            ->groupBy('drug_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'daily_volume'       => $dailyVolume,
            'monthly_volume'     => $monthlyVolume,
            'most_common_drugs'  => $mostCommonDrugs,
        ]);
    }
}
