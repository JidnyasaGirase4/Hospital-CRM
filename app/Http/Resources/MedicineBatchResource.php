<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'quantity' => $this->quantity,
            'purchase_price' => $this->purchase_price,
            'selling_price' => $this->selling_price,
            'mrp' => $this->mrp,
            'manufactured_date' => $this->manufactured_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'is_expired' => $this->isExpired(),
        ];
    }
}
