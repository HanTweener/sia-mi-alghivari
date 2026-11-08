<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\HomeroomAssignment;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
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
            Role::DATA_ENTRY_ADMIN,
        ]);
    }

    public function export(User $user): bool
    {
        return $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::DATA_ENTRY_ADMIN, Role::PRINCIPAL]);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::DATA_ENTRY_ADMIN,
        ]);
    }

    public function viewProgress(User $user, Student $student, ?Enrollment $enrollment = null): bool
    {
        if ($enrollment && $enrollment->student_id !== $student->id) {
            return false;
        }

        if ($user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::PRINCIPAL])) {
            return true;
        }

        if ($user->hasRole(Role::GUARDIAN) && $student->guardians()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $activeClassroomIds = $enrollment
                ? [$enrollment->classroom_id]
                : $student->enrollments()->pluck('classroom_id');

            $isHomeroom = HomeroomAssignment::whereIn('classroom_id', $activeClassroomIds)
                ->where('teacher_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if ($isHomeroom) {
                return true;
            }

            return ClassSubject::whereIn('classroom_id', $activeClassroomIds)
                ->whereHas('teachingAssignments', function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)->active();
                })
                ->exists();
        }

        return false;
    }

    public function viewReportCards(User $user, Student $student): bool
    {
        if ($user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::PRINCIPAL])) {
            return true;
        }

        if ($user->hasRole(Role::GUARDIAN) && $student->guardians()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $activeClassroomIds = $student->enrollments()->pluck('classroom_id');

            return HomeroomAssignment::whereIn('classroom_id', $activeClassroomIds)
                ->where('teacher_id', $user->id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function viewAllProgress(User $user, Student $student, Enrollment $enrollment): bool
    {
        if ($enrollment->student_id !== $student->id) {
            return false;
        }

        return $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::PRINCIPAL])
            || ($user->hasRole(Role::GUARDIAN) && $student->guardians()->where('users.id', $user->id)->exists())
            || ($user->hasRole(Role::TEACHER) && HomeroomAssignment::where('classroom_id', $enrollment->classroom_id)
                ->where('teacher_id', $user->id)->where('status', 'active')->exists());
    }
}
