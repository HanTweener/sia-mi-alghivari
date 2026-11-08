<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::MASTER_ADMIN);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::PRINCIPAL,
        ]);
    }

    /**
     * Determine whether the user can create models.
     * Only Master Admin can create accounts and assign initial roles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::MASTER_ADMIN);
    }

    /**
     * Determine whether the user can manage roles of another user.
     * Strictly restricted to Master Admin (FR-002, Q4).
     */
    public function manageRoles(User $user): bool
    {
        return $user->hasRole(Role::MASTER_ADMIN);
    }

    /**
     * Determine whether the user can update their own profile.
     */
    public function updateProfile(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole(Role::MASTER_ADMIN);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves, and only Master Admin can deactivate/delete.
        return $user->hasRole(Role::MASTER_ADMIN) && $user->id !== $model->id;
    }
}
