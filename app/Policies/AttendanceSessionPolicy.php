<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\DataEntryDelegation;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSessionPolicy
{
    public function viewAny(User $user, ClassSubject $classSubject): bool
    {
        if ($user->hasRole(Role::PRINCIPAL)) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER) && $classSubject->classroom->activeHomeroom?->teacher_id === $user->id) {
            return true;
        }

        return $this->create($user, $classSubject);
    }

    public function create(User $user, ClassSubject $classSubject): bool
    {
        if ($user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN])) {
            return true;
        }

        if ($this->isAssignedTeacher($user, $classSubject)) {
            return true;
        }

        return $this->hasActiveDelegation($user, $classSubject);
    }

    public function view(User $user, AttendanceSession $session): bool
    {
        return $this->viewAny($user, $session->classSubject);
    }

    public function update(User $user, AttendanceSession $session): bool
    {
        if ($session->isFinalized()) {
            return false;
        }

        return $this->create($user, $session->classSubject);
    }

    public function updateRecords(User $user, AttendanceSession $session): bool
    {
        return $this->update($user, $session);
    }

    public function finalize(User $user, AttendanceSession $session): bool
    {
        if ($session->isFinalized()) {
            return false;
        }

        // Data Entry cannot finalize sessions (D-27 / F-05)
        if ($user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN])) {
            return true;
        }

        return $this->isAssignedTeacher($user, $session->classSubject);
    }

    public function delete(User $user, AttendanceSession $session): bool
    {
        if ($session->isFinalized()) {
            return false;
        }

        // Data Entry cannot delete sessions
        if ($user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN])) {
            return true;
        }

        return $this->isAssignedTeacher($user, $session->classSubject);
    }

    public function manageDelegations(User $user): bool
    {
        return $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN]);
    }

    public function isAssignedTeacher(User $user, ClassSubject $classSubject): bool
    {
        if (! $user->hasRole(Role::TEACHER)) {
            return false;
        }

        return $classSubject->activeTeachingAssignment?->teacher_id === $user->id;
    }

    public function hasActiveDelegation(User $user, ClassSubject $classSubject, ?Carbon $date = null): bool
    {
        if (! $user->hasRole(Role::DATA_ENTRY_ADMIN)) {
            return false;
        }

        $target = ($date ?? now())->toDateString();
        $assignmentId = $classSubject->activeTeachingAssignment?->id;

        return DataEntryDelegation::where('user_id', $user->id)
            ->where('teaching_assignment_id', $assignmentId)
            ->where('capability', 'attendance_entry')
            ->where('valid_from', '<=', $target)
            ->where('valid_until', '>=', $target)
            ->whereNull('revoked_at')
            ->exists();
    }
}
