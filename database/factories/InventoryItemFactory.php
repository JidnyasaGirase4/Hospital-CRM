<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Surgical Gloves', 'Syringes', 'IV Cannula', 'Gauze Rolls', 'Face Masks']),
            'category' => 'consumable',
            'unit' => 'box',
            'reorder_level' => 10,
            'is_active' => true,
        ];
    }
}
