<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prescription\UploadPrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Jobs\ProcessPrescriptionJob;
use App\Models\Prescription;
use App\Notifications\PrescriptionUploadedNotification;
use App\Services\AuditService;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class PrescriptionController extends Controller
{
    public function __construct(
        private readonly CloudinaryService $cloudinary,
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Prescription::with(['uploader', 'extraction', 'review'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('uploaded_by', $user->id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, fn ($q, $search) => $q->where('prescription_number', 'like', "%{$search}%"))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('uploaded_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('uploaded_at', '<=', $date))
            ->latest('uploaded_at');

        return PrescriptionResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function store(UploadPrescriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        $imageUrl = $this->cloudinary->uploadPrescription($request->file('image'));

        $prescription = Prescription::create([
            'prescription_number' => 'RX-' . strtoupper(Str::random(8)),
            'image_url'           => $imageUrl,
            'uploaded_by'         => $user->id,
            'status'              => 'UPLOADED',
            'uploaded_at'         => now(),
        ]);

        ProcessPrescriptionJob::dispatch($prescription);

        $user->notify(new PrescriptionUploadedNotification($prescription));

        $this->auditService->log(
            'PRESCRIPTION_UPLOADED',
            user: $user,
            entity: $prescription,
            metadata: ['prescription_number' => $prescription->prescription_number],
        );

        return response()->json(new PrescriptionResource($prescription->load('uploader')), 201);
    }

    public function show(Prescription $prescription, Request $request): PrescriptionResource
    {
        $this->authorize('view', $prescription);

        return new PrescriptionResource(
            $prescription->load(['uploader', 'extraction', 'review.reviewer'])
        );
    }

    public function destroy(Prescription $prescription, Request $request): JsonResponse
    {
        $this->authorize('delete', $prescription);

        $this->auditService->log(
            'PRESCRIPTION_DELETED',
            user: $request->user(),
            entity: $prescription,
            metadata: ['prescription_number' => $prescription->prescription_number],
        );

        $prescription->delete();

        return response()->json(['message' => 'Prescription deleted.']);
    }
}
