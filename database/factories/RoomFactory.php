<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ward_id' => Ward::factory(),
            'room_number' => (string) fake()->unique()->numberBetween(100, 999),
            'room_type' => 'general',
        ];
    }
}
