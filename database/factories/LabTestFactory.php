<?php

namespace Database\Factories;

use App\Models\LabTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabTest>
 */
class LabTestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Complete Blood Count', 'Blood Glucose', 'Lipid Profile', 'Liver Function Test']),
            'code' => 'LAB'.fake()->unique()->numerify('####'),
            'sample_type' => 'blood',
            'unit' => 'mg/dL',
            'reference_range' => '70-100',
            'price' => fake()->randomFloat(2, 5, 50),
            'is_active' => true,
        ];
    }
}
