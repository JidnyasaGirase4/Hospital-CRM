<?php

namespace App\Policies;

use App\Models\NursingNote;
use App\Models\User;

class NursingNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('nursing.view');
    }

    public function view(User $user, NursingNote $nursingNote): bool
    {
        return $user->hasPermission('nursing.view') || $user->id === $nursingNote->nurse_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('nursing.create');
    }
}
