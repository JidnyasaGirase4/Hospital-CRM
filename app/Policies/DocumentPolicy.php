<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.create');
    }

    public function download(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.download');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.delete');
    }
}
