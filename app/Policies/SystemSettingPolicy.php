<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingPolicy
{
    /**
     * Determine whether the user can view any settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::MASTER_ADMIN);
    }

    /**
     * Determine whether the user can view a specific setting.
     */
    public function view(User $user, SystemSetting $setting): bool
    {
        return $user->hasRole(Role::MASTER_ADMIN);
    }

    /**
     * Determine whether the user can update settings.
     */
    public function update(User $user, ?SystemSetting $setting = null): bool
    {
        return $user->hasRole(Role::MASTER_ADMIN);
    }
}
