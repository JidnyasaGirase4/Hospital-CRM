<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

class AuditLogService
{
    /**
     * $actorId overrides Auth::id() for actions where the acting user isn't
     * (yet) the authenticated request user - e.g. login itself: the token
     * just issued authenticates *future* requests, so Auth::id() is still
     * null during the login request that creates it.
     */
    public function log(string $action, ?Model $auditable = null, array $metadata = [], ?int $actorId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata,
            'ip_address' => RequestFacade::ip(),
            'user_agent' => RequestFacade::userAgent(),
        ]);
    }
}
