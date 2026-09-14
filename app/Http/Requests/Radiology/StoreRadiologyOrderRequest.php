<?php

namespace App\Http\Requests\Radiology;

use App\Models\RadiologyOrder;
use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RadiologyOrder::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'radiology_test_id' => ['required', 'exists:radiology_tests,id'],
            'consultation_id' => ['nullable', 'exists:consultations,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
