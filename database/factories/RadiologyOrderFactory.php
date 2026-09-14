<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\RadiologyOrder;
use App\Models\RadiologyTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiologyOrder>
 */
class RadiologyOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'radiology_test_id' => RadiologyTest::factory(),
            'status' => 'ordered',
            'ordered_at' => now(),
        ];
    }
}
