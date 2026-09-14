<?php

use App\Http\Controllers\Api\V1\DiagnosisController;
use App\Http\Controllers\Api\V1\MedicineCategoryController;
use App\Http\Controllers\Api\V1\MedicineController;
use App\Http\Controllers\Api\V1\PharmacyPurchaseController;
use App\Http\Controllers\Api\V1\PharmacySaleController;
use App\Http\Controllers\Api\V1\PrescriptionController;
use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('diagnoses', DiagnosisController::class);

    Route::apiResource('prescriptions', PrescriptionController::class)->only(['index', 'store', 'show', 'destroy']);

    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::apiResource('suppliers', SupplierController::class);

    Route::get('medicines/low-stock', [MedicineController::class, 'lowStock'])->name('medicines.low-stock');
    Route::apiResource('medicines', MedicineController::class);

    Route::get('medicine-categories', [MedicineCategoryController::class, 'index'])->name('medicine-categories.index');
    Route::post('medicine-categories', [MedicineCategoryController::class, 'store'])->name('medicine-categories.store');

    Route::apiResource('pharmacy-purchases', PharmacyPurchaseController::class)->only(['index', 'store', 'show']);

    Route::post('pharmacy-sales/{sale}/returns', [PharmacySaleController::class, 'storeReturn'])->name('pharmacy-sales.returns.store');
    Route::apiResource('pharmacy-sales', PharmacySaleController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['pharmacy-sales' => 'sale']);
});
