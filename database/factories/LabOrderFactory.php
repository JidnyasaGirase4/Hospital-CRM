<?php

namespace Database\Factories;

use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabOrder>
 */
class LabOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'status' => 'ordered',
            'ordered_at' => now(),
        ];
    }
}
