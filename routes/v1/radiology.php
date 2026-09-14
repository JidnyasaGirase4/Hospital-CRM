<?php

use App\Http\Controllers\Api\V1\RadiologyOrderController;
use App\Http\Controllers\Api\V1\RadiologyTestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('radiology-tests', [RadiologyTestController::class, 'index'])->name('radiology-tests.index');
    Route::post('radiology-tests', [RadiologyTestController::class, 'store'])->name('radiology-tests.store');

    Route::patch('radiology-orders/{radiology_order}/schedule', [RadiologyOrderController::class, 'schedule'])->name('radiology-orders.schedule');
    Route::post('radiology-orders/{radiology_order}/report', [RadiologyOrderController::class, 'submitReport'])->name('radiology-orders.report.store');
    Route::patch('radiology-orders/{radiology_order}/report/approve', [RadiologyOrderController::class, 'approveReport'])->name('radiology-orders.report.approve');

    Route::apiResource('radiology-orders', RadiologyOrderController::class)->only(['index', 'store', 'show']);
});
