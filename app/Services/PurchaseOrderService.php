<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(private readonly SequenceGeneratorService $sequences) {}

    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $total = array_reduce(
                $data['items'],
                fn (string $carry, array $item) => bcadd($carry, bcmul((string) $item['unit_cost'], (string) $item['quantity'], 2), 2),
                '0'
            );

            $po = PurchaseOrder::create([
                'po_number' => $this->sequences->next('purchase_order', 'PO'),
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'status' => 'draft',
                'total_amount' => $total,
                'created_by' => request()->user()?->id,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => bcmul((string) $item['unit_cost'], (string) $item['quantity'], 2),
                ]);
            }

            return $po->fresh(['items', 'supplier']);
        });
    }

    public function markOrdered(PurchaseOrder $po): PurchaseOrder
    {
        $this->assertStatus($po, 'draft');

        $po->update(['status' => 'ordered']);

        return $po->fresh();
    }

    public function receive(PurchaseOrder $po): PurchaseOrder
    {
        $this->assertStatus($po, 'ordered');

        return DB::transaction(function () use ($po) {
            foreach ($po->items()->with('item')->get() as $item) {
                InventoryTransaction::create([
                    'inventory_item_id' => $item->inventory_item_id,
                    'type' => 'purchase',
                    'quantity' => $item->quantity,
                    'reference' => "PO {$po->po_number}",
                    'performed_by' => request()->user()?->id,
                    'transaction_date' => now(),
                ]);
            }

            $po->update(['status' => 'received']);

            return $po->fresh(['items']);
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status === 'received') {
            throw ValidationException::withMessages([
                'status' => ['Cannot cancel a purchase order that has already been received.'],
            ]);
        }

        $po->update(['status' => 'cancelled']);

        return $po->fresh();
    }

    private function assertStatus(PurchaseOrder $po, string $expected): void
    {
        if ($po->status !== $expected) {
            throw ValidationException::withMessages([
                'status' => ["Expected status '{$expected}', got '{$po->status}'."],
            ]);
        }
    }
}
