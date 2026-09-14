<?php

namespace App\Http\Requests\Beds;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Room::class);
    }

    public function rules(): array
    {
        return [
            'ward_id' => ['required', 'exists:wards,id'],
            'room_number' => ['required', 'string', 'max:50'],
            'room_type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
