<?php

namespace App\Policies;

use App\Models\GovernanceItem;
use App\Models\User;

class GovernanceItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GovernanceItem $governanceItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has explicit access
        $access = $governanceItem->access()
            ->where('user_id', $user->id)
            ->where('can_view', true)
            ->exists();

        if ($access) {
            return true;
        }

        // Check department permission
        return $user->canViewDepartment($governanceItem->department);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->departmentPermissions()
            ->where('can_create_tasks', true)
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GovernanceItem $governanceItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check explicit edit access
        $access = $governanceItem->access()
            ->where('user_id', $user->id)
            ->where('can_edit', true)
            ->exists();

        if ($access) {
            return true;
        }

        // Responsible party can edit
        if ($governanceItem->responsible_party_id === $user->id) {
            return true;
        }

        return $user->canEditInDepartment($governanceItem->department);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GovernanceItem $governanceItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->canEditInDepartment($governanceItem->department);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GovernanceItem $governanceItem): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GovernanceItem $governanceItem): bool
    {
        return false;
    }
}
