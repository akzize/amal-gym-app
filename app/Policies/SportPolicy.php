<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Sport;
use Illuminate\Auth\Access\HandlesAuthorization;

class SportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Sport');
    }

    public function view(AuthUser $authUser, Sport $sport): bool
    {
        return $authUser->can('View:Sport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Sport');
    }

    public function update(AuthUser $authUser, Sport $sport): bool
    {
        return $authUser->can('Update:Sport');
    }

    public function delete(AuthUser $authUser, Sport $sport): bool
    {
        return $authUser->can('Delete:Sport');
    }

    public function restore(AuthUser $authUser, Sport $sport): bool
    {
        return $authUser->can('Restore:Sport');
    }

    public function forceDelete(AuthUser $authUser, Sport $sport): bool
    {
        return $authUser->can('ForceDelete:Sport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Sport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Sport');
    }

    public function replicate(AuthUser $authUser, Sport $sport): bool
    {
        return $authUser->can('Replicate:Sport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Sport');
    }

}