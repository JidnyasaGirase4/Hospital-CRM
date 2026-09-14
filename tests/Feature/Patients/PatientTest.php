<?php

namespace Tests\Feature\Patients;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsReceptionist(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::RECEPTIONIST);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_creating_a_patient_auto_generates_a_unique_mrn(): void
    {
        $this->actingAsReceptionist();

        $response = $this->postJson('/api/v1/patients', [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'mobile' => '9876543210',
        ]);

        $response->assertCreated()->assertJsonStructure(['data' => ['mrn']]);

        $mrn = $response->json('data.mrn');
        $this->assertMatchesRegularExpression('/^MRN\d{6}$/', $mrn);
    }

    public function test_mrn_sequence_never_collides_across_multiple_patients(): void
    {
        $this->actingAsReceptionist();

        $mrns = collect(range(1, 5))->map(function ($i) {
            $response = $this->postJson('/api/v1/patients', [
                'first_name' => "Patient{$i}",
                'mobile' => "900000000{$i}",
            ]);

            return $response->json('data.mrn');
        });

        $this->assertCount(5, $mrns->unique());
    }

    public function test_patient_creation_requires_first_name_and_mobile(): void
    {
        $this->actingAsReceptionist();

        $this->postJson('/api/v1/patients', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'mobile']);
    }

    public function test_user_without_permission_cannot_create_patients(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);
        $this->actingAs($pharmacist, 'sanctum');

        $this->postJson('/api/v1/patients', [
            'first_name' => 'John',
            'mobile' => '9876543210',
        ])->assertStatus(403);
    }

    public function test_patients_can_be_searched_by_mrn_name_or_mobile(): void
    {
        $this->actingAsReceptionist();

        Patient::factory()->create(['first_name' => 'Alice', 'mobile' => '1111111111']);
        Patient::factory()->create(['first_name' => 'Bob', 'mobile' => '2222222222']);

        $response = $this->getJson('/api/v1/patients?search=Alice');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Alice', $response->json('data.0.first_name'));
    }

    public function test_admin_can_update_and_soft_delete_a_patient(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::HOSPITAL_ADMIN);
        $this->actingAs($admin, 'sanctum');

        $patient = Patient::factory()->create(['first_name' => 'Original']);

        $this->putJson("/api/v1/patients/{$patient->id}", ['first_name' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Updated');

        $this->deleteJson("/api/v1/patients/{$patient->id}")->assertOk();

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }
}
