<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::DATA_ENTRY_ADMIN,
            Role::TEACHER,
            Role::PRINCIPAL,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }
}
