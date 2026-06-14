<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'generic_name' => $this->generic_name,
            'brand_name'   => $this->brand_name,
            'aliases'      => $this->aliases ?? [],
            'nafdac_code'  => $this->nafdac_code,
            'is_active'    => $this->is_active,
            'created_at'   => $this->created_at,
        ];
    }
}
