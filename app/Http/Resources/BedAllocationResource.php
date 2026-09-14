<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BedAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bed' => $this->whenLoaded('bed', fn () => [
                'id' => $this->bed->id,
                'bed_number' => $this->bed->bed_number,
                'status' => $this->bed->status,
            ]),
            'allocated_at' => $this->allocated_at,
            'released_at' => $this->released_at,
        ];
    }
}
