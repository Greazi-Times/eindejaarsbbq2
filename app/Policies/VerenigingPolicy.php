<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Vereniging;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class VerenigingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Vereniging');
    }

    public function view(AuthUser $authUser, Vereniging $vereniging): bool
    {
        return $authUser->can('View:Vereniging');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Vereniging');
    }

    public function update(AuthUser $authUser, Vereniging $vereniging): bool
    {
        return $authUser->can('Update:Vereniging');
    }

    public function delete(AuthUser $authUser, Vereniging $vereniging): bool
    {
        return $authUser->can('Delete:Vereniging');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Vereniging');
    }

    public function restore(AuthUser $authUser, Vereniging $vereniging): bool
    {
        return $authUser->can('Restore:Vereniging');
    }

    public function forceDelete(AuthUser $authUser, Vereniging $vereniging): bool
    {
        return $authUser->can('ForceDelete:Vereniging');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Vereniging');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Vereniging');
    }

    public function replicate(AuthUser $authUser, Vereniging $vereniging): bool
    {
        return $authUser->can('Replicate:Vereniging');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Vereniging');
    }
}
