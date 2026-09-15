<?php

namespace Tests\Feature\AuditLogs;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_and_patient_creation_are_audited(): void
    {
        $admin = User::factory()->create(['password' => bcrypt('secret123')]);
        $admin->assignRole(Role::HOSPITAL_ADMIN);

        $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'secret123',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'login']);

        $this->actingAs($admin, 'sanctum');

        $patientId = $this->postJson('/api/v1/patients', [
            'first_name' => 'Jane',
            'mobile' => '9876500000',
        ])->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => 'patient',
            'auditable_id' => $patientId,
        ]);
    }

    public function test_medical_record_access_is_audited(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole(Role::DOCTOR);
        $this->actingAs($doctor, 'sanctum');

        $patient = Patient::factory()->create();

        $this->getJson("/api/v1/patients/{$patient->id}/360")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $doctor->id,
            'action' => 'medical-record-accessed',
            'auditable_id' => $patient->id,
        ]);
    }

    public function test_only_authorized_roles_can_view_audit_logs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::HOSPITAL_ADMIN);
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/v1/audit-logs')->assertOk();

        $nurse = User::factory()->create();
        $nurse->assignRole(Role::NURSE);
        $this->actingAs($nurse, 'sanctum');

        $this->getJson('/api/v1/audit-logs')->assertStatus(403);
    }
}
