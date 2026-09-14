<?php

namespace App\Http\Requests\Diagnoses;

use App\Models\Diagnosis;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Diagnosis::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'consultation_id' => ['nullable', 'exists:consultations,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'diagnosis_code' => ['nullable', 'string', 'max:50'],
            'diagnosis_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'diagnosed_at' => ['nullable', 'date'],
        ];
    }
}
