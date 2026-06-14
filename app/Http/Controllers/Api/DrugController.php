<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drug\StoreDrugRequest;
use App\Http\Requests\Drug\UpdateDrugRequest;
use App\Http\Resources\DrugResource;
use App\Models\DrugDictionary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DrugController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $drugs = DrugDictionary::query()
            ->when($request->search, fn ($q, $s) => $q->where('generic_name', 'like', "%{$s}%")
                ->orWhere('brand_name', 'like', "%{$s}%"))
            ->when($request->has('active'), fn ($q) => $q->where('is_active', (bool) $request->active))
            ->orderBy('generic_name')
            ->paginate($request->integer('per_page', 50));

        return DrugResource::collection($drugs);
    }

    public function store(StoreDrugRequest $request): JsonResponse
    {
        $this->authorize('create', DrugDictionary::class);

        $drug = DrugDictionary::create($request->validated());

        return response()->json(new DrugResource($drug), 201);
    }

    public function update(UpdateDrugRequest $request, DrugDictionary $drug): DrugResource
    {
        $this->authorize('update', $drug);

        $drug->update($request->validated());

        return new DrugResource($drug);
    }

    public function destroy(DrugDictionary $drug): JsonResponse
    {
        $this->authorize('delete', $drug);

        $drug->delete();

        return response()->json(['message' => 'Drug removed from dictionary.']);
    }
}
