<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parameter_name' => $this->parameter_name,
            'result_value' => $this->result_value,
            'unit' => $this->unit,
            'reference_range' => $this->reference_range,
            'flag' => $this->flag,
            'is_approved' => $this->is_approved,
            'approved_at' => $this->approved_at,
        ];
    }
}
