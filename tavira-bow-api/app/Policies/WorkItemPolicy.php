<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkItem;

class WorkItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view the list (filtered by permissions)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkItem $workItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check department permission
        return $user->canViewDepartment($workItem->department);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // User must have create permission in at least one department
        return $user->departmentPermissions()
            ->where('can_create_tasks', true)
            ->exists();
    }

    /**
     * Determine whether the user can create in a specific department.
     */
    public function createInDepartment(User $user, string $department): bool
    {
        return $user->canCreateInDepartment($department);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkItem $workItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Responsible party can update their own items
        if ($workItem->responsible_party_id === $user->id) {
            return true;
        }

        // Check department edit permission
        return $user->canEditInDepartment($workItem->department);
    }

    /**
     * Determine whether the user can update the status only.
     */
    public function updateStatus(User $user, WorkItem $workItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Responsible party can update status
        if ($workItem->responsible_party_id === $user->id) {
            return true;
        }

        // Check department status edit permission
        return $user->canEditStatus($workItem->department);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkItem $workItem): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Only users with full edit permission can delete
        return $user->canEditInDepartment($workItem->department);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkItem $workItem): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkItem $workItem): bool
    {
        return false;
    }
}
