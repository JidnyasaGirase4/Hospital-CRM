<?php

namespace Database\Factories;

use App\Models\RadiologyTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiologyTest>
 */
class RadiologyTestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Chest X-Ray', 'CT Brain', 'MRI Spine', 'Abdominal Ultrasound']),
            'code' => 'RAD'.fake()->unique()->numerify('####'),
            'modality' => fake()->randomElement(['X-Ray', 'CT', 'MRI', 'Ultrasound']),
            'price' => fake()->randomFloat(2, 20, 500),
            'is_active' => true,
        ];
    }
}
