<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'prescription_id' => $this->prescription_id,
            'drugs'           => $this->drugs ?? [],
            'review_status'   => $this->review_status,
            'review_notes'    => $this->review_notes,
            'reviewer'        => new UserResource($this->whenLoaded('reviewer')),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
