<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bill_number' => 'INV'.fake()->unique()->numerify('######'),
            'patient_id' => Patient::factory(),
            'type' => 'other',
            'status' => 'unpaid',
        ];
    }
}
