<?php

namespace Database\Factories;

use App\Models\EmergencyVisit;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyVisit>
 */
class EmergencyVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'status' => 'registered',
            'registered_at' => now(),
        ];
    }
}
