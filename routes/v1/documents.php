<?php

use App\Http\Controllers\Api\V1\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::apiResource('documents', DocumentController::class)->only(['index', 'store', 'show', 'destroy']);
});
