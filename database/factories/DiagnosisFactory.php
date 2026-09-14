<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Diagnosis>
 */
class DiagnosisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'diagnosis_name' => fake()->randomElement(['Hypertension', 'Type 2 Diabetes', 'Pharyngitis', 'Migraine']),
            'diagnosed_at' => now(),
        ];
    }
}
