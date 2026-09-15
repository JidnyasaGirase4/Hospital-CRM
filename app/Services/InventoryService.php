<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function createItem(array $data): InventoryItem
    {
        return InventoryItem::create([
            ...$data,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        $item->update($data);

        return $item->fresh();
    }

    /**
     * Positive types (purchase/return) add stock; negative types (issue/
     * transfer) deduct it and can never take stock below zero. 'adjustment'
     * may be either sign for stock-count corrections.
     */
    public function recordTransaction(InventoryItem $item, array $data): InventoryTransaction
    {
        return DB::transaction(function () use ($item, $data) {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            $signedQuantity = $this->signedQuantity($data['type'], (int) $data['quantity']);

            if ($signedQuantity < 0) {
                $currentStock = (int) $item->transactions()->sum('quantity');

                if ($currentStock + $signedQuantity < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => ["Insufficient stock for {$item->name}. Available: {$currentStock}, requested: ".abs($signedQuantity).'.'],
                    ]);
                }
            }

            return InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => $data['type'],
                'quantity' => $signedQuantity,
                'reference' => $data['reference'] ?? null,
                'performed_by' => request()->user()?->id,
                'transaction_date' => now(),
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * purchase/return/issue/transfer take a positive count from the caller
     * (e.g. "issue 10 units") and the sign is applied here based on type.
     * adjustment is the one type where the caller passes the signed delta
     * directly (+5 found on recount, -3 to write off damaged stock).
     */
    private function signedQuantity(string $type, int $quantity): int
    {
        return match ($type) {
            'issue', 'transfer' => -abs($quantity),
            'adjustment' => $quantity,
            default => abs($quantity), // purchase, return
        };
    }

    public function lowStock()
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (InventoryItem $item) => $item->isLowStock())
            ->values();
    }
}
