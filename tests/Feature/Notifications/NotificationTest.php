<?php

namespace Tests\Feature\Notifications;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Role;
use App\Models\User;
use App\Notifications\LowStockAlertNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dispensing_below_reorder_level_notifies_pharmacists(): void
    {
        Notification::fake();

        $pharmacist = User::factory()->create();
        $pharmacist->assignRole(Role::PHARMACIST);

        $actor = User::factory()->create();
        $actor->assignRole(Role::PHARMACIST);
        $this->actingAs($actor, 'sanctum');

        $medicine = Medicine::factory()->create(['reorder_level' => 10]);
        MedicineBatch::factory()->create(['medicine_id' => $medicine->id, 'quantity' => 15]);

        $this->postJson('/api/v1/pharmacy-sales', [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 10]],
        ])->assertCreated();

        Notification::assertSentTo($pharmacist, LowStockAlertNotification::class);
        Notification::assertSentTo($actor, LowStockAlertNotification::class);
    }

    public function test_user_can_list_and_mark_notifications_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::PHARMACIST);

        $user->notify(new LowStockAlertNotification('Paracetamol', 5, 10));

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/notifications')->assertOk();
        $notificationId = $response->json('data.0.id');
        $this->assertNull($response->json('data.0.read_at'));

        $this->patchJson("/api/v1/notifications/{$notificationId}/read")->assertOk();

        $this->assertNotNull($user->notifications()->find($notificationId)->read_at);
    }

    public function test_mark_all_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new LowStockAlertNotification('Item A', 1, 5));
        $user->notify(new LowStockAlertNotification('Item B', 2, 5));

        $this->actingAs($user, 'sanctum');

        $this->patchJson('/api/v1/notifications/read-all')->assertOk();

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }
}
