<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Association;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssociationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Association');
    }

    public function view(AuthUser $authUser, Association $association): bool
    {
        return $authUser->can('View:Association');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Association');
    }

    public function update(AuthUser $authUser, Association $association): bool
    {
        return $authUser->can('Update:Association');
    }

    public function delete(AuthUser $authUser, Association $association): bool
    {
        return $authUser->can('Delete:Association');
    }

    public function restore(AuthUser $authUser, Association $association): bool
    {
        return $authUser->can('Restore:Association');
    }

    public function forceDelete(AuthUser $authUser, Association $association): bool
    {
        return $authUser->can('ForceDelete:Association');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Association');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Association');
    }

    public function replicate(AuthUser $authUser, Association $association): bool
    {
        return $authUser->can('Replicate:Association');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Association');
    }

}