<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "critical rule" from the spec: dispensing must deduct stock correctly
 * inside a transaction, and stock must never go negative unless the
 * medicine explicitly opts in via allow_negative_stock.
 */
class PharmacySaleTest extends TestCase
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

    public function test_selling_deducts_stock_from_the_batch(): void
    {
        $this->actingAsPharmacist();

        $medicine = Medicine::factory()->create();
        $batch = MedicineBatch::factory()->create(['medicine_id' => $medicine->id, 'quantity' => 50]);

        $response = $this->postJson('/api/v1/pharmacy-sales', [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 20]],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('medicine_batches', ['id' => $batch->id, 'quantity' => 30]);
    }

    public function test_selling_allocates_fefo_across_multiple_batches_when_one_is_insufficient(): void
    {
        $this->actingAsPharmacist();

        $medicine = Medicine::factory()->create();

        $expiringSoon = MedicineBatch::factory()->create([
            'medicine_id' => $medicine->id,
            'quantity' => 10,
            'expiry_date' => now()->addMonths(2),
        ]);
        $expiringLater = MedicineBatch::factory()->create([
            'medicine_id' => $medicine->id,
            'quantity' => 50,
            'expiry_date' => now()->addYear(),
        ]);

        $response = $this->postJson('/api/v1/pharmacy-sales', [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 15]],
        ]);

        $response->assertCreated()->assertJsonCount(2, 'data.items');

        // The nearer-expiry batch is drained first (FEFO), the remainder
        // comes from the later-expiry batch.
        $this->assertDatabaseHas('medicine_batches', ['id' => $expiringSoon->id, 'quantity' => 0]);
        $this->assertDatabaseHas('medicine_batches', ['id' => $expiringLater->id, 'quantity' => 45]);
    }

    public function test_expired_batches_are_never_allocated(): void
    {
        $this->actingAsPharmacist();

        $medicine = Medicine::factory()->create();
        MedicineBatch::factory()->expired()->create(['medicine_id' => $medicine->id, 'quantity' => 100]);

        $response = $this->postJson('/api/v1/pharmacy-sales', [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 5]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['items']);
    }

    public function test_selling_more_than_available_stock_is_rejected_and_deducts_nothing(): void
    {
        $this->actingAsPharmacist();

        $medicine = Medicine::factory()->create(['allow_negative_stock' => false]);
        $batch = MedicineBatch::factory()->create(['medicine_id' => $medicine->id, 'quantity' => 5]);

        $response = $this->postJson('/api/v1/pharmacy-sales', [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 50]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['items']);

        // Transaction rolled back: stock is untouched, no sale/items persisted.
        $this->assertDatabaseHas('medicine_batches', ['id' => $batch->id, 'quantity' => 5]);
        $this->assertDatabaseCount('pharmacy_sales', 0);
    }

    public function test_negative_stock_is_permitted_when_medicine_explicitly_allows_it(): void
    {
        $this->actingAsPharmacist();

        $medicine = Medicine::factory()->create(['allow_negative_stock' => true]);
        $batch = MedicineBatch::factory()->create(['medicine_id' => $medicine->id, 'quantity' => 5]);

        $response = $this->postJson('/api/v1/pharmacy-sales', [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 8]],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('medicine_batches', ['id' => $batch->id, 'quantity' => -3]);
    }

    public function test_dispensing_against_a_prescription_updates_dispensed_quantity_and_status(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);
        $this->actingAs($doctor, 'sanctum');

        $patient = Patient::factory()->create();
        $medicine = Medicine::factory()->create();
        MedicineBatch::factory()->create(['medicine_id' => $medicine->id, 'quantity' => 100]);

        $prescriptionId = $this->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 10]],
        ])->assertCreated()->json('data.id');

        $itemId = $this->getJson("/api/v1/prescriptions/{$prescriptionId}")->json('data.items.0.id');

        $this->actingAsPharmacist();

        $this->postJson('/api/v1/pharmacy-sales', [
            'patient_id' => $patient->id,
            'prescription_id' => $prescriptionId,
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 10, 'prescription_item_id' => $itemId]],
        ])->assertCreated();

        $this->assertDatabaseHas('prescription_items', ['id' => $itemId, 'dispensed_quantity' => 10]);
        $this->assertDatabaseHas('prescriptions', ['id' => $prescriptionId, 'status' => 'dispensed']);
    }

    public function test_partial_dispensing_marks_prescription_partially_dispensed(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);
        $this->actingAs($doctor, 'sanctum');

        $patient = Patient::factory()->create();
        $medicine = Medicine::factory()->create();
        MedicineBatch::factory()->create(['medicine_id' => $medicine->id, 'quantity' => 100]);

        $prescriptionId = $this->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 10]],
        ])->assertCreated()->json('data.id');

        $itemId = $this->getJson("/api/v1/prescriptions/{$prescriptionId}")->json('data.items.0.id');

        $this->actingAsPharmacist();

        $this->postJson('/api/v1/pharmacy-sales', [
            'prescription_id' => $prescriptionId,
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 4, 'prescription_item_id' => $itemId]],
        ])->assertCreated();

        $this->assertDatabaseHas('prescriptions', ['id' => $prescriptionId, 'status' => 'partially-dispensed']);
    }

    public function test_sale_creates_a_pharmacy_bill_with_totals_and_can_be_returned(): void
    {
        $this->actingAsPharmacist();

        $medicine = Medicine::factory()->create();
        MedicineBatch::factory()->create([
            'medicine_id' => $medicine->id,
            'quantity' => 50,
            'selling_price' => 10,
        ]);

        $sale = $this->postJson('/api/v1/pharmacy-sales', [
            'discount_amount' => 5,
            'tax_amount' => 2,
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 4]],
        ])->assertCreated();

        $sale->assertJsonPath('data.total_amount', '40.00')
            ->assertJsonPath('data.net_amount', '37.00')
            ->assertJsonStructure(['data' => ['invoice_number']]);

        $saleId = $sale->json('data.id');
        $saleItemId = $sale->json('data.items.0.id');

        $this->postJson("/api/v1/pharmacy-sales/{$saleId}/returns", [
            'pharmacy_sale_item_id' => $saleItemId,
            'quantity' => 2,
            'reason' => 'Patient reaction',
        ])->assertCreated();

        $this->assertDatabaseHas('medicine_batches', ['medicine_id' => $medicine->id, 'quantity' => 48]);
    }
}
