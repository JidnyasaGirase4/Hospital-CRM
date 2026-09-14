<?php

namespace App\Http\Requests\Prescriptions;

use App\Models\Prescription;
use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Prescription::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'consultation_id' => ['nullable', 'exists:consultations,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'exists:medicines,id'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.frequency' => ['nullable', 'string', 'max:100'],
            'items.*.route' => ['nullable', 'string', 'max:50'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
            'items.*.timing' => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
