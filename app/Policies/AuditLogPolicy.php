<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine whether the user can view any audit logs.
     * Allowed: Master Admin and Academic Admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN]);
    }

    /**
     * Determine whether the user can view a specific audit log.
     * Allowed: Master Admin and Academic Admin.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return AuditLog::visibleTo($user)->whereKey($auditLog->id)->exists();
    }

    /**
     * Audit logs are append-only by system; no user can create directly.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Audit logs cannot be updated by anyone.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Audit logs cannot be deleted by anyone.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
