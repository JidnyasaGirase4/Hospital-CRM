<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'title' => $this->title,
            'category' => $this->category,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => $this->uploadedBy?->only(['id', 'name'])),
            'created_at' => $this->created_at,
        ];
    }
}
