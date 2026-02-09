<?php

namespace App\Policies;

use App\Models\Risk;
use App\Models\User;

class RiskPolicy
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
    public function view(User $user, Risk $risk): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check theme permission
        $themeId = $risk->category?->theme_id;
        if (! $themeId) {
            return false;
        }

        return $user->riskThemePermissions()
            ->where('theme_id', $themeId)
            ->where('can_view', true)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->riskThemePermissions()
            ->where('can_create', true)
            ->exists();
    }

    /**
     * Determine whether the user can create in a specific theme.
     */
    public function createInTheme(User $user, int $themeId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->riskThemePermissions()
            ->where('theme_id', $themeId)
            ->where('can_create', true)
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Risk $risk): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can edit
        if ($risk->owner_id === $user->id) {
            return true;
        }

        // Responsible party can edit
        if ($risk->responsible_party_id === $user->id) {
            return true;
        }

        // Check theme permission
        $themeId = $risk->category?->theme_id;
        if (! $themeId) {
            return false;
        }

        return $user->riskThemePermissions()
            ->where('theme_id', $themeId)
            ->where('can_edit', true)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Risk $risk): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $themeId = $risk->category?->theme_id;
        if (! $themeId) {
            return false;
        }

        return $user->riskThemePermissions()
            ->where('theme_id', $themeId)
            ->where('can_delete', true)
            ->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Risk $risk): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Risk $risk): bool
    {
        return false;
    }
}
