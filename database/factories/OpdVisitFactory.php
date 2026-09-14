<?php

namespace Database\Factories;

use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpdVisit>
 */
class OpdVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'visit_date' => now(),
            'symptoms' => fake()->sentence(),
            'vitals' => [
                'bp_systolic' => fake()->numberBetween(100, 140),
                'bp_diastolic' => fake()->numberBetween(60, 90),
                'pulse' => fake()->numberBetween(60, 100),
                'temperature_c' => fake()->randomFloat(1, 36, 38),
            ],
            'status' => 'open',
        ];
    }
}
