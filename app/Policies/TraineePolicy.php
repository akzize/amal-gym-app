<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Trainee;
use Illuminate\Auth\Access\HandlesAuthorization;

class TraineePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Trainee');
    }

    public function view(AuthUser $authUser, Trainee $trainee): bool
    {
        return $authUser->can('View:Trainee');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Trainee');
    }

    public function update(AuthUser $authUser, Trainee $trainee): bool
    {
        return $authUser->can('Update:Trainee');
    }

    public function delete(AuthUser $authUser, Trainee $trainee): bool
    {
        return $authUser->can('Delete:Trainee');
    }

    public function restore(AuthUser $authUser, Trainee $trainee): bool
    {
        return $authUser->can('Restore:Trainee');
    }

    public function forceDelete(AuthUser $authUser, Trainee $trainee): bool
    {
        return $authUser->can('ForceDelete:Trainee');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Trainee');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Trainee');
    }

    public function replicate(AuthUser $authUser, Trainee $trainee): bool
    {
        return $authUser->can('Replicate:Trainee');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Trainee');
    }

}