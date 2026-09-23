<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\LabTest;
use App\Models\RadiologyTest;
use App\Models\Role;
use App\Models\User;
use App\Models\Ward;
use App\Services\AdmissionService;
use App\Services\AppointmentService;
use App\Services\BillingService;
use App\Services\ConsultationService;
use App\Services\DiagnosisService;
use App\Services\EmergencyService;
use App\Services\InsuranceService;
use App\Services\InventoryService;
use App\Services\LabService;
use App\Services\MedicineService;
use App\Services\OpdVisitService;
use App\Services\OtService;
use App\Services\PatientService;
use App\Services\PaymentService;
use App\Services\PharmacyService;
use App\Services\PrescriptionService;
use App\Services\PurchaseOrderService;
use App\Services\RadiologyService;
use App\Services\SupplierService;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Realistic sample data across every module so the dashboard, lists and
 * status badges have something real to show instead of empty states.
 * Not part of the versioned DatabaseSeeder (which only seeds roles +
 * the Super Admin) - run explicitly with:
 *   php artisan db:seed --class=DemoDataSeeder
 * Each section is independent and wrapped so one failure doesn't stop
 * the rest; re-running just adds more transactional records (bills,
 * appointments, etc.) rather than trying to be perfectly idempotent -
 * that's fine for demo data.
 */
class DemoDataSeeder extends Seeder
{
    private function step(string $label, callable $fn): void
    {
        try {
            $fn();
            $this->command?->info("  ✓ {$label}");
        } catch (Throwable $e) {
            $this->command?->error("  ✗ {$label}: {$e->getMessage()}");
        }
    }

    public function run(): void
    {
        $dept = fn (string $code) => Department::where('code', $code)->first()?->id;

        // --- Staff (7 users across 6 roles) -------------------------------
        $staff = [];
        $this->step('Staff users', function () use (&$staff, $dept) {
            $userService = app(UserService::class);
            $staffDefs = [
                ['employee_code' => 'EMP-D001', 'name' => 'Dr. Ananya Rao', 'email' => 'ananya.rao@hospital-crm.test', 'mobile' => '9812345101', 'department_id' => $dept('GEN-MED'), 'role' => Role::DOCTOR],
                ['employee_code' => 'EMP-D002', 'name' => 'Dr. Karan Malhotra', 'email' => 'karan.malhotra@hospital-crm.test', 'mobile' => '9812345102', 'department_id' => $dept('CARDIO'), 'role' => Role::DOCTOR],
                ['employee_code' => 'EMP-N001', 'name' => 'Kavita Joshi', 'email' => 'kavita.joshi@hospital-crm.test', 'mobile' => '9812345103', 'department_id' => $dept('GEN-MED'), 'role' => Role::NURSE],
                ['employee_code' => 'EMP-R001', 'name' => 'Rohan Desai', 'email' => 'rohan.desai@hospital-crm.test', 'mobile' => '9812345104', 'department_id' => $dept('ADMIN'), 'role' => Role::RECEPTIONIST],
                ['employee_code' => 'EMP-P001', 'name' => 'Meera Iyer', 'email' => 'meera.iyer@hospital-crm.test', 'mobile' => '9812345105', 'department_id' => $dept('PHARM'), 'role' => Role::PHARMACIST],
                ['employee_code' => 'EMP-L001', 'name' => 'Suresh Pillai', 'email' => 'suresh.pillai@hospital-crm.test', 'mobile' => '9812345106', 'department_id' => $dept('LAB'), 'role' => Role::LAB_TECHNICIAN],
                ['employee_code' => 'EMP-A001', 'name' => 'Anita Kapoor', 'email' => 'anita.kapoor@hospital-crm.test', 'mobile' => '9812345107', 'department_id' => $dept('ADMIN'), 'role' => Role::ACCOUNTANT],
            ];

            foreach ($staffDefs as $def) {
                $role = $def['role'];
                unset($def['role']);
                $user = User::where('email', $def['email'])->first();
                if (! $user) {
                    $user = $userService->create([...$def, 'password' => 'password']);
                }
                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                }
                $staff[$def['employee_code']] = $user;
            }
        });

        $doctor1 = $staff['EMP-D001'] ?? User::where('email', 'ananya.rao@hospital-crm.test')->first();
        $doctor2 = $staff['EMP-D002'] ?? User::where('email', 'karan.malhotra@hospital-crm.test')->first();

        // --- Patients (6) --------------------------------------------------
        $patients = [];
        $this->step('Patients', function () use (&$patients) {
            $patientService = app(PatientService::class);
            $patientDefs = [
                ['first_name' => 'Ramesh', 'last_name' => 'Kumar', 'dob' => '1985-04-12', 'gender' => 'male', 'mobile' => '9812345601', 'email' => 'ramesh.kumar@example.test', 'blood_group' => 'O+', 'city' => 'Pune', 'state' => 'Maharashtra', 'country' => 'India'],
                ['first_name' => 'Priya', 'last_name' => 'Sharma', 'dob' => '1990-08-23', 'gender' => 'female', 'mobile' => '9812345602', 'email' => 'priya.sharma@example.test', 'blood_group' => 'A+', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India'],
                ['first_name' => 'Arjun', 'last_name' => 'Mehta', 'dob' => '1978-01-15', 'gender' => 'male', 'mobile' => '9812345603', 'email' => 'arjun.mehta@example.test', 'blood_group' => 'B+', 'city' => 'Ahmedabad', 'state' => 'Gujarat', 'country' => 'India'],
                ['first_name' => 'Sunita', 'last_name' => 'Verma', 'dob' => '1995-11-02', 'gender' => 'female', 'mobile' => '9812345604', 'email' => 'sunita.verma@example.test', 'blood_group' => 'AB+', 'city' => 'Jaipur', 'state' => 'Rajasthan', 'country' => 'India'],
                ['first_name' => 'Vikram', 'last_name' => 'Singh', 'dob' => '1982-06-30', 'gender' => 'male', 'mobile' => '9812345605', 'email' => 'vikram.singh@example.test', 'blood_group' => 'O-', 'city' => 'Lucknow', 'state' => 'Uttar Pradesh', 'country' => 'India'],
                ['first_name' => 'Anjali', 'last_name' => 'Nair', 'dob' => '2000-03-18', 'gender' => 'female', 'mobile' => '9812345606', 'email' => 'anjali.nair@example.test', 'blood_group' => 'A-', 'city' => 'Kochi', 'state' => 'Kerala', 'country' => 'India'],
            ];

            foreach ($patientDefs as $def) {
                $existing = \App\Models\Patient::where('mobile', $def['mobile'])->first();
                $patients[] = $existing ?: $patientService->create($def);
            }
        });

        if (count($patients) < 6) {
            $patients = \App\Models\Patient::whereIn('mobile', ['9812345601', '9812345602', '9812345603', '9812345604', '9812345605', '9812345606'])->orderBy('mobile')->get()->all();
        }
        [$p1, $p2, $p3, $p4, $p5, $p6] = $patients;

        // --- Wards, Rooms, Beds ---------------------------------------------
        $this->step('Wards, rooms & beds', function () {
            if (Ward::where('name', 'General Ward')->exists()) {
                return;
            }
            $general = Ward::create(['name' => 'General Ward', 'floor' => '1', 'ward_type' => 'General']);
            $icu = Ward::create(['name' => 'ICU', 'floor' => '2', 'ward_type' => 'ICU']);

            $room101 = $general->rooms()->create(['room_number' => '101', 'room_type' => 'Twin Sharing']);
            $room102 = $general->rooms()->create(['room_number' => '102', 'room_type' => 'Single']);
            $roomIcu1 = $icu->rooms()->create(['room_number' => 'ICU-1', 'room_type' => 'ICU']);

            $room101->beds()->create(['bed_number' => '101-A']);
            $room101->beds()->create(['bed_number' => '101-B']);
            $room102->beds()->create(['bed_number' => '102-A']);
            $roomIcu1->beds()->create(['bed_number' => 'ICU-1-A']);
        });

        // --- Suppliers -------------------------------------------------------
        $supplier1 = \App\Models\Supplier::firstOrCreate(
            ['name' => 'MedLife Distributors'],
            ['contact_person' => 'Sanjay Gupta', 'phone' => '9900011122', 'email' => 'sales@medlife-distributors.test']
        );
        $supplier2 = \App\Models\Supplier::firstOrCreate(
            ['name' => 'Apollo Surgical Supplies'],
            ['contact_person' => 'Neha Bhatt', 'phone' => '9900011133', 'email' => 'orders@apollo-surgical.test']
        );

        // --- Medicines + stock via a purchase ---------------------------------
        $medicines = [];
        $this->step('Medicines & stock', function () use (&$medicines, $supplier1) {
            $medicineService = app(MedicineService::class);
            $category = \App\Models\MedicineCategory::firstOrCreate(['name' => 'General']);
            $medicineDefs = [
                ['name' => 'Paracetamol', 'strength' => '500mg', 'form' => 'Tablet', 'unit' => 'strip', 'reorder_level' => 20],
                ['name' => 'Amoxicillin', 'strength' => '250mg', 'form' => 'Capsule', 'unit' => 'strip', 'reorder_level' => 15],
                ['name' => 'Cetirizine', 'strength' => '10mg', 'form' => 'Tablet', 'unit' => 'strip', 'reorder_level' => 10],
                ['name' => 'Metformin', 'strength' => '500mg', 'form' => 'Tablet', 'unit' => 'strip', 'reorder_level' => 25],
                ['name' => 'Azithromycin', 'strength' => '500mg', 'form' => 'Tablet', 'unit' => 'strip', 'reorder_level' => 5],
            ];
            foreach ($medicineDefs as $def) {
                $medicine = \App\Models\Medicine::where('name', $def['name'])->first();
                if (! $medicine) {
                    $medicine = $medicineService->create([...$def, 'medicine_category_id' => $category->id]);
                }
                $medicines[] = $medicine;
            }

            if (\App\Models\MedicineBatch::whereIn('medicine_id', collect($medicines)->pluck('id'))->count() === 0) {
                app(PharmacyService::class)->purchase([
                    'supplier_id' => $supplier1->id,
                    'invoice_number' => 'SUP-INV-1001',
                    'purchase_date' => now()->subDays(20)->toDateString(),
                    'items' => collect($medicines)->map(fn ($m, $i) => [
                        'medicine_id' => $m->id,
                        'batch_number' => 'B'.strtoupper(substr(md5($m->name), 0, 6)),
                        'quantity' => $i === 4 ? 3 : 100, // Azithromycin deliberately low to trip low-stock
                        'unit_cost' => 5,
                        'selling_price' => 8,
                        'mrp' => 10,
                        'expiry_date' => now()->addYear()->toDateString(),
                    ])->all(),
                ]);
            }
        });

        if (count($medicines) < 5) {
            $medicines = \App\Models\Medicine::whereIn('name', ['Paracetamol', 'Amoxicillin', 'Cetirizine', 'Metformin', 'Azithromycin'])->get()->all();
        }

        // --- Inventory items + a purchase order -------------------------------
        $this->step('Inventory & purchase order', function () use ($supplier2) {
            $inventoryService = app(InventoryService::class);
            $glove = InventoryItem::firstOrCreate(['name' => 'Surgical Gloves (box)'], ['category' => 'Consumables', 'unit' => 'box', 'reorder_level' => 20]);
            $syringe = InventoryItem::firstOrCreate(['name' => 'Syringes 5ml (box)'], ['category' => 'Consumables', 'unit' => 'box', 'reorder_level' => 30]);
            if ($glove->wasRecentlyCreated) {
                $inventoryService->recordTransaction($glove, ['type' => 'adjustment', 'quantity' => 15]);
            }
            if ($syringe->wasRecentlyCreated) {
                $inventoryService->recordTransaction($syringe, ['type' => 'adjustment', 'quantity' => 60]);
            }
            app(PurchaseOrderService::class)->create([
                'supplier_id' => $supplier2->id,
                'order_date' => now()->subDays(3)->toDateString(),
                'items' => [
                    ['inventory_item_id' => $glove->id, 'quantity' => 30, 'unit_cost' => 3.5],
                ],
            ]);
        });

        // --- Appointments (varied statuses) -----------------------------------
        $this->step('Appointments', function () use ($p1, $p2, $p3, $p4, $p5, $doctor1, $doctor2) {
            $appointmentService = app(AppointmentService::class);
            $make = fn ($patient, $doctor, $when, $type = 'new') => $appointmentService->create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'scheduled_at' => $when,
                'type' => $type,
            ]);

            $a1 = $make($p1, $doctor1, now()->addHours(2));
            $appointmentService->checkIn($a1);
            $make($p2, $doctor1, now()->addDay());
            $make($p3, $doctor2, now()->addDays(2));
            $a4 = $make($p4, $doctor2, now()->addHours(5));
            $appointmentService->cancel($a4, 'Patient requested reschedule');
            $make($p5, $doctor1, now()->addDays(3), 'follow-up');
        });

        // --- OPD visit, consultation, diagnosis, prescription -------------------
        $this->step('OPD visit + consultation + diagnosis + prescription', function () use ($p2, $doctor1, $medicines) {
            $visit = app(OpdVisitService::class)->create([
                'patient_id' => $p2->id,
                'doctor_id' => $doctor1->id,
                'visit_date' => now(),
                'symptoms' => 'Fever and sore throat for 3 days',
                'diagnosis' => 'Viral pharyngitis',
                'notes' => 'Advised rest and fluids',
            ]);

            $consultation = app(ConsultationService::class)->create([
                'patient_id' => $p2->id,
                'doctor_id' => $doctor1->id,
                'opd_visit_id' => $visit->id,
                'chief_complaint' => 'Fever, sore throat',
                'examination' => 'Throat congestion, temp 100.8F',
                'diagnosis' => 'Viral pharyngitis',
            ]);

            app(DiagnosisService::class)->create([
                'patient_id' => $p2->id,
                'doctor_id' => $doctor1->id,
                'consultation_id' => $consultation->id,
                'diagnosis_name' => 'Viral Pharyngitis',
                'diagnosis_code' => 'J02.9',
            ]);

            app(PrescriptionService::class)->create([
                'patient_id' => $p2->id,
                'doctor_id' => $doctor1->id,
                'consultation_id' => $consultation->id,
                'items' => [
                    ['medicine_id' => $medicines[0]->id, 'dosage' => '1 tablet', 'frequency' => 'twice daily', 'duration' => '5 days', 'quantity' => 10],
                    ['medicine_id' => $medicines[2]->id, 'dosage' => '1 tablet', 'frequency' => 'once daily', 'duration' => '5 days', 'quantity' => 5],
                ],
            ]);
        });

        // --- Bills + Payments (varied statuses for badge colors) -----------------
        $lastP5Bill = null;
        $this->step('Bills & payments', function () use ($p1, $p2, $p3, $p4, $p5, &$lastP5Bill) {
            $billingService = app(BillingService::class);
            $paymentService = app(PaymentService::class);

            $bill1 = $billingService->create([
                'patient_id' => $p1->id,
                'type' => 'opd',
                'items' => [['description' => 'Consultation Fee', 'quantity' => 1, 'unit_price' => 500]],
            ]);
            $paymentService->recordPayment(['bill_id' => $bill1->id, 'patient_id' => $p1->id, 'amount' => 500, 'method' => 'cash']);

            $bill2 = $billingService->create([
                'patient_id' => $p2->id,
                'type' => 'opd',
                'items' => [
                    ['description' => 'Consultation Fee', 'quantity' => 1, 'unit_price' => 500],
                    ['description' => 'Pharmacy Charges', 'quantity' => 1, 'unit_price' => 350],
                ],
            ]);
            $paymentService->recordPayment(['bill_id' => $bill2->id, 'patient_id' => $p2->id, 'amount' => 400, 'method' => 'upi']);

            $billingService->create([
                'patient_id' => $p3->id,
                'type' => 'laboratory',
                'items' => [['description' => 'Lab Tests', 'quantity' => 1, 'unit_price' => 1200]],
            ]);

            $bill4 = $billingService->create([
                'patient_id' => $p4->id,
                'type' => 'other',
                'items' => [['description' => 'Miscellaneous Charges', 'quantity' => 1, 'unit_price' => 200]],
            ]);
            $billingService->cancel($bill4);

            $lastP5Bill = $billingService->create([
                'patient_id' => $p5->id,
                'type' => 'ipd',
                'items' => [['description' => 'Room Charges (3 days)', 'quantity' => 3, 'unit_price' => 2000]],
            ]);
        });

        // --- Insurance company, policy, claim -----------------------------------
        $this->step('Insurance policy & claim', function () use ($p5, $lastP5Bill) {
            $company = \App\Models\InsuranceCompany::firstOrCreate(['name' => 'Star Health Insurance'], ['is_active' => true]);
            $policy = app(InsuranceService::class)->createPolicy([
                'patient_id' => $p5->id,
                'insurance_company_id' => $company->id,
                'policy_number' => 'STAR-'.random_int(100000, 999999),
                'valid_from' => now()->subMonths(6)->toDateString(),
                'valid_till' => now()->addMonths(6)->toDateString(),
                'coverage_amount' => 500000,
            ]);

            $bill = $lastP5Bill ?? \App\Models\Bill::where('patient_id', $p5->id)->latest()->first();
            app(InsuranceService::class)->submitClaim([
                'insurance_policy_id' => $policy->id,
                'bill_id' => $bill?->id,
                'requested_amount' => 6000,
            ]);
        });

        // --- Admission ------------------------------------------------------
        $this->step('Admission', function () use ($p5, $doctor2) {
            $availableBed = \App\Models\Bed::where('status', 'available')->first();
            if (! $availableBed) {
                throw new \RuntimeException('no available bed');
            }
            app(AdmissionService::class)->admit([
                'patient_id' => $p5->id,
                'doctor_id' => $doctor2->id,
                'admission_type' => 'Planned',
                'reason' => 'Observation for dehydration',
                'bed_id' => $availableBed->id,
            ]);
        });

        // --- Lab tests + order (progressed partway through workflow) -------------
        $this->step('Lab order', function () use ($p3, $doctor1) {
            $labService = app(LabService::class);
            $cbc = LabTest::firstOrCreate(['code' => 'CBC'], ['name' => 'Complete Blood Count', 'sample_type' => 'Blood', 'unit' => '', 'reference_range' => 'Varies', 'price' => 300]);
            $lft = LabTest::firstOrCreate(['code' => 'LFT'], ['name' => 'Liver Function Test', 'sample_type' => 'Blood', 'price' => 600]);
            $order = $labService->createOrder([
                'patient_id' => $p3->id,
                'doctor_id' => $doctor1->id,
                'test_ids' => [$cbc->id, $lft->id],
            ]);
            $item = $order->items->first();
            $labService->collectSample($item);
            $labService->startProcessing($item);
        });

        // --- Radiology test + order --------------------------------------------
        $this->step('Radiology order', function () use ($p4, $doctor2) {
            $xray = RadiologyTest::firstOrCreate(['code' => 'CXR'], ['name' => 'Chest X-Ray', 'modality' => 'X-Ray', 'price' => 400]);
            app(RadiologyService::class)->createOrder([
                'patient_id' => $p4->id,
                'doctor_id' => $doctor2->id,
                'radiology_test_id' => $xray->id,
            ]);
        });

        // --- Emergency visit -----------------------------------------------
        $this->step('Emergency visit', function () use ($p6) {
            $visit = app(EmergencyService::class)->register([
                'patient_id' => $p6->id,
                'chief_complaint' => 'Road traffic accident - minor injuries',
            ]);
            app(EmergencyService::class)->triage($visit, 'urgent');
        });

        // --- OT schedule -----------------------------------------------------
        $this->step('OT schedule', function () use ($p3, $doctor2) {
            app(OtService::class)->schedule([
                'patient_id' => $p3->id,
                'surgeon_id' => $doctor2->id,
                'procedure_name' => 'Appendectomy',
                'ot_room' => 'OT-1',
                'scheduled_at' => now()->addDays(4)->setTime(9, 0),
            ]);
        });
    }
}
