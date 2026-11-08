<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\StudentProgression;
use App\Models\User;

class StudentProgressionPolicy
{
    /**
     * Determine whether the user can view any progressions.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::PRINCIPAL,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            return $user->homeroomAssignments()->where('status', 'active')->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can view a specific progression.
     */
    public function view(User $user, StudentProgression $progression): bool
    {
        if ($user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
            Role::PRINCIPAL,
        ])) {
            return true;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $classroom = $progression->sourceReportCard?->enrollment?->classroom;

            return $classroom && $classroom->activeHomeroom?->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create progression proposals.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ]);
    }

    /**
     * Determine whether the user can update a draft/needs_review progression.
     */
    public function update(User $user, StudentProgression $progression): bool
    {
        if (! $progression->status->canBeEdited()) {
            return false;
        }

        return $this->create($user);
    }

    /**
     * Determine whether the user can delete a draft progression.
     */
    public function delete(User $user, StudentProgression $progression): bool
    {
        if (! $progression->isDraft()) {
            return false;
        }

        return $this->create($user);
    }

    /**
     * Determine whether the user can submit a draft progression for review.
     */
    public function submit(User $user, StudentProgression $progression): bool
    {
        if (! $progression->status->canBeEdited()) {
            return false;
        }

        return $this->create($user);
    }

    /**
     * Determine whether the user can approve a progression (Decoupled Approval).
     * Strictly Principal only (AC-09.1).
     */
    public function approve(User $user, StudentProgression $progression): bool
    {
        if (! $user->hasRole(Role::PRINCIPAL)) {
            return false;
        }

        return $progression->isSubmitted() || $progression->isNeedsReview();
    }

    /**
     * Determine whether the user can reject a progression.
     * Strictly Principal only (AC-09.1).
     */
    public function reject(User $user, StudentProgression $progression): bool
    {
        if (! $user->hasRole(Role::PRINCIPAL)) {
            return false;
        }

        return $progression->isSubmitted() || $progression->isNeedsReview();
    }

    /**
     * Determine whether the user can apply an approved progression.
     * Administrative role (Master Admin or Academic Admin) only (AC-09.2).
     */
    public function apply(User $user, StudentProgression $progression): bool
    {
        if (! $user->hasAnyRole([
            Role::MASTER_ADMIN,
            Role::ACADEMIC_ADMIN,
        ])) {
            return false;
        }

        return $progression->isApproved();
    }
}
