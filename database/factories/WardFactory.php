<?php

namespace Database\Factories;

use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['General Ward', 'ICU', 'Maternity Ward', 'Pediatric Ward']),
            'floor' => (string) fake()->numberBetween(1, 5),
            'ward_type' => 'general',
        ];
    }
}
