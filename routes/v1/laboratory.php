<?php

use App\Http\Controllers\Api\V1\LabOrderController;
use App\Http\Controllers\Api\V1\LabTestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('lab-tests', [LabTestController::class, 'index'])->name('lab-tests.index');
    Route::post('lab-tests', [LabTestController::class, 'store'])->name('lab-tests.store');

    Route::patch('lab-order-items/{item}/collect-sample', [LabOrderController::class, 'collectSample'])->name('lab-order-items.collect-sample');
    Route::patch('lab-order-items/{item}/start-processing', [LabOrderController::class, 'startProcessing'])->name('lab-order-items.start-processing');
    Route::post('lab-order-items/{item}/results', [LabOrderController::class, 'recordResults'])->name('lab-order-items.results.store');
    Route::patch('lab-order-items/{item}/approve', [LabOrderController::class, 'approveResults'])->name('lab-order-items.approve');

    Route::apiResource('lab-orders', LabOrderController::class)->only(['index', 'store', 'show']);
});
