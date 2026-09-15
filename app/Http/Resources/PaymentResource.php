<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'bill_id' => $this->bill_id,
            'patient_id' => $this->patient_id,
            'amount' => $this->amount,
            'method' => $this->method,
            'reference_number' => $this->reference_number,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'refunded_amount' => $this->refundedAmount(),
            'paid_at' => $this->paid_at,
        ];
    }
}
