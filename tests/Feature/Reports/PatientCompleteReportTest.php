<?php

namespace Tests\Feature\Reports;

use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Consultation;
use App\Models\Diagnosis;
use App\Models\EmergencyVisit;
use App\Models\InsuranceClaim;
use App\Models\InsurancePolicy;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\MedicineBatch;
use App\Models\OtSchedule;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\RadiologyOrder;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientCompleteReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_report_combines_icu_stay_diagnosis_bills_and_totals(): void
    {
        $this->actingAsRole(Role::HOSPITAL_ADMIN);

        $patient = Patient::factory()->create();
        $other = Patient::factory()->create();
        $doctor = User::factory()->create();

        $ward = Ward::factory()->create(['name' => 'ICU', 'ward_type' => 'ICU']);
        $room = Room::factory()->create(['ward_id' => $ward->id, 'daily_rate' => 5000]);
        $bed = Bed::factory()->create(['room_id' => $room->id, 'status' => 'available']);

        $admissionId = $this->postJson('/api/v1/admissions', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'bed_id' => $bed->id,
            'reason' => 'Cardiac arrest',
        ])->assertCreated()->json('data.id');
        BedAllocation::where('admission_id', $admissionId)->update(['allocated_at' => now()->subDays(2)]);
        $this->patchJson("/api/v1/admissions/{$admissionId}/discharge", ['discharge_summary' => 'Stable.'])->assertOk();
        $this->postJson("/api/v1/admissions/{$admissionId}/final-bill")->assertCreated();

        Diagnosis::factory()->create(['patient_id' => $patient->id, 'diagnosis_name' => 'Myocardial infarction']);

        $cancelled = $this->postJson('/api/v1/bills', [
            'patient_id' => $patient->id,
            'type' => 'opd',
            'items' => [['description' => 'Dressing', 'quantity' => 1, 'unit_price' => 100]],
        ])->json('data.id');
        $this->patchJson("/api/v1/bills/{$cancelled}/cancel")->assertOk();

        $this->postJson('/api/v1/bills', [
            'patient_id' => $other->id,
            'type' => 'opd',
            'items' => [['description' => 'Someone else', 'quantity' => 1, 'unit_price' => 999]],
        ]);

        $this->getJson("/api/v1/patients/{$patient->id}/complete-report")
            ->assertOk()
            ->assertJsonPath('data.patient.id', $patient->id)
            ->assertJsonPath('data.admissions.0.is_icu', true)
            ->assertJsonPath('data.admissions.0.reason', 'Cardiac arrest')
            ->assertJsonPath('data.admissions.0.stays.0.days', 2)
            ->assertJsonPath('data.admissions.0.room_total', '10000.00')
            ->assertJsonPath('data.diagnoses.0.name', 'Myocardial infarction')
            ->assertJsonCount(2, 'data.bills')
            ->assertJsonPath('data.totals.bills_count', 2)
            ->assertJsonPath('data.totals.cancelled_count', 1)
            ->assertJsonPath('data.totals.total_amount', '10000.00')
            ->assertJsonPath('data.totals.outstanding_amount', '10000.00')
            ->assertJsonPath('data.charges_by_category.0.category', 'room');
    }

    public function test_report_includes_medicines_lab_radiology_ot_emergency_and_clinical_sections(): void
    {
        $this->actingAsRole(Role::HOSPITAL_ADMIN);
        $patient = Patient::factory()->create();

        $batch = MedicineBatch::factory()->create();
        $sale = PharmacySale::create([
            'invoice_number' => 'PH-1', 'patient_id' => $patient->id, 'total_amount' => 90, 'discount_amount' => 0,
            'tax_amount' => 0, 'net_amount' => 90, 'payment_status' => 'paid', 'status' => 'completed',
        ]);
        PharmacySaleItem::create([
            'pharmacy_sale_id' => $sale->id, 'medicine_batch_id' => $batch->id, 'quantity' => 3, 'unit_price' => 30, 'total_price' => 90,
        ]);

        $test = LabTest::factory()->create(['name' => 'CBC', 'price' => 300]);
        $labOrder = LabOrder::factory()->create(['patient_id' => $patient->id]);
        $item = LabOrderItem::create(['lab_order_id' => $labOrder->id, 'lab_test_id' => $test->id, 'status' => 'approved']);
        LabResult::create(['lab_order_item_id' => $item->id, 'parameter_name' => 'Hb', 'result_value' => '13', 'unit' => 'g/dL', 'is_approved' => true]);
        LabResult::create(['lab_order_item_id' => $item->id, 'parameter_name' => 'Unapproved', 'result_value' => '1', 'is_approved' => false]);

        RadiologyOrder::factory()->create(['patient_id' => $patient->id]);
        OtSchedule::factory()->create(['patient_id' => $patient->id, 'procedure_name' => 'Angioplasty']);
        EmergencyVisit::factory()->create(['patient_id' => $patient->id, 'chief_complaint' => 'Chest pain']);
        Consultation::factory()->create(['patient_id' => $patient->id, 'chief_complaint' => 'Breathlessness']);
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);
        PrescriptionItem::create([
            'prescription_id' => $prescription->id, 'medicine_id' => $batch->medicine_id, 'dosage' => '1 tab', 'frequency' => 'BD', 'quantity' => 10,
        ]);

        $policy = InsurancePolicy::factory()->create(['patient_id' => $patient->id]);
        InsuranceClaim::create([
            'claim_number' => 'CLM-1', 'insurance_policy_id' => $policy->id, 'status' => 'submitted', 'requested_amount' => 1000,
        ]);

        $this->getJson("/api/v1/patients/{$patient->id}/complete-report")
            ->assertOk()
            ->assertJsonPath('data.pharmacy_sales.0.net_amount', '90.00')
            ->assertJsonPath('data.pharmacy_sales.0.items.0.quantity', 3)
            ->assertJsonPath('data.lab_orders.0.items.0.test', 'CBC')
            ->assertJsonCount(1, 'data.lab_orders.0.items.0.results')
            ->assertJsonCount(1, 'data.radiology_orders')
            ->assertJsonPath('data.ot_schedules.0.procedure_name', 'Angioplasty')
            ->assertJsonPath('data.emergency_visits.0.chief_complaint', 'Chest pain')
            ->assertJsonPath('data.consultations.0.chief_complaint', 'Breathlessness')
            ->assertJsonPath('data.prescriptions.0.items.0.dosage', '1 tab')
            ->assertJsonPath('data.insurance_claims.0.claim_number', 'CLM-1');
    }

    public function test_sections_the_user_cannot_view_are_omitted(): void
    {
        $this->actingAsRole(Role::BILLING_STAFF);
        $patient = Patient::factory()->create();

        $this->postJson('/api/v1/bills', [
            'patient_id' => $patient->id,
            'type' => 'opd',
            'items' => [['description' => 'Consultation', 'quantity' => 1, 'unit_price' => 500]],
        ])->assertCreated();

        $this->getJson("/api/v1/patients/{$patient->id}/complete-report")
            ->assertOk()
            ->assertJsonPath('data.totals.total_amount', '500.00')
            ->assertJsonPath('data.admissions', null)
            ->assertJsonPath('data.diagnoses', null)
            ->assertJsonPath('data.lab_orders', null);
    }

    public function test_report_requires_patient_view_permission(): void
    {
        $this->actingAsRole(Role::PHARMACIST);
        $this->getJson('/api/v1/patients/'.Patient::factory()->create()->id.'/complete-report')->assertOk();

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $this->getJson('/api/v1/patients/'.Patient::factory()->create()->id.'/complete-report')->assertForbidden();
    }
}
