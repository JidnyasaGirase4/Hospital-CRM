<?php

namespace App\Http\Requests\Insurance;

use App\Models\InsuranceClaim;
use Illuminate\Foundation\Http\FormRequest;

class StoreInsuranceClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InsuranceClaim::class);
    }

    public function rules(): array
    {
        return [
            'insurance_policy_id' => ['required', 'exists:insurance_policies,id'],
            'bill_id' => ['nullable', 'exists:bills,id'],
            'requested_amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
