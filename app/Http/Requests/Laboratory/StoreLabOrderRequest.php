<?php

namespace App\Http\Requests\Laboratory;

use App\Models\LabOrder;
use Illuminate\Foundation\Http\FormRequest;

class StoreLabOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LabOrder::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'consultation_id' => ['nullable', 'exists:consultations,id'],
            'notes' => ['nullable', 'string'],
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['exists:lab_tests,id'],
        ];
    }
}
