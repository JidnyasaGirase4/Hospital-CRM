<?php

namespace App\Http\Requests\OpdVisits;

use App\Models\OpdVisit;
use Illuminate\Foundation\Http\FormRequest;

class StoreOpdVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', OpdVisit::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'visit_date' => ['nullable', 'date'],
            'symptoms' => ['nullable', 'string'],
            'vitals' => ['nullable', 'array'],
            'vitals.height_cm' => ['nullable', 'numeric'],
            'vitals.weight_kg' => ['nullable', 'numeric'],
            'vitals.bp_systolic' => ['nullable', 'integer'],
            'vitals.bp_diastolic' => ['nullable', 'integer'],
            'vitals.pulse' => ['nullable', 'integer'],
            'vitals.temperature_c' => ['nullable', 'numeric'],
            'vitals.spo2' => ['nullable', 'integer'],
            'vitals.respiratory_rate' => ['nullable', 'integer'],
            'diagnosis' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'visit_date' => $this->input('visit_date', now()->toDateTimeString()),
        ]);
    }
}
