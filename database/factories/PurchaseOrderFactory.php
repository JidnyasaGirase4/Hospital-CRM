<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'po_number' => 'PO'.fake()->unique()->numerify('######'),
            'supplier_id' => Supplier::factory(),
            'order_date' => now()->toDateString(),
            'status' => 'draft',
        ];
    }
}
