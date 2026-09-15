<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Small shared helper so every service that needs to notify "everyone
 * holding role X" doesn't repeat the same role-lookup query.
 */
class StaffNotifier
{
    public function notifyRoles(array $roleSlugs, object $notification): void
    {
        $users = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', $roleSlugs))
            ->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, $notification);
        }
    }
}
