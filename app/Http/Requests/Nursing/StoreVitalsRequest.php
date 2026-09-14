<?php

namespace App\Http\Requests\Nursing;

use App\Models\PatientVital;
use Illuminate\Foundation\Http\FormRequest;

class StoreVitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PatientVital::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'temperature_c' => ['nullable', 'numeric', 'between:30,45'],
            'pulse' => ['nullable', 'integer', 'min:0', 'max:300'],
            'bp_systolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'bp_diastolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
