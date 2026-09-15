<?php

use App\Http\Controllers\Api\V1\AdmissionController;
use App\Http\Controllers\Api\V1\BedController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\WardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('wards', [WardController::class, 'index'])->name('wards.index');
    Route::post('wards', [WardController::class, 'store'])->name('wards.store');

    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('rooms', [RoomController::class, 'store'])->name('rooms.store');

    Route::patch('beds/{bed}/mark-cleaned', [BedController::class, 'markCleaned'])->name('beds.mark-cleaned');
    Route::get('beds', [BedController::class, 'index'])->name('beds.index');
    Route::post('beds', [BedController::class, 'store'])->name('beds.store');

    Route::patch('admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])->name('admissions.discharge');
    Route::patch('admissions/{admission}/transfer-bed', [AdmissionController::class, 'transferBed'])->name('admissions.transfer-bed');
    Route::post('admissions/{admission}/final-bill', [AdmissionController::class, 'generateFinalBill'])->name('admissions.final-bill');
    Route::apiResource('admissions', AdmissionController::class)->only(['index', 'store', 'show']);
});
