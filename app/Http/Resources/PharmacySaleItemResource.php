<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacySaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medicine' => $this->whenLoaded('batch', fn () => [
                'id' => $this->batch->medicine->id,
                'name' => $this->batch->medicine->name,
            ]),
            'batch_number' => $this->whenLoaded('batch', fn () => $this->batch->batch_number),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
        ];
    }
}
