<?php

use App\Http\Controllers\Api\V1\MedicationAdministrationController;
use App\Http\Controllers\Api\V1\NursingNoteController;
use App\Http\Controllers\Api\V1\PatientVitalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('nursing-notes', NursingNoteController::class)->only(['index', 'store']);
    Route::apiResource('patient-vitals', PatientVitalController::class)->only(['index', 'store']);
    Route::apiResource('medication-administrations', MedicationAdministrationController::class)->only(['index', 'store']);
});
