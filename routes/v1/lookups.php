<?php

use App\Http\Controllers\Api\V1\LookupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('lookups')->name('lookups.')->group(function () {
    Route::get('staff', [LookupController::class, 'staff'])->name('staff');
    Route::get('medicines', [LookupController::class, 'medicines'])->name('medicines');
    Route::get('beds', [LookupController::class, 'beds'])->name('beds');
});
