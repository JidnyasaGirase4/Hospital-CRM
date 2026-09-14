<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Medicine;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsPharmacist(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::PHARMACIST);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_recording_a_purchase_creates_a_batch_and_increases_stock(): void
    {
        $this->actingAsPharmacist();

        $supplier = Supplier::factory()->create();
        $medicine = Medicine::factory()->create();

        $response = $this->postJson('/api/v1/pharmacy-purchases', [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'medicine_id' => $medicine->id,
                    'batch_number' => 'BATCH-001',
                    'quantity' => 100,
                    'unit_cost' => 5.50,
                    'selling_price' => 8.00,
                    'expiry_date' => now()->addYear()->toDateString(),
                ],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.total_amount', '550.00');

        $this->assertDatabaseHas('medicine_batches', [
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 100,
        ]);
    }

    public function test_purchasing_the_same_batch_again_tops_up_quantity_instead_of_duplicating(): void
    {
        $this->actingAsPharmacist();

        $supplier = Supplier::factory()->create();
        $medicine = Medicine::factory()->create();

        $payload = [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [[
                'medicine_id' => $medicine->id,
                'batch_number' => 'BATCH-XYZ',
                'quantity' => 50,
                'unit_cost' => 2,
                'expiry_date' => now()->addYear()->toDateString(),
            ]],
        ];

        $this->postJson('/api/v1/pharmacy-purchases', $payload)->assertCreated();
        $this->postJson('/api/v1/pharmacy-purchases', $payload)->assertCreated();

        $this->assertDatabaseCount('medicine_batches', 1);
        $this->assertDatabaseHas('medicine_batches', [
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-XYZ',
            'quantity' => 100,
        ]);
    }

    public function test_receptionist_cannot_record_a_pharmacy_purchase(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist, 'sanctum');

        $this->postJson('/api/v1/pharmacy-purchases', [
            'supplier_id' => Supplier::factory()->create()->id,
            'purchase_date' => now()->toDateString(),
            'items' => [],
        ])->assertStatus(403);
    }
}
