<?php

use App\Http\Controllers\Api\V1\EmergencyVisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('emergency-visits/{emergency_visit}/triage', [EmergencyVisitController::class, 'triage'])->name('emergency-visits.triage');
    Route::patch('emergency-visits/{emergency_visit}/start-treatment', [EmergencyVisitController::class, 'startTreatment'])->name('emergency-visits.start-treatment');
    Route::patch('emergency-visits/{emergency_visit}/admit', [EmergencyVisitController::class, 'admit'])->name('emergency-visits.admit');
    Route::patch('emergency-visits/{emergency_visit}/discharge', [EmergencyVisitController::class, 'discharge'])->name('emergency-visits.discharge');
    Route::patch('emergency-visits/{emergency_visit}/refer', [EmergencyVisitController::class, 'refer'])->name('emergency-visits.refer');
    Route::apiResource('emergency-visits', EmergencyVisitController::class)->only(['index', 'store', 'show']);
});
