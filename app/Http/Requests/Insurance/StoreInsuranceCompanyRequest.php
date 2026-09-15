<?php

namespace App\Http\Requests\Insurance;

use App\Models\InsurancePolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreInsuranceCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InsurancePolicy::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ];
    }
}
