<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'sample_type' => $this->sample_type,
            'unit' => $this->unit,
            'reference_range' => $this->reference_range,
            'price' => $this->price,
            'is_active' => $this->is_active,
        ];
    }
}
