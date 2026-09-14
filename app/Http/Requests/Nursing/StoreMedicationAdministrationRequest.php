<?php

namespace App\Http\Requests\Nursing;

use App\Models\MedicationAdministration;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationAdministrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MedicationAdministration::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'prescription_item_id' => ['nullable', 'exists:prescription_items,id'],
            'medicine_id' => ['required', 'exists:medicines,id'],
            'dose_given' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
