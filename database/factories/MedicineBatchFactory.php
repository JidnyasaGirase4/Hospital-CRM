<?php

namespace Database\Factories;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicineBatch>
 */
class MedicineBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'medicine_id' => Medicine::factory(),
            'batch_number' => 'B'.fake()->unique()->numerify('######'),
            'quantity' => fake()->numberBetween(50, 200),
            'purchase_price' => fake()->randomFloat(2, 1, 50),
            'selling_price' => fake()->randomFloat(2, 2, 60),
            'mrp' => fake()->randomFloat(2, 3, 70),
            'expiry_date' => fake()->dateTimeBetween('+6 months', '+2 years'),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expiry_date' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }
}
