<?php

namespace App\Http\Requests\Ot;

use App\Models\OtSchedule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOtScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', OtSchedule::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'surgeon_id' => ['required', 'exists:users,id'],
            'admission_id' => ['nullable', 'exists:admissions,id'],
            'procedure_name' => ['required', 'string', 'max:255'],
            'ot_room' => ['nullable', 'string', 'max:100'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
