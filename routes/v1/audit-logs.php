<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
