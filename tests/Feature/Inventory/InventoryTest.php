<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsInventoryManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::INVENTORY_MANAGER);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_purchase_transaction_increases_stock(): void
    {
        $this->actingAsInventoryManager();
        $item = InventoryItem::factory()->create();

        $this->postJson("/api/v1/inventory-items/{$item->id}/transactions", [
            'type' => 'purchase',
            'quantity' => 100,
        ])->assertCreated();

        $this->assertEquals(100, $item->fresh()->stockOnHand());
    }

    public function test_issue_transaction_decreases_stock(): void
    {
        $this->actingAsInventoryManager();
        $item = InventoryItem::factory()->create();

        $this->postJson("/api/v1/inventory-items/{$item->id}/transactions", ['type' => 'purchase', 'quantity' => 50])->assertCreated();
        $this->postJson("/api/v1/inventory-items/{$item->id}/transactions", ['type' => 'issue', 'quantity' => 20])->assertCreated();

        $this->assertEquals(30, $item->fresh()->stockOnHand());
    }

    public function test_issue_cannot_take_stock_negative(): void
    {
        $this->actingAsInventoryManager();
        $item = InventoryItem::factory()->create();

        $this->postJson("/api/v1/inventory-items/{$item->id}/transactions", ['type' => 'purchase', 'quantity' => 10])->assertCreated();

        $response = $this->postJson("/api/v1/inventory-items/{$item->id}/transactions", ['type' => 'issue', 'quantity' => 20]);

        $response->assertStatus(422)->assertJsonValidationErrors(['quantity']);
        $this->assertEquals(10, $item->fresh()->stockOnHand());
    }

    public function test_negative_adjustment_can_write_off_damaged_stock(): void
    {
        $this->actingAsInventoryManager();
        $item = InventoryItem::factory()->create();

        $this->postJson("/api/v1/inventory-items/{$item->id}/transactions", ['type' => 'purchase', 'quantity' => 50])->assertCreated();
        $this->postJson("/api/v1/inventory-items/{$item->id}/transactions", ['type' => 'adjustment', 'quantity' => -5, 'notes' => 'Damaged in storage'])->assertCreated();

        $this->assertEquals(45, $item->fresh()->stockOnHand());
    }

    public function test_low_stock_endpoint_lists_items_at_or_below_reorder_level(): void
    {
        $this->actingAsInventoryManager();
        $low = InventoryItem::factory()->create(['reorder_level' => 20]);
        $healthy = InventoryItem::factory()->create(['reorder_level' => 5]);

        $this->postJson("/api/v1/inventory-items/{$low->id}/transactions", ['type' => 'purchase', 'quantity' => 10])->assertCreated();
        $this->postJson("/api/v1/inventory-items/{$healthy->id}/transactions", ['type' => 'purchase', 'quantity' => 50])->assertCreated();

        $response = $this->getJson('/api/v1/inventory-items/low-stock')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($low->id));
        $this->assertFalse($ids->contains($healthy->id));
    }

    public function test_purchase_order_lifecycle_updates_stock_on_receive(): void
    {
        $this->actingAsInventoryManager();
        $supplier = Supplier::factory()->create();
        $item = InventoryItem::factory()->create();

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [['inventory_item_id' => $item->id, 'quantity' => 30, 'unit_cost' => 5]],
        ])->assertCreated()->assertJsonPath('data.total_amount', '150.00')->json('data.id');

        $this->patchJson("/api/v1/purchase-orders/{$poId}/mark-ordered")->assertOk()->assertJsonPath('data.status', 'ordered');

        $this->assertEquals(0, $item->fresh()->stockOnHand());

        $this->patchJson("/api/v1/purchase-orders/{$poId}/receive")->assertOk()->assertJsonPath('data.status', 'received');

        $this->assertEquals(30, $item->fresh()->stockOnHand());
    }

    public function test_received_purchase_order_cannot_be_cancelled(): void
    {
        $this->actingAsInventoryManager();
        $supplier = Supplier::factory()->create();
        $item = InventoryItem::factory()->create();

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [['inventory_item_id' => $item->id, 'quantity' => 10, 'unit_cost' => 2]],
        ])->json('data.id');

        $this->patchJson("/api/v1/purchase-orders/{$poId}/mark-ordered")->assertOk();
        $this->patchJson("/api/v1/purchase-orders/{$poId}/receive")->assertOk();

        $this->patchJson("/api/v1/purchase-orders/{$poId}/cancel")->assertStatus(422);
    }

    public function test_receptionist_cannot_manage_inventory(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist, 'sanctum');

        $this->postJson('/api/v1/inventory-items', ['name' => 'Test Item'])->assertStatus(403);
    }
}
