<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ward_id' => $this->ward_id,
            'room_number' => $this->room_number,
            'room_type' => $this->room_type,
            'beds' => BedResource::collection($this->whenLoaded('beds')),
        ];
    }
}
