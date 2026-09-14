<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' '.fake()->randomElement(['500mg', '250mg', '10mg', 'Syrup']),
            'generic_name' => fake()->word(),
            'manufacturer' => fake()->company(),
            'form' => fake()->randomElement(['tablet', 'syrup', 'injection', 'capsule']),
            'strength' => fake()->randomElement(['250mg', '500mg', '10ml']),
            'unit' => 'unit',
            'reorder_level' => 10,
            'allow_negative_stock' => false,
            'is_active' => true,
        ];
    }
}
