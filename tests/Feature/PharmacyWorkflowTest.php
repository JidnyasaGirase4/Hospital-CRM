<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The golden-path Pharmacy flow named in the README's Phase 10 test plan:
 * prescription -> dispensing -> stock deduction -> pharmacy bill.
 */
class PharmacyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_full_pharmacy_flow_from_prescription_to_paid_bill(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);

        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);

        $patient = Patient::factory()->create();
        $medicine = Medicine::factory()->create();
        $batch = MedicineBatch::factory()->create([
            'medicine_id' => $medicine->id,
            'quantity' => 100,
            'selling_price' => 15,
        ]);

        // 1. Doctor writes the prescription.
        $this->actingAs($doctor, 'sanctum');
        $prescriptionResponse = $this->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'items' => [[
                'medicine_id' => $medicine->id,
                'dosage' => '500mg',
                'frequency' => '1-0-1',
                'duration' => '5 days',
                'quantity' => 10,
            ]],
        ])->assertCreated();

        $prescriptionId = $prescriptionResponse->json('data.id');
        $itemId = $prescriptionResponse->json('data.items.0.id');

        // 2. Pharmacist dispenses against the prescription.
        $this->actingAs($pharmacist, 'sanctum');
        $saleResponse = $this->postJson('/api/v1/pharmacy-sales', [
            'patient_id' => $patient->id,
            'prescription_id' => $prescriptionId,
            'items' => [[
                'medicine_id' => $medicine->id,
                'quantity' => 10,
                'prescription_item_id' => $itemId,
            ]],
        ])->assertCreated();

        // 3. Stock is deducted correctly.
        $this->assertDatabaseHas('medicine_batches', ['id' => $batch->id, 'quantity' => 90]);

        // 4. Prescription is fully dispensed.
        $this->assertDatabaseHas('prescriptions', ['id' => $prescriptionId, 'status' => 'dispensed']);

        // 5. The pharmacy bill (sale) has correct totals - this is the
        // record that the future Billing module (Phase 7) will draw from.
        $saleResponse->assertJsonPath('data.total_amount', '150.00')
            ->assertJsonPath('data.net_amount', '150.00')
            ->assertJsonPath('data.status', 'completed');

        // 6. Everything is now visible on Patient 360 (doctor has 360 access).
        $this->actingAs($doctor, 'sanctum');
        $this->getJson("/api/v1/patients/{$patient->id}/360")
            ->assertOk()
            ->assertJsonCount(1, 'data.prescriptions')
            ->assertJsonCount(1, 'data.pharmacy_sales');
    }
}
