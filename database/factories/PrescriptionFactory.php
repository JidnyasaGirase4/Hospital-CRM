<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'status' => 'active',
        ];
    }
}
