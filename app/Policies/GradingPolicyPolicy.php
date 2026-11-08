<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\GradingPolicy;
use App\Models\User;

class GradingPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::TEACHER,
            Role::PRINCIPAL,
        ]);
    }

    public function view(User $user, ?GradingPolicy $policy = null): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function update(User $user, ?GradingPolicy $policy = null): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function activate(User $user, ?GradingPolicy $policy = null): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function manageComponents(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }
}
