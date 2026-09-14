<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RadiologyReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'findings' => $this->findings,
            'impression' => $this->impression,
            'reported_at' => $this->reported_at,
            'is_approved' => $this->is_approved,
            'approved_at' => $this->approved_at,
        ];
    }
}
