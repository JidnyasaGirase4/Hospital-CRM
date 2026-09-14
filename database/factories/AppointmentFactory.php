<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
            'duration_minutes' => 15,
            'type' => 'new',
            'status' => 'scheduled',
        ];
    }
}
