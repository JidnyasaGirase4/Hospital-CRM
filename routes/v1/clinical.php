<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\ConsultationController;
use App\Http\Controllers\Api\V1\OpdVisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');
    Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::patch('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::apiResource('appointments', AppointmentController::class);

    Route::patch('opd-visits/{opd_visit}/close', [OpdVisitController::class, 'close'])->name('opd-visits.close');
    Route::apiResource('opd-visits', OpdVisitController::class);

    Route::patch('consultations/{consultation}/complete', [ConsultationController::class, 'complete'])->name('consultations.complete');
    Route::apiResource('consultations', ConsultationController::class);
});
