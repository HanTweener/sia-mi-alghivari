<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
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

    public function view(User $user, ?Classroom $classroom = null): bool
    {
        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::DATA_ENTRY_ADMIN,
            Role::PRINCIPAL,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER) && $classroom) {
            $isHomeroom = $classroom->activeHomeroom?->teacher_id === $user->id;
            $teachesClass = $classroom->classSubjects()
                ->whereHas('teachingAssignments', fn ($q) => $q->where('teacher_id', $user->id)
                    ->active()
                )->exists();

            return $isHomeroom || $teachesClass;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function update(User $user, ?Classroom $classroom = null): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function manageHomeroom(User $user, ?Classroom $classroom = null): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function manageEnrollments(User $user, ?Classroom $classroom = null): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::DATA_ENTRY_ADMIN,
        ]);
    }

    public function manageTeaching(User $user, ?Classroom $classroom = null): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function withdrawEnrollment(User $user, Classroom $classroom): bool
    {
        return $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN]);
    }

    public function manageSchedule(User $user, ?Classroom $classroom = null): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    public function viewReportPreview(User $user, Classroom $classroom): bool
    {
        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            return $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }
}
