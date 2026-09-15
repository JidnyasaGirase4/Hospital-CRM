<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'title' => fake()->sentence(3),
            'category' => 'other',
            'disk' => 'local',
            'file_path' => 'documents/fake/'.fake()->uuid().'.pdf',
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
