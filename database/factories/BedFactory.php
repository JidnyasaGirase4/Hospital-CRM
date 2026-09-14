<?php

namespace Database\Factories;

use App\Models\Bed;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bed>
 */
class BedFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'bed_number' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'status' => 'available',
        ];
    }
}
