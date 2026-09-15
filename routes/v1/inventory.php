<?php

use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('inventory-items/low-stock', [InventoryItemController::class, 'lowStock'])->name('inventory-items.low-stock');
    Route::post('inventory-items/{inventory_item}/transactions', [InventoryItemController::class, 'storeTransaction'])->name('inventory-items.transactions.store');
    Route::apiResource('inventory-items', InventoryItemController::class)->only(['index', 'store', 'show', 'update']);

    Route::patch('purchase-orders/{purchase_order}/mark-ordered', [PurchaseOrderController::class, 'markOrdered'])->name('purchase-orders.mark-ordered');
    Route::patch('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::patch('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::apiResource('purchase-orders', PurchaseOrderController::class)->only(['index', 'store', 'show']);
});
