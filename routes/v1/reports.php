<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('reports')->name('reports.')->group(function () {
    Route::get('revenue', [ReportController::class, 'revenue'])->name('revenue');
    Route::get('bed-occupancy', [ReportController::class, 'bedOccupancy'])->name('bed-occupancy');
    Route::get('pharmacy-stock', [ReportController::class, 'pharmacyStock'])->name('pharmacy-stock');
    Route::get('lab-turnaround', [ReportController::class, 'labTurnaround'])->name('lab-turnaround');
});
