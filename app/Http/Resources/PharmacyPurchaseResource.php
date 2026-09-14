<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_number' => $this->purchase_number,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'invoice_number' => $this->invoice_number,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'items' => PharmacyPurchaseItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
