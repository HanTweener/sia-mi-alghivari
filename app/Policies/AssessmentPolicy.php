<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\DataEntryDelegation;
use App\Models\User;
use Carbon\Carbon;

class AssessmentPolicy
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

    public function view(User $user, Assessment $assessment): bool
    {
        return $this->viewAny($user, $assessment->classSubject);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        if ($assessment->isPublished()) {
            return false;
        }

        return $this->create($user, $assessment->classSubject);
    }

    public function updateScores(User $user, Assessment $assessment): bool
    {
        return $this->update($user, $assessment);
    }

    public function publish(User $user, Assessment $assessment): bool
    {
        if ($assessment->isPublished()) {
            return false;
        }

        // Data Entry cannot publish assessments (F-06 / Q6.3)
        if ($user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN])) {
            return true;
        }

        return $this->isAssignedTeacher($user, $assessment->classSubject);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        if ($assessment->isPublished()) {
            return false;
        }

        // Data Entry cannot delete assessments
        if ($user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN])) {
            return true;
        }

        return $this->isAssignedTeacher($user, $assessment->classSubject);
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
            ->where('capability', 'grade_entry')
            ->where('valid_from', '<=', $target)
            ->where('valid_until', '>=', $target)
            ->whereNull('revoked_at')
            ->exists();
    }
}
