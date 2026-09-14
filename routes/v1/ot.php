<?php

use App\Http\Controllers\Api\V1\OtScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('ot-schedules/{ot_schedule}/start', [OtScheduleController::class, 'start'])->name('ot-schedules.start');
    Route::patch('ot-schedules/{ot_schedule}/complete', [OtScheduleController::class, 'complete'])->name('ot-schedules.complete');
    Route::patch('ot-schedules/{ot_schedule}/cancel', [OtScheduleController::class, 'cancel'])->name('ot-schedules.cancel');
    Route::apiResource('ot-schedules', OtScheduleController::class)->only(['index', 'store', 'show']);
});
