<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function actingAsDoctor(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::DOCTOR);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_doctor_can_upload_a_document_and_it_is_not_publicly_accessible(): void
    {
        $this->actingAsDoctor();
        $patient = Patient::factory()->create();

        $file = UploadedFile::fake()->create('discharge-summary.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/v1/documents', [
            'patient_id' => $patient->id,
            'title' => 'Discharge Summary',
            'category' => 'discharge-summary',
            'file' => $file,
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Discharge Summary');

        $document = Document::first();
        Storage::disk('local')->assertExists($document->file_path);

        // The 'local' disk root (storage/app/private) is never symlinked to
        // public/ - see config/filesystems.php - so there is no public URL
        // to assert against; the only way to retrieve a file is the
        // authorized download endpoint (covered in the next tests).
        $this->assertSame('local', $document->disk);
    }

    public function test_guest_cannot_download_a_document(): void
    {
        $patient = Patient::factory()->create();
        $document = Document::factory()->for($patient)->create();

        $this->getJson("/api/v1/documents/{$document->id}/download")->assertStatus(401);
    }

    public function test_user_without_download_permission_cannot_download(): void
    {
        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);
        $this->actingAs($pharmacist, 'sanctum');

        $patient = Patient::factory()->create();
        $document = Document::factory()->for($patient)->create();

        $this->getJson("/api/v1/documents/{$document->id}/download")->assertStatus(403);
    }

    public function test_authorized_user_can_download_document(): void
    {
        $doctor = $this->actingAsDoctor();
        $patient = Patient::factory()->create();

        $file = UploadedFile::fake()->create('report.pdf', 50, 'application/pdf');
        $documentId = $this->postJson('/api/v1/documents', [
            'patient_id' => $patient->id,
            'title' => 'Report',
            'file' => $file,
        ])->json('data.id');

        $this->getJson("/api/v1/documents/{$documentId}/download")->assertOk();
    }
}
