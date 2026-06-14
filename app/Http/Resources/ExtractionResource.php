<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtractionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'prescription_id'  => $this->prescription_id,
            'model_name'       => $this->model_name,
            'model_version'    => $this->model_version,
            'drugs'            => $this->drugs ?? [],
            'confidence_score' => $this->confidence_score,
            'created_at'       => $this->created_at,
        ];
    }
}
