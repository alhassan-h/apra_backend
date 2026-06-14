<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'prescription_number' => $this->prescription_number,
            'image_url'           => $this->image_url,
            'status'              => $this->status,
            'uploaded_at'         => $this->uploaded_at,
            'created_at'          => $this->created_at,
            'uploader'            => new UserResource($this->whenLoaded('uploader')),
            'extraction'          => new ExtractionResource($this->whenLoaded('extraction')),
            'review'              => new ReviewResource($this->whenLoaded('review')),
        ];
    }
}
