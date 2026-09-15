<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->hasPermission('audit-logs.view')) {
            abort(403, 'This action is unauthorized');
        }

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('auditable_type'), fn ($q) => $q->where('auditable_type', $request->string('auditable_type')))
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return $this->paginated($logs, AuditLogResource::class, 'Audit logs retrieved successfully');
    }
}
