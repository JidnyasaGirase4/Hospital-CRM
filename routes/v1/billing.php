<?php

use App\Http\Controllers\Api\V1\BillController;
use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('bills/{bill}/items', [BillController::class, 'addItem'])->name('bills.items.store');
    Route::post('bills/{bill}/discounts', [BillController::class, 'addDiscount'])->name('bills.discounts.store');
    Route::patch('bills/{bill}/cancel', [BillController::class, 'cancel'])->name('bills.cancel');
    Route::apiResource('bills', BillController::class)->only(['index', 'store', 'show']);

    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);
});
