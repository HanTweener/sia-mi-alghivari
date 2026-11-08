<?php

namespace App\Services\Academic;

use App\Enums\AuditScope;
use App\Enums\ProgressionStatus;
use App\Enums\ProgressionType;
use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Exceptions\SourceDataChangedException;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentProgression;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgressionService
{
    public function __construct(
        protected AuditService $auditService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Create an individual progression proposal.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProposal(array $data, User $actor): StudentProgression
    {
        return DB::transaction(function () use ($data, $actor) {
            $student = Student::findOrFail($data['student_id']);
            $sourceAcademicYearId = (int) $data['source_academic_year_id'];

            // Check duplicate progression
            if (StudentProgression::where('student_id', $student->id)
                ->where('source_academic_year_id', $sourceAcademicYearId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'Usulan kenaikan/kelulusan untuk siswa ini sudah pernah dibuat pada tahun ajaran sumber terkait.',
                ]);
            }

            // Resolve and validate source report card
            $reportCard = null;
            if (! empty($data['source_report_card_id'])) {
                $reportCard = ReportCard::with('term')->findOrFail($data['source_report_card_id']);
            } else {
                $reportCard = ReportCard::whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id))
                    ->whereHas('term', fn ($q) => $q->where('academic_year_id', $sourceAcademicYearId))
                    ->where('status', ReportCardStatus::PUBLISHED)
                    ->where('availability', ReportCardAvailability::CURRENT)
                    ->latest('id')
                    ->first();
            }

            if (! $reportCard || ! $reportCard->isPublished() || ! $reportCard->isCurrent()) {
                throw ValidationException::withMessages([
                    'source_report_card_id' => 'Siswa belum memiliki rapor resmi terbit (published) yang valid untuk tahun ajaran ini.',
                ]);
            }

            $type = $data['type'] instanceof ProgressionType
                ? $data['type']
                : ProgressionType::from($data['type']);

            $targetAcademicYearId = $type === ProgressionType::GRADUATION ? null : ($data['target_academic_year_id'] ?? null);
            $targetClassroomId = in_array($type, [ProgressionType::GRADUATION, ProgressionType::RETENTION], true)
                ? null
                : ($data['target_classroom_id'] ?? null);

            $progression = StudentProgression::create([
                'student_id' => $student->id,
                'source_academic_year_id' => $sourceAcademicYearId,
                'source_report_card_id' => $reportCard->id,
                'type' => $type,
                'target_academic_year_id' => $targetAcademicYearId,
                'target_classroom_id' => $targetClassroomId,
                'status' => ProgressionStatus::DRAFT,
                'version' => 1,
                'proposed_by' => $actor->id,
                'reason' => $data['reason'] ?? null,
            ]);

            $this->auditService->record(
                actorUserId: $actor->id,
                action: 'PROGRESSION_CREATED',
                entityType: StudentProgression::class,
                entityId: $progression->id,
                scope: AuditScope::PROGRESSION,
                allowedChanges: [
                    'student_id' => ['after' => $student->id],
                    'type' => ['after' => $type->value],
                    'status' => ['after' => ProgressionStatus::DRAFT->value],
                    'source_academic_year_id' => ['after' => $sourceAcademicYearId],
                    'source_report_card_id' => ['after' => $reportCard->id],
                ],
                reason: $data['reason'] ?? 'Pembuatan draf usulan kenaikan/kelulusan'
            );

            return $progression;
        });
    }

    /**
     * Batch create progression proposals for a classroom.
     *
     * @param  array<string, mixed>  $commonData
     * @return array{created_count: int, skipped_count: int, progressions: Collection}
     */
    public function batchCreateProposals(int $classroomId, array $commonData, User $actor): array
    {
        return DB::transaction(function () use ($classroomId, $commonData, $actor) {
            $classroom = Classroom::with(['term.academicYear', 'enrollments.student'])->findOrFail($classroomId);
            $sourceAcademicYearId = (int) ($commonData['source_academic_year_id'] ?? $classroom->term->academic_year_id);

            $type = $commonData['type'] instanceof ProgressionType
                ? $commonData['type']
                : ProgressionType::from($commonData['type'] ?? 'promotion');

            $targetAcademicYearId = $type === ProgressionType::GRADUATION ? null : ($commonData['target_academic_year_id'] ?? null);
            $targetClassroomId = in_array($type, [ProgressionType::GRADUATION, ProgressionType::RETENTION], true)
                ? null
                : ($commonData['target_classroom_id'] ?? null);

            $createdCount = 0;
            $skippedCount = 0;
            $progressions = new Collection;

            $activeEnrollments = $classroom->enrollments->where('status', 'active');

            foreach ($activeEnrollments as $enrollment) {
                $studentId = $enrollment->student_id;

                // Check duplicate
                if (StudentProgression::where('student_id', $studentId)
                    ->where('source_academic_year_id', $sourceAcademicYearId)
                    ->exists()) {
                    $skippedCount++;

                    continue;
                }

                // Find published and current report card
                $reportCard = ReportCard::where('enrollment_id', $enrollment->id)
                    ->where('status', ReportCardStatus::PUBLISHED)
                    ->where('availability', ReportCardAvailability::CURRENT)
                    ->latest('id')
                    ->first();

                if (! $reportCard) {
                    $skippedCount++;

                    continue;
                }

                $progression = StudentProgression::create([
                    'student_id' => $studentId,
                    'source_academic_year_id' => $sourceAcademicYearId,
                    'source_report_card_id' => $reportCard->id,
                    'type' => $type,
                    'target_academic_year_id' => $targetAcademicYearId,
                    'target_classroom_id' => $targetClassroomId,
                    'status' => ProgressionStatus::DRAFT,
                    'version' => 1,
                    'proposed_by' => $actor->id,
                    'reason' => $commonData['reason'] ?? 'Usulan massal rombel '.$classroom->name,
                ]);

                $this->auditService->record(
                    actorUserId: $actor->id,
                    action: 'PROGRESSION_CREATED',
                    entityType: StudentProgression::class,
                    entityId: $progression->id,
                    scope: AuditScope::PROGRESSION,
                    allowedChanges: [
                        'student_id' => ['after' => $studentId],
                        'type' => ['after' => $type->value],
                        'status' => ['after' => ProgressionStatus::DRAFT->value],
                    ],
                    reason: 'Pembuatan draf usulan massal'
                );

                $progressions->push($progression);
                $createdCount++;
            }

            return [
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'progressions' => $progressions,
            ];
        });
    }

    /**
     * Update an existing draft/needs_review progression proposal.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProposal(
        StudentProgression $progression,
        array $data,
        int $expectedVersion,
        User $actor
    ): StudentProgression {
        return DB::transaction(function () use ($progression, $data, $expectedVersion, $actor) {
            if (! $progression->status->canBeEdited()) {
                throw ValidationException::withMessages([
                    'status' => 'Usulan dalam status ini tidak dapat diubah.',
                ]);
            }

            if ($progression->version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Data usulan telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
                ]);
            }

            $beforeState = [
                'type' => $progression->type->value,
                'target_academic_year_id' => $progression->target_academic_year_id,
                'target_classroom_id' => $progression->target_classroom_id,
                'status' => $progression->status->value,
                'reason' => $progression->reason,
            ];

            $type = isset($data['type'])
                ? ($data['type'] instanceof ProgressionType ? $data['type'] : ProgressionType::from($data['type']))
                : $progression->type;

            $targetAcademicYearId = $type === ProgressionType::GRADUATION
                ? null
                : (array_key_exists('target_academic_year_id', $data) ? $data['target_academic_year_id'] : $progression->target_academic_year_id);

            $targetClassroomId = in_array($type, [ProgressionType::GRADUATION, ProgressionType::RETENTION], true)
                ? null
                : (array_key_exists('target_classroom_id', $data) ? $data['target_classroom_id'] : $progression->target_classroom_id);

            $progression->type = $type;
            $progression->target_academic_year_id = $targetAcademicYearId;
            $progression->target_classroom_id = $targetClassroomId;

            if (array_key_exists('reason', $data)) {
                $progression->reason = $data['reason'];
            }

            // If it was needs_review, editing it resets back to draft
            if ($progression->status === ProgressionStatus::NEEDS_REVIEW) {
                $progression->status = ProgressionStatus::DRAFT;
            }

            $progression->version = $progression->version + 1;
            $progression->save();

            $this->auditService->record(
                actorUserId: $actor->id,
                action: 'PROGRESSION_UPDATED',
                entityType: StudentProgression::class,
                entityId: $progression->id,
                scope: AuditScope::PROGRESSION,
                allowedChanges: [
                    'type' => ['before' => $beforeState['type'], 'after' => $progression->type->value],
                    'target_classroom_id' => ['before' => $beforeState['target_classroom_id'], 'after' => $progression->target_classroom_id],
                    'status' => ['before' => $beforeState['status'], 'after' => $progression->status->value],
                ],
                reason: $data['reason'] ?? 'Pembaruan data draf usulan'
            );

            return $progression;
        });
    }

    /**
     * Delete/cancel a draft progression proposal.
     */
    public function deleteProposal(
        StudentProgression $progression,
        ?int $expectedVersion,
        User $actor
    ): void {
        DB::transaction(function () use ($progression, $expectedVersion, $actor) {
            if (! $progression->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya draf usulan yang dapat dibatalkan atau dihapus.',
                ]);
            }

            if ($expectedVersion !== null && $progression->version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Data usulan telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
                ]);
            }

            $id = $progression->id;
            $progression->delete();

            $this->auditService->record(
                actorUserId: $actor->id,
                action: 'PROGRESSION_DELETED',
                entityType: StudentProgression::class,
                entityId: $id,
                scope: AuditScope::PROGRESSION,
                allowedChanges: [
                    'id' => ['before' => $id, 'after' => null],
                ],
                reason: 'Pembatalan draf usulan kenaikan/kelulusan'
            );
        });
    }

    /**
     * Submit a draft progression proposal for Principal review.
     */
    public function submitProposal(
        StudentProgression $progression,
        int $expectedVersion,
        User $actor
    ): StudentProgression {
        return DB::transaction(function () use ($progression, $expectedVersion, $actor) {
            if (! $progression->status->canBeEdited()) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya usulan draf atau yang perlu ditinjau ulang yang dapat diajukan.',
                ]);
            }

            if ($progression->version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Data usulan telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
                ]);
            }

            // Verify source report card is still valid, published, and current
            $reportCard = $progression->sourceReportCard;
            if (! $reportCard || ! $reportCard->isPublished() || ! $reportCard->isCurrent()) {
                throw new SourceDataChangedException('Rapor sumber telah berubah, dicabut, atau belum berstatus published saat diajukan.');
            }

            $beforeStatus = $progression->status->value;
            $progression->status = ProgressionStatus::SUBMITTED;
            $progression->version = $progression->version + 1;
            $progression->save();

            $audit = $this->auditService->record(
                actorUserId: $actor->id,
                action: 'PROGRESSION_SUBMITTED',
                entityType: StudentProgression::class,
                entityId: $progression->id,
                scope: AuditScope::PROGRESSION,
                allowedChanges: [
                    'status' => ['before' => $beforeStatus, 'after' => ProgressionStatus::SUBMITTED->value],
                    'version' => ['before' => $expectedVersion, 'after' => $progression->version],
                ],
                reason: 'Pengajuan usulan untuk ditinjau oleh Kepala Madrasah'
            );

            // Notify Principals
            $principals = User::whereHas('userRoles', fn ($q) => $q->where('role', Role::PRINCIPAL->value))->get();
            foreach ($principals as $principal) {
                $this->notificationService->createInboxNotification(
                    recipient: $principal,
                    eventCategory: 'progression_submitted',
                    summary: "Usulan {$progression->type->label()} untuk siswa {$progression->student->name} telah diajukan dan menunggu persetujuan.",
                    sourceAudit: $audit,
                    scopeType: 'progression',
                    scopeId: $progression->id
                );
            }

            return $progression;
        });
    }

    /**
     * Approve a submitted or needs_review progression proposal (Decoupled Approval, AC-09.1).
     * Strictly Principal only. Does NOT mutate student enrollments or active classroom.
     *
     * @param  array<string, mixed>  $data
     */
    public function approveProposal(
        StudentProgression $progression,
        array $data,
        User $actor
    ): StudentProgression {
        return DB::transaction(function () use ($progression, $data, $actor) {
            if (! in_array($progression->status, [ProgressionStatus::SUBMITTED, ProgressionStatus::NEEDS_REVIEW], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Usulan tidak dalam status menunggu persetujuan.',
                ]);
            }

            $expectedVersion = (int) $data['expected_version'];
            if ($progression->version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Data usulan telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
                ]);
            }

            $reportCard = $progression->sourceReportCard;

            // Check expected source report id and revision if provided
            if (! empty($data['expected_source_report_id']) && (int) $data['expected_source_report_id'] !== $progression->source_report_card_id) {
                throw ValidationException::withMessages([
                    'expected_source_report_id' => 'ID rapor sumber tidak sesuai dengan ekspektasi approval.',
                ]);
            }

            if (! empty($data['expected_source_report_revision']) && (int) $data['expected_source_report_revision'] !== $reportCard?->revision) {
                throw ValidationException::withMessages([
                    'expected_source_report_revision' => 'Revisi rapor sumber telah berubah sejak usulan dibuat.',
                ]);
            }

            // Invariant: Source report card must still be published and current
            if (! $reportCard || ! $reportCard->isPublished() || ! $reportCard->isCurrent()) {
                $progression->status = ProgressionStatus::NEEDS_REVIEW;
                $progression->version = $progression->version + 1;
                $progression->save();

                throw new SourceDataChangedException('Rapor sumber telah berubah atau dicabut. Status usulan ditandai needs_review.');
            }

            $beforeStatus = $progression->status->value;
            $progression->status = ProgressionStatus::APPROVED;
            $progression->approved_by = $actor->id;
            $progression->approved_at = now();
            $progression->version = $progression->version + 1;
            $progression->save();

            $audit = $this->auditService->record(
                actorUserId: $actor->id,
                action: 'PROGRESSION_APPROVED',
                entityType: StudentProgression::class,
                entityId: $progression->id,
                scope: AuditScope::PROGRESSION,
                allowedChanges: [
                    'status' => ['before' => $beforeStatus, 'after' => ProgressionStatus::APPROVED->value],
                    'approved_by' => ['after' => $actor->id],
                    'approved_at' => ['after' => $progression->approved_at->toIso8601String()],
                ],
                reason: $data['reason'] ?? 'Persetujuan resmi usulan oleh Kepala Madrasah'
            );

            // Notify Proposer
            if ($progression->proposed_by) {
                $this->notificationService->createInboxNotification(
                    recipient: $progression->proposed_by,
                    eventCategory: 'progression_approved',
                    summary: "Usulan {$progression->type->label()} untuk {$progression->student->name} telah disetujui Kepala Madrasah.",
                    sourceAudit: $audit,
                    scopeType: 'progression',
                    scopeId: $progression->id
                );
            }

            return $progression;
        });
    }

    /**
     * Reject a submitted or needs_review progression proposal.
     * Strictly Principal only. Requires a clear rejection reason.
     *
     * @param  array<string, mixed>  $data
     */
    public function rejectProposal(
        StudentProgression $progression,
        array $data,
        User $actor
    ): StudentProgression {
        return DB::transaction(function () use ($progression, $data, $actor) {
            if (! in_array($progression->status, [ProgressionStatus::SUBMITTED, ProgressionStatus::NEEDS_REVIEW], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Usulan tidak dalam status menunggu persetujuan.',
                ]);
            }

            $expectedVersion = (int) $data['expected_version'];
            if ($progression->version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Data usulan telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
                ]);
            }

            $reason = trim($data['reason'] ?? '');
            if (strlen($reason) < 5) {
                throw ValidationException::withMessages([
                    'reason' => 'Alasan penolakan usulan wajib diisi minimal 5 karakter.',
                ]);
            }

            $beforeStatus = $progression->status->value;
            $progression->status = ProgressionStatus::REJECTED;
            $progression->rejection_reason = $reason;
            $progression->approved_by = $actor->id; // Recorded as the decision maker
            $progression->approved_at = now();
            $progression->version = $progression->version + 1;
            $progression->save();

            $audit = $this->auditService->record(
                actorUserId: $actor->id,
                action: 'PROGRESSION_REJECTED',
                entityType: StudentProgression::class,
                entityId: $progression->id,
                scope: AuditScope::PROGRESSION,
                allowedChanges: [
                    'status' => ['before' => $beforeStatus, 'after' => ProgressionStatus::REJECTED->value],
                    'rejection_reason' => ['after' => $reason],
                    'rejected_by' => ['after' => $actor->id],
                ],
                reason: "Penolakan usulan oleh Kepala Madrasah: {$reason}"
            );

            // Notify Proposer
            if ($progression->proposed_by) {
                $this->notificationService->createInboxNotification(
                    recipient: $progression->proposed_by,
                    eventCategory: 'progression_rejected',
                    summary: "Usulan {$progression->type->label()} untuk {$progression->student->name} ditolak: {$reason}.",
                    sourceAudit: $audit,
                    scopeType: 'progression',
                    scopeId: $progression->id
                );
            }

            return $progression;
        });
    }

    /**
     * Batch approve progression proposals atomically (all-or-nothing, AC-09.3).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{approved_count: int}
     */
    public function batchApproveProposals(array $items, User $actor): array
    {
        return DB::transaction(function () use ($items, $actor) {
            $approvedCount = 0;

            foreach ($items as $item) {
                $progression = StudentProgression::with('sourceReportCard')
                    ->lockForUpdate()
                    ->findOrFail($item['progression_id']);

                if (! in_array($progression->status, [ProgressionStatus::SUBMITTED, ProgressionStatus::NEEDS_REVIEW], true)) {
                    throw ValidationException::withMessages([
                        'items' => "Usulan ID {$progression->id} ({$progression->student->name}) tidak dalam status menunggu persetujuan.",
                    ]);
                }

                if ($progression->version !== (int) $item['expected_version']) {
                    throw ValidationException::withMessages([
                        'items' => "Versi usulan ID {$progression->id} ({$progression->student->name}) telah berubah. Seluruh batch dibatalkan.",
                    ]);
                }

                $reportCard = $progression->sourceReportCard;
                if (! $reportCard || ! $reportCard->isPublished() || ! $reportCard->isCurrent()) {
                    $progression->status = ProgressionStatus::NEEDS_REVIEW;
                    $progression->version = $progression->version + 1;
                    $progression->save();

                    throw new SourceDataChangedException("Rapor siswa {$progression->student->name} telah berubah atau dicabut. Batch persetujuan dibatalkan.");
                }

                $beforeStatus = $progression->status->value;
                $progression->status = ProgressionStatus::APPROVED;
                $progression->approved_by = $actor->id;
                $progression->approved_at = now();
                $progression->version = $progression->version + 1;
                $progression->save();

                $this->auditService->record(
                    actorUserId: $actor->id,
                    action: 'PROGRESSION_APPROVED',
                    entityType: StudentProgression::class,
                    entityId: $progression->id,
                    scope: AuditScope::PROGRESSION,
                    allowedChanges: [
                        'status' => ['before' => $beforeStatus, 'after' => ProgressionStatus::APPROVED->value],
                        'approved_by' => ['after' => $actor->id],
                    ],
                    reason: 'Persetujuan massal usulan oleh Kepala Madrasah'
                );

                $approvedCount++;
            }

            return ['approved_count' => $approvedCount];
        });
    }

    /**
     * Batch reject progression proposals atomically (all-or-nothing).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{rejected_count: int}
     */
    public function batchRejectProposals(array $items, string $reason, User $actor): array
    {
        return DB::transaction(function () use ($items, $reason, $actor) {
            $reason = trim($reason);
            if (strlen($reason) < 5) {
                throw ValidationException::withMessages([
                    'reason' => 'Alasan penolakan massal wajib diisi minimal 5 karakter.',
                ]);
            }

            $rejectedCount = 0;

            foreach ($items as $item) {
                $progression = StudentProgression::lockForUpdate()->findOrFail($item['progression_id']);

                if (! in_array($progression->status, [ProgressionStatus::SUBMITTED, ProgressionStatus::NEEDS_REVIEW], true)) {
                    throw ValidationException::withMessages([
                        'items' => "Usulan ID {$progression->id} tidak dalam status menunggu persetujuan.",
                    ]);
                }

                if ($progression->version !== (int) $item['expected_version']) {
                    throw ValidationException::withMessages([
                        'items' => "Versi usulan ID {$progression->id} telah berubah. Seluruh batch dibatalkan.",
                    ]);
                }

                $beforeStatus = $progression->status->value;
                $progression->status = ProgressionStatus::REJECTED;
                $progression->rejection_reason = $reason;
                $progression->approved_by = $actor->id;
                $progression->approved_at = now();
                $progression->version = $progression->version + 1;
                $progression->save();

                $this->auditService->record(
                    actorUserId: $actor->id,
                    action: 'PROGRESSION_REJECTED',
                    entityType: StudentProgression::class,
                    entityId: $progression->id,
                    scope: AuditScope::PROGRESSION,
                    allowedChanges: [
                        'status' => ['before' => $beforeStatus, 'after' => ProgressionStatus::REJECTED->value],
                        'rejection_reason' => ['after' => $reason],
                    ],
                    reason: "Penolakan massal oleh Kepala Madrasah: {$reason}"
                );

                $rejectedCount++;
            }

            return ['rejected_count' => $rejectedCount];
        });
    }

    /**
     * Invalidate any unapplied progressions referencing the given report card to needs_review.
     */
    public function invalidateProgressionsForReport(
        ReportCard $reportCard,
        string $reason,
        User $actor
    ): int {
        return DB::transaction(function () use ($reportCard, $reason, $actor) {
            $progressions = StudentProgression::where('source_report_card_id', $reportCard->id)
                ->whereIn('status', [ProgressionStatus::SUBMITTED, ProgressionStatus::APPROVED])
                ->get();

            $count = 0;
            foreach ($progressions as $progression) {
                $beforeStatus = $progression->status->value;
                $progression->status = ProgressionStatus::NEEDS_REVIEW;
                $progression->version = $progression->version + 1;
                $progression->save();

                $audit = $this->auditService->record(
                    actorUserId: $actor->id,
                    action: 'PROGRESSION_NEEDS_REVIEW',
                    entityType: StudentProgression::class,
                    entityId: $progression->id,
                    scope: AuditScope::PROGRESSION,
                    allowedChanges: [
                        'status' => ['before' => $beforeStatus, 'after' => ProgressionStatus::NEEDS_REVIEW->value],
                        'version' => ['after' => $progression->version],
                    ],
                    reason: $reason
                );

                // Notify Principals that progression now needs review
                $principals = User::whereHas('userRoles', fn ($q) => $q->where('role', Role::PRINCIPAL->value))->get();
                foreach ($principals as $principal) {
                    $this->notificationService->createInboxNotification(
                        recipient: $principal,
                        eventCategory: 'progression_needs_review',
                        summary: "Usulan {$progression->type->label()} untuk siswa {$progression->student->name} perlu ditinjau ulang karena rapor sumber berubah/direvisi.",
                        sourceAudit: $audit,
                        scopeType: 'progression',
                        scopeId: $progression->id
                    );
                }

                $count++;
            }

            return $count;
        });
    }

    /**
     * Apply an approved progression proposal (Master Admin or Academic Admin only, AC-09.2).
     * Mutates student enrollment or graduation status idempotently (apply-once).
     */
    public function applyProposal(
        StudentProgression $progression,
        int $expectedVersion,
        User $actor
    ): StudentProgression {
        // Invariant: Verify source report card is still published and current
        $reportCard = $progression->sourceReportCard;
        if (! $reportCard || ! $reportCard->isPublished() || ! $reportCard->isCurrent()) {
            $progression->update([
                'status' => ProgressionStatus::NEEDS_REVIEW,
                'version' => $progression->version + 1,
            ]);

            throw new SourceDataChangedException('Rapor sumber telah berubah atau dicabut. Status usulan ditandai needs_review dan penerapan dibatalkan.');
        }

        return DB::transaction(function () use ($progression, $expectedVersion, $actor) {
            $progression = StudentProgression::with(['student', 'sourceReportCard', 'targetClassroom.term'])
                ->lockForUpdate()
                ->findOrFail($progression->id);

            if ($progression->isApplied()) {
                throw ValidationException::withMessages([
                    'status' => 'Keputusan kenaikan/kelulusan ini sudah pernah diterapkan sebelumnya (apply-once).',
                ]);
            }

            if (! $progression->isApproved()) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya usulan yang telah disetujui (approved) oleh Kepala Madrasah yang dapat diterapkan.',
                ]);
            }

            if ($progression->version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Data usulan telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
                ]);
            }

            $student = $progression->student;

            if ($progression->isGraduation()) {
                // Graduate student
                $student->update(['status' => 'graduated']);
            } elseif ($progression->isPromotion() || $progression->isRetention()) {
                if (! $progression->target_classroom_id) {
                    throw ValidationException::withMessages([
                        'target_classroom_id' => 'Rombel tujuan wajib ditentukan sebelum usulan diterapkan.',
                    ]);
                }

                $targetClassroom = $progression->targetClassroom;
                $targetTermId = $targetClassroom->term_id;

                // Create new enrollment preserving history (AC-09.2)
                Enrollment::firstOrCreate([
                    'student_id' => $student->id,
                    'term_id' => $targetTermId,
                    'status' => 'active',
                ], [
                    'classroom_id' => $targetClassroom->id,
                    'starts_on' => $targetClassroom->term->starts_on ?? now(),
                ]);

                // Ensure student is active
                if ($student->status !== 'active') {
                    $student->update(['status' => 'active']);
                }
            }

            $beforeStatus = $progression->status->value;
            $progression->status = ProgressionStatus::APPLIED;
            $progression->applied_by = $actor->id;
            $progression->applied_at = now();
            $progression->version = $progression->version + 1;
            $progression->save();

            $audit = $this->auditService->record(
                actorUserId: $actor->id,
                action: 'PROGRESSION_APPLIED',
                entityType: StudentProgression::class,
                entityId: $progression->id,
                scope: AuditScope::PROGRESSION,
                allowedChanges: [
                    'status' => ['before' => $beforeStatus, 'after' => ProgressionStatus::APPLIED->value],
                    'applied_by' => ['after' => $actor->id],
                    'applied_at' => ['after' => $progression->applied_at->toIso8601String()],
                ],
                reason: 'Penerapan resmi mutasi kenaikan/kelulusan'
            );

            // Notify Principals
            $principals = User::whereHas('userRoles', fn ($q) => $q->where('role', Role::PRINCIPAL->value))->get();
            foreach ($principals as $principal) {
                $this->notificationService->createInboxNotification(
                    recipient: $principal,
                    eventCategory: 'progression_applied',
                    summary: "Keputusan {$progression->type->label()} untuk {$student->name} telah berhasil diterapkan.",
                    sourceAudit: $audit,
                    scopeType: 'progression',
                    scopeId: $progression->id
                );
            }

            return $progression;
        });
    }

    /**
     * Batch apply approved progression proposals atomically (all-or-nothing).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{applied_count: int}
     */
    public function batchApplyProposals(array $items, User $actor): array
    {
        return DB::transaction(function () use ($items, $actor) {
            $appliedCount = 0;

            foreach ($items as $item) {
                $progression = StudentProgression::with(['student', 'sourceReportCard', 'targetClassroom.term'])
                    ->lockForUpdate()
                    ->findOrFail($item['progression_id']);

                if ($progression->isApplied()) {
                    throw ValidationException::withMessages([
                        'items' => "Usulan ID {$progression->id} ({$progression->student->name}) sudah pernah diterapkan sebelumnya.",
                    ]);
                }

                if (! $progression->isApproved()) {
                    throw ValidationException::withMessages([
                        'items' => "Usulan ID {$progression->id} ({$progression->student->name}) belum disetujui Kepala Madrasah.",
                    ]);
                }

                if ($progression->version !== (int) $item['expected_version']) {
                    throw ValidationException::withMessages([
                        'items' => "Versi usulan ID {$progression->id} ({$progression->student->name}) telah berubah. Seluruh batch dibatalkan.",
                    ]);
                }

                $reportCard = $progression->sourceReportCard;
                if (! $reportCard || ! $reportCard->isPublished() || ! $reportCard->isCurrent()) {
                    $progression->status = ProgressionStatus::NEEDS_REVIEW;
                    $progression->version = $progression->version + 1;
                    $progression->save();

                    throw new SourceDataChangedException("Rapor siswa {$progression->student->name} telah berubah atau dicabut. Batch penerapan dibatalkan.");
                }

                $student = $progression->student;

                if ($progression->isGraduation()) {
                    $student->update(['status' => 'graduated']);
                } elseif ($progression->isPromotion() || $progression->isRetention()) {
                    if (! $progression->target_classroom_id) {
                        throw ValidationException::withMessages([
                            'items' => "Rombel tujuan untuk siswa {$student->name} belum ditentukan.",
                        ]);
                    }

                    $targetClassroom = $progression->targetClassroom;
                    $targetTermId = $targetClassroom->term_id;

                    Enrollment::firstOrCreate([
                        'student_id' => $student->id,
                        'term_id' => $targetTermId,
                    ], [
                        'classroom_id' => $targetClassroom->id,
                        'starts_on' => $targetClassroom->term->starts_on ?? now(),
                        'status' => 'active',
                    ]);

                    if ($student->status !== 'active') {
                        $student->update(['status' => 'active']);
                    }
                }

                $beforeStatus = $progression->status->value;
                $progression->status = ProgressionStatus::APPLIED;
                $progression->applied_by = $actor->id;
                $progression->applied_at = now();
                $progression->version = $progression->version + 1;
                $progression->save();

                $this->auditService->record(
                    actorUserId: $actor->id,
                    action: 'PROGRESSION_APPLIED',
                    entityType: StudentProgression::class,
                    entityId: $progression->id,
                    scope: AuditScope::PROGRESSION,
                    allowedChanges: [
                        'status' => ['before' => $beforeStatus, 'after' => ProgressionStatus::APPLIED->value],
                        'applied_by' => ['after' => $actor->id],
                    ],
                    reason: 'Penerapan massal kenaikan/kelulusan oleh staf administratif'
                );

                $appliedCount++;
            }

            return ['applied_count' => $appliedCount];
        });
    }
}
