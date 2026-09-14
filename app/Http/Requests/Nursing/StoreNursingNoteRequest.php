<?php

namespace App\Http\Requests\Nursing;

use App\Models\NursingNote;
use Illuminate\Foundation\Http\FormRequest;

class StoreNursingNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', NursingNote::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'type' => ['nullable', 'in:general,intake-output,care-plan,shift-handover'],
            'note' => ['required', 'string'],
            'shift' => ['nullable', 'in:morning,evening,night'],
        ];
    }
}
