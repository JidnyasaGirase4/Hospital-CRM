<?php

namespace App\Http\Requests\Insurance;

use App\Models\InsurancePolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreInsurancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InsurancePolicy::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'insurance_company_id' => ['required', 'exists:insurance_companies,id'],
            'policy_number' => ['required', 'string', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_till' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'coverage_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
