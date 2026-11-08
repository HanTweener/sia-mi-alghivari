<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Models\User;

class ReportCardPolicy
{
    /**
     * Determine whether the user can view the report card detail.
     */
    public function view(User $user, ReportCard $reportCard): bool
    {
        return $this->viewStaff($user, $reportCard)
            || ($user->hasRole(Role::GUARDIAN) && $reportCard->isPublished() && $reportCard->isCurrent()
                && $user->children()->where('students.id', $reportCard->enrollment?->student_id)->exists());
    }

    public function viewStaff(User $user, ReportCard $reportCard): bool
    {
        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::PRINCIPAL,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $reportCard->enrollment?->classroom;

            return $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create draft report cards for a classroom.
     */
    public function create(User $user, Classroom $classroom): bool
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

    /**
     * Determine whether the user can update the report card (e.g. homeroom note).
     */
    public function update(User $user, ReportCard $reportCard): bool
    {
        if (! $reportCard->isDraft()) {
            return false;
        }

        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $reportCard->enrollment?->classroom;

            return $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }

    public function updateHomeroomNote(User $user, ReportCard $reportCard): bool
    {
        return $reportCard->isDraft()
            && ($user->hasRole(Role::MASTER_ADMIN)
                || ($user->hasRole(Role::TEACHER)
                    && $reportCard->enrollment?->classroom?->activeHomeroom?->teacher_id === $user->id));
    }

    /**
     * Determine whether the user can finalize the report card snapshot.
     */
    public function finalize(User $user, ReportCard $reportCard): bool
    {
        if (! $reportCard->isDraft()) {
            return false;
        }

        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $reportCard->enrollment?->classroom;

            return $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can publish the report card.
     */
    public function publish(User $user, ReportCard $reportCard): bool
    {
        if (! $reportCard->isFinalized()) {
            return false;
        }

        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $reportCard->enrollment?->classroom;

            return $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create a new revision draft of the report card.
     */
    public function createRevision(User $user, ReportCard $reportCard): bool
    {
        if ($reportCard->isDraft()) {
            return false;
        }

        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $reportCard->enrollment?->classroom;

            return $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can revoke a published or finalized report card.
     */
    public function revoke(User $user, ReportCard $reportCard): bool
    {
        if ($reportCard->isDraft() || $reportCard->isRevoked()) {
            return false;
        }

        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $reportCard->enrollment?->classroom;

            return $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can render and prepare the PDF file.
     */
    public function prepareFile(User $user, ReportCard $reportCard): bool
    {
        if (! $reportCard->isFinalized() && ! $reportCard->isPublished()) {
            return false;
        }

        if (! $reportCard->isCurrent()) {
            return false;
        }

        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $reportCard->enrollment?->classroom;

            return $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can download the PDF file.
     */
    public function downloadPdf(User $user, ReportCard $reportCard): bool
    {
        // Staff access
        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::PRINCIPAL,
        ])) {
            return $reportCard->isFinalized() || $reportCard->isPublished();
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $reportCard->enrollment?->classroom;
            $isHomeroom = $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;

            if ($isHomeroom && ($reportCard->isFinalized() || $reportCard->isPublished())) {
                return true;
            }
        }

        // Guardian access (only published and current reports of their own children)
        if ($user->hasRole(Role::GUARDIAN)) {
            if (! $reportCard->isPublished() || ! $reportCard->isCurrent()) {
                return false;
            }

            return $user->children()
                ->where('students.id', $reportCard->enrollment?->student_id)
                ->exists();
        }

        return false;
    }
}
