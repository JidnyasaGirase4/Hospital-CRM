<?php

use App\Http\Controllers\Api\V1\InsuranceClaimController;
use App\Http\Controllers\Api\V1\InsuranceCompanyController;
use App\Http\Controllers\Api\V1\InsurancePolicyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('insurance-companies', [InsuranceCompanyController::class, 'index'])->name('insurance-companies.index');
    Route::post('insurance-companies', [InsuranceCompanyController::class, 'store'])->name('insurance-companies.store');

    Route::apiResource('insurance-policies', InsurancePolicyController::class)->only(['index', 'store', 'show']);

    Route::patch('insurance-claims/{insurance_claim}/approve', [InsuranceClaimController::class, 'approve'])->name('insurance-claims.approve');
    Route::patch('insurance-claims/{insurance_claim}/reject', [InsuranceClaimController::class, 'reject'])->name('insurance-claims.reject');
    Route::patch('insurance-claims/{insurance_claim}/settle', [InsuranceClaimController::class, 'settle'])->name('insurance-claims.settle');
    Route::apiResource('insurance-claims', InsuranceClaimController::class)->only(['index', 'store', 'show']);
});
