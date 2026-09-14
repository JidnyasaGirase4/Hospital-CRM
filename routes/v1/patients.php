<?php

use App\Http\Controllers\Api\V1\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('patients/{patient}/360', [PatientController::class, 'show360'])->name('patients.360');
    Route::apiResource('patients', PatientController::class);
});
