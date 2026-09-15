<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bill_number' => $this->bill_number,
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'name' => $this->patient->fullName(),
            ]),
            'type' => $this->type,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'outstanding_amount' => $this->outstandingAmount(),
            'status' => $this->status,
            'items' => BillItemResource::collection($this->whenLoaded('items')),
            'discounts' => DiscountResource::collection($this->whenLoaded('discounts')),
            'created_at' => $this->created_at,
        ];
    }
}
