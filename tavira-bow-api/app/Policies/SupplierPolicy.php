<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
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
    public function view(User $user, Supplier $supplier): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check explicit access
        $access = $supplier->access()
            ->where('user_id', $user->id)
            ->where('can_view', true)
            ->exists();

        if ($access) {
            return true;
        }

        // Responsible party can view
        return $supplier->responsible_party_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check explicit edit access
        $access = $supplier->access()
            ->where('user_id', $user->id)
            ->where('can_edit', true)
            ->exists();

        if ($access) {
            return true;
        }

        // Responsible party can edit
        return $supplier->responsible_party_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check explicit delete access
        return $supplier->access()
            ->where('user_id', $user->id)
            ->where('can_delete', true)
            ->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return false;
    }
}
