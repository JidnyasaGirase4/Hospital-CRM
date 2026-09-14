<?php

namespace App\Http\Requests\Beds;

use App\Models\Bed;
use Illuminate\Foundation\Http\FormRequest;

class StoreBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Bed::class);
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'bed_number' => ['required', 'string', 'max:50'],
        ];
    }
}
