<?php

namespace App\Http\Requests\Admissions;

use App\Models\Bed;
use Illuminate\Foundation\Http\FormRequest;

class AllocateBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('allocate', Bed::class);
    }

    public function rules(): array
    {
        return [
            'bed_id' => ['required', 'exists:beds,id'],
        ];
    }
}
