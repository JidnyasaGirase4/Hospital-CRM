<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyPurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medicine' => $this->whenLoaded('medicine', fn () => [
                'id' => $this->medicine->id,
                'name' => $this->medicine->name,
            ]),
            'batch_number' => $this->batch_number,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'expiry_date' => $this->expiry_date?->toDateString(),
        ];
    }
}
