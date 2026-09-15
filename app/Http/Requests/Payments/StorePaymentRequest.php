<?php

namespace App\Http\Requests\Payments;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    public function rules(): array
    {
        return [
            'bill_id' => ['nullable', 'exists:bills,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,card,upi,bank-transfer,cheque,insurance,tpa'],
            'reference_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}
