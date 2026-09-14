<?php

namespace Database\Factories;

use App\Models\OtSchedule;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtSchedule>
 */
class OtScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'surgeon_id' => User::factory(),
            'procedure_name' => fake()->randomElement(['Appendectomy', 'Cesarean Section', 'Knee Replacement']),
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
            'status' => 'scheduled',
        ];
    }
}
