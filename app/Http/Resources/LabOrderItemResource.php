<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test' => $this->whenLoaded('test', fn () => [
                'id' => $this->test->id,
                'name' => $this->test->name,
                'code' => $this->test->code,
            ]),
            'status' => $this->status,
            'sample_collected_at' => $this->sample_collected_at,
            'results' => LabResultResource::collection($this->whenLoaded('results')),
        ];
    }
}
