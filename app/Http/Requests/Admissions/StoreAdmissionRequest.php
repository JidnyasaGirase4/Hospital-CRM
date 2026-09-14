<?php

namespace App\Http\Requests\Admissions;

use App\Models\Admission;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Admission::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'admission_type' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string'],
            'bed_id' => ['nullable', 'exists:beds,id'],
        ];
    }
}
