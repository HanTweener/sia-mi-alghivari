<?php

namespace App\Services\Reports;

use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Exceptions\SourceDataChangedException;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\GradingPolicy;
use App\Models\ReportCard;
use App\Models\ReportCardItem;
use App\Models\Score;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Grading\GradingCalculator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReportSnapshotService
{
    public const CALCULATION_VERSION = '1.0';

    public function __construct(
        protected GradingCalculator $calculator
    ) {}

    /**
     * Compute comprehensive snapshot data for an enrollment's semester report card.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function calculateSnapshot(Enrollment $enrollment, ?GradingPolicy $policy = null): array
    {
        $policy = $policy ?? GradingPolicy::where('status', 'active')->with('components.scoringComponent')->first();

        if (! $policy) {
            throw new RuntimeException('Tidak ada kebijakan penilaian (grading policy) yang aktif.');
        }

        $enrollment->loadMissing([
            'classroom.classSubjects.subject',
            'classroom.activeHomeroom.teacher',
            'term.academicYear',
            'student',
        ]);

        $classroom = $enrollment->classroom;
        $classSubjects = $classroom->classSubjects->sortBy(
            fn (ClassSubject $cs) => $cs->subject->sort_order ?? $cs->subject->order ?? $cs->subject->name
        )->values();

        $classSubjectIds = $classSubjects->pluck('id')->all();

        // Determine all enrollments for this student in this term (handles mid-term transfers)
        $termEnrollmentIds = Enrollment::where('student_id', $enrollment->student_id)
            ->where('term_id', $enrollment->term_id)
            ->pluck('id')
            ->all();

        $matchingClassSubjectIds = ClassSubject::whereIn('subject_id', $classSubjects->pluck('subject_id'))
            ->whereIn('classroom_id', function ($query) use ($enrollment) {
                $query->select('classroom_id')
                    ->from('enrollments')
                    ->where('student_id', $enrollment->student_id)
                    ->where('term_id', $enrollment->term_id);
            })
            ->pluck('id')
            ->all();

        // 1. Published assessments & scores for this student (across relevant enrollments in term)
        $publishedScores = Score::whereIn('enrollment_id', $termEnrollmentIds)
            ->whereHas('assessment', function ($q) use ($matchingClassSubjectIds) {
                $q->where('status', Assessment::STATUS_PUBLISHED)
                    ->whereIn('class_subject_id', $matchingClassSubjectIds);
            })
            ->with(['assessment.scoringComponent', 'assessment.classSubject'])
            ->get();

        // 2. Finalized attendance sessions & records for this student
        $finalizedAttendance = AttendanceRecord::whereIn('enrollment_id', $termEnrollmentIds)
            ->whereHas('attendanceSession', function ($q) use ($matchingClassSubjectIds) {
                $q->where('status', 'finalized')
                    ->whereIn('class_subject_id', $matchingClassSubjectIds);
            })
            ->with(['attendanceSession.classSubject'])
            ->get();

        $items = [];
        $isAllComplete = true;
        $blockedReasons = [];
        $subjectsRawData = [];

        $totalAllSessions = 0;
        $totalAllPresent = 0;
        $totalAllExcused = 0;
        $totalAllSick = 0;
        $totalAllAbsent = 0;

        foreach ($classSubjects as $cs) {
            // Group published scores by scoring component code (matching subject_id)
            $subjectScores = $publishedScores->filter(
                fn (Score $s) => $s->assessment->classSubject?->subject_id === $cs->subject_id
            );

            $scoresByComponent = [];
            $assessmentRawItems = [];

            foreach ($subjectScores as $score) {
                $compCode = $score->assessment->scoringComponent?->code ?? "comp_{$score->assessment->scoring_component_id}";
                $scoresByComponent[$compCode][] = (string) $score->score;

                $assessmentRawItems[] = [
                    'assessment_id' => $score->assessment_id,
                    'version' => $score->assessment->version,
                    'component_id' => $score->assessment->scoring_component_id,
                    'assessed_on' => (string) $score->assessment->assessed_on,
                    'updated_at' => $score->assessment->updated_at?->toIso8601String(),
                    'score_id' => $score->id,
                    'score' => (string) $score->score,
                    'score_updated_at' => $score->updated_at?->toIso8601String(),
                ];
            }

            // Finalized attendance records for this subject (matching subject_id)
            $subjectAtt = $finalizedAttendance->filter(
                fn (AttendanceRecord $ar) => $ar->attendanceSession?->classSubject?->subject_id === $cs->subject_id
            );

            $sessCount = $subjectAtt->count();
            $presCount = $subjectAtt->where('status', 'present')->count();
            $excCount = $subjectAtt->where('status', 'excused')->count();
            $sickCount = $subjectAtt->where('status', 'sick')->count();
            $absCount = $subjectAtt->where('status', 'absent')->count();

            $totalAllSessions += $sessCount;
            $totalAllPresent += $presCount;
            $totalAllExcused += $excCount;
            $totalAllSick += $sickCount;
            $totalAllAbsent += $absCount;

            $attRate = $sessCount > 0
                ? GradingCalculator::roundHalfUp(bcdiv(bcmul((string) $presCount, '100', 10), (string) $sessCount, 10), 2)
                : null;

            $attendanceRecap = [
                'total_sessions' => $sessCount,
                'present' => $presCount,
                'excused' => $excCount,
                'sick' => $sickCount,
                'absent' => $absCount,
                'percentage' => $attRate !== null ? (float) $attRate : null,
                'rate' => $attRate,
            ];

            $attendanceRawItems = [];
            foreach ($subjectAtt as $ar) {
                $attendanceRawItems[] = [
                    'session_id' => $ar->attendance_session_id,
                    'version' => $ar->attendanceSession->version,
                    'meeting_number' => $ar->attendanceSession->meeting_number,
                    'held_on' => (string) $ar->attendanceSession->held_on,
                    'session_updated_at' => $ar->attendanceSession->updated_at?->toIso8601String(),
                    'record_id' => $ar->id,
                    'status' => $ar->status,
                    'record_updated_at' => $ar->updated_at?->toIso8601String(),
                ];
            }

            // Calculate grades via GradingCalculator
            $calcResult = $this->calculator->calculate($policy, $scoresByComponent, $attendanceRecap);

            // Teaching history snapshot
            $teachingHistory = $cs->teachingAssignments()
                ->with('teacher')
                ->orderBy('starts_on')
                ->get()
                ->map(fn (TeachingAssignment $ta) => [
                    'assignment_id' => $ta->id,
                    'teacher_id' => $ta->teacher_id,
                    'teacher_name' => $ta->teacher?->name,
                    'teacher_nip' => $ta->teacher?->nip,
                    'starts_on' => $ta->starts_on instanceof \DateTimeInterface ? $ta->starts_on->format('Y-m-d') : (string) $ta->starts_on,
                    'ends_on' => $ta->ends_on instanceof \DateTimeInterface ? $ta->ends_on->format('Y-m-d') : ($ta->ends_on ? (string) $ta->ends_on : null),
                    'status' => $ta->status,
                ])
                ->values()
                ->all();

            $subjectSnapshot = [
                'id' => $cs->subject->id,
                'code' => $cs->subject->code,
                'name' => $cs->subject->name,
                'order' => $cs->subject->sort_order ?? $cs->subject->order ?? 0,
            ];

            $componentBreakdown = array_map(fn ($c) => $c->toArray(), $calcResult->components);

            $items[] = [
                'class_subject_id' => $cs->id,
                'subject_snapshot' => $subjectSnapshot,
                'teaching_history_snapshot' => $teachingHistory,
                'component_breakdown_snapshot' => $componentBreakdown,
                'kkm_snapshot' => $calcResult->kkm,
                'final_score' => $calcResult->finalScore,
                'kkm_status' => $calcResult->kkmStatus,
                'is_complete' => $calcResult->isComplete,
                'missing_components' => $calcResult->missingComponents,
                'attendance' => $attendanceRecap,
            ];

            if (! $calcResult->isComplete) {
                $isAllComplete = false;
                $compList = implode(', ', $calcResult->missingComponents);
                $blockedReasons[] = "Mata pelajaran {$cs->subject->name} belum lengkap (komponen {$compList} belum memiliki nilai atau presensi sah).";
            }

            $subjectsRawData[] = [
                'class_subject_id' => $cs->id,
                'assessments' => $assessmentRawItems,
                'attendance' => $attendanceRawItems,
            ];
        }

        if ($classSubjects->isEmpty()) {
            $isAllComplete = false;
            $blockedReasons[] = 'Rombel belum memiliki mata pelajaran terdaftar.';
        }

        // Attendance summary across all subjects
        $overallAttendanceRate = $totalAllSessions > 0
            ? GradingCalculator::roundHalfUp(bcdiv(bcmul((string) $totalAllPresent, '100', 10), (string) $totalAllSessions, 10), 2)
            : null;

        $attendanceSummary = [
            'total_sessions' => $totalAllSessions,
            'present' => $totalAllPresent,
            'excused' => $totalAllExcused,
            'sick' => $totalAllSick,
            'absent' => $totalAllAbsent,
            'percentage' => $overallAttendanceRate !== null ? (float) $overallAttendanceRate : null,
            'rate' => $overallAttendanceRate,
        ];

        // Consistency token (source_version)
        $sourceVersion = $this->computeSourceVersion($enrollment, $policy, $subjectsRawData);

        $transferInfo = null;
        if ($enrollment->transferred_from_enrollment_id) {
            $prevEnrollment = $enrollment->transferredFrom()->with('classroom')->first();
            $transferInfo = [
                'is_transferred' => true,
                'source_classroom_id' => $prevEnrollment?->classroom_id,
                'source_classroom_name' => $prevEnrollment?->classroom?->full_name,
                'transfer_date' => $enrollment->starts_on?->toDateString(),
                'reason' => $enrollment->transfer_reason,
            ];
        }

        return [
            'enrollment_id' => $enrollment->id,
            'term_id' => $enrollment->term_id,
            'classroom_id' => $enrollment->classroom_id,
            'grading_policy_id' => $policy->id,
            'policy_revision' => $policy->revision,
            'homeroom_assignment_id' => $classroom->activeHomeroom?->id,
            'calculation_version' => self::CALCULATION_VERSION,
            'source_version' => $sourceVersion,
            'is_complete' => $isAllComplete,
            'can_finalize' => $isAllComplete && empty($blockedReasons),
            'blocked_reasons' => $blockedReasons,
            'attendance_summary' => $attendanceSummary,
            'items' => $items,
            'transfer_info' => $transferInfo,
        ];
    }

    /**
     * Compute deterministic SHA-256 source consistency token.
     *
     * @param  array<int, array<string, mixed>>  $subjectsRawData
     */
    public function computeSourceVersion(
        Enrollment $enrollment,
        GradingPolicy $policy,
        array $subjectsRawData
    ): string {
        $canonical = [
            'policy_id' => $policy->id,
            'policy_revision' => $policy->revision,
            'policy_kkm' => (string) $policy->kkm,
            'policy_components' => $policy->components->map(fn ($c) => [
                'component_id' => $c->scoring_component_id,
                'weight' => (string) $c->weight_percentage,
            ])->values()->all(),
            'enrollment_id' => $enrollment->id,
            'student_id' => $enrollment->student_id,
            'classroom_id' => $enrollment->classroom_id,
            'term_id' => $enrollment->term_id,
            'subjects' => $subjectsRawData,
        ];

        return 'src_'.hash('sha256', (string) json_encode($canonical));
    }

    /**
     * Build or update a draft ReportCard and its ReportCardItems in an atomic transaction.
     *
     * @throws RuntimeException
     */
    public function buildDraft(
        Enrollment $enrollment,
        ?string $homeroomNote = null,
        ?GradingPolicy $policy = null,
        bool $requireComplete = false,
        ?int $supersedesId = null,
        ?string $lifecycleReason = null
    ): ReportCard {
        return DB::transaction(function () use ($enrollment, $homeroomNote, $policy, $requireComplete, $supersedesId, $lifecycleReason) {
            $snapshot = $this->calculateSnapshot($enrollment, $policy);

            if ($requireComplete && ! $snapshot['is_complete']) {
                $compList = implode('; ', $snapshot['blocked_reasons']);
                throw new RuntimeException("Rapor siswa {$enrollment->student->name} belum lengkap: {$compList}");
            }

            // Find existing draft report card for this enrollment
            $reportCard = ReportCard::where('enrollment_id', $enrollment->id)
                ->where('status', ReportCardStatus::DRAFT)
                ->first();

            if (! $reportCard) {
                $latestRevision = (int) (ReportCard::where('enrollment_id', $enrollment->id)->max('revision') ?? 0);
                $revision = $latestRevision + 1;

                $reportCard = ReportCard::create([
                    'enrollment_id' => $enrollment->id,
                    'term_id' => $enrollment->term_id,
                    'grading_policy_id' => $snapshot['grading_policy_id'],
                    'homeroom_assignment_id' => $snapshot['homeroom_assignment_id'],
                    'revision' => $revision,
                    'supersedes_id' => $supersedesId,
                    'status' => ReportCardStatus::DRAFT,
                    'availability' => ReportCardAvailability::CURRENT,
                    'version' => 1,
                    'source_version' => $snapshot['source_version'],
                    'homeroom_note' => $homeroomNote,
                    'calculation_version' => $snapshot['calculation_version'],
                    'generated_at' => now(),
                    'attendance_summary' => $snapshot['attendance_summary'],
                    'lifecycle_reason' => $lifecycleReason,
                ]);
            } else {
                $reportCard->update([
                    'grading_policy_id' => $snapshot['grading_policy_id'],
                    'homeroom_assignment_id' => $snapshot['homeroom_assignment_id'],
                    'source_version' => $snapshot['source_version'],
                    'calculation_version' => $snapshot['calculation_version'],
                    'generated_at' => now(),
                    'attendance_summary' => $snapshot['attendance_summary'],
                    'homeroom_note' => $homeroomNote !== null ? $homeroomNote : $reportCard->homeroom_note,
                    'supersedes_id' => $supersedesId ?? $reportCard->supersedes_id,
                    'lifecycle_reason' => $lifecycleReason ?? $reportCard->lifecycle_reason,
                ]);

                $reportCard->items()->delete();
            }

            // Create items
            foreach ($snapshot['items'] as $itemData) {
                ReportCardItem::create([
                    'report_card_id' => $reportCard->id,
                    'class_subject_id' => $itemData['class_subject_id'],
                    'subject_snapshot' => $itemData['subject_snapshot'],
                    'teaching_history_snapshot' => $itemData['teaching_history_snapshot'],
                    'component_breakdown_snapshot' => $itemData['component_breakdown_snapshot'],
                    'kkm_snapshot' => $itemData['kkm_snapshot'],
                    'final_score' => $itemData['final_score'],
                    'kkm_status' => $itemData['kkm_status'],
                ]);
            }

            return $reportCard->load(['items', 'gradingPolicy', 'homeroomAssignment.teacher']);
        });
    }

    /**
     * Finalize report card snapshot into an immutable record.
     *
     * @throws RuntimeException|SourceDataChangedException
     */
    public function finalizeReportCard(
        ReportCard $reportCard,
        User $actor,
        ?string $expectedSourceVersion = null
    ): ReportCard {
        return DB::transaction(function () use ($reportCard, $actor, $expectedSourceVersion) {
            if (! $reportCard->isDraft()) {
                throw new RuntimeException('Hanya rapor berstatus draf yang dapat difinalisasi.');
            }

            $freshSnapshot = $this->calculateSnapshot($reportCard->enrollment, $reportCard->gradingPolicy);

            if (! $freshSnapshot['is_complete'] || ! $freshSnapshot['can_finalize']) {
                $reasons = implode('; ', $freshSnapshot['blocked_reasons']);
                throw new RuntimeException("Rapor belum lengkap sehingga tidak dapat difinalisasi: {$reasons}");
            }

            // Verify source data consistency
            if ($expectedSourceVersion !== null && $expectedSourceVersion !== $freshSnapshot['source_version']) {
                throw new SourceDataChangedException(
                    'Sumber nilai atau presensi telah berubah sejak peninjauan terakhir. Silakan tinjau ulang preview rapor.',
                    expectedSourceVersion: $expectedSourceVersion,
                    actualSourceVersion: $freshSnapshot['source_version']
                );
            }

            if ($reportCard->source_version !== null && $reportCard->source_version !== $freshSnapshot['source_version']) {
                throw new SourceDataChangedException(
                    'Sumber nilai atau presensi telah berubah sejak draf dibuat. Silakan tinjau ulang preview rapor.',
                    expectedSourceVersion: $reportCard->source_version,
                    actualSourceVersion: $freshSnapshot['source_version']
                );
            }

            // Gather principal and school snapshot
            $principal = User::whereHas('userRoles', fn ($q) => $q->where('role', Role::PRINCIPAL->value))->first();
            $enrollment = $reportCard->enrollment;
            $classroom = $enrollment->classroom;
            $student = $enrollment->student;
            $term = $reportCard->term;
            $homeroomTeacher = $reportCard->homeroomAssignment?->teacher;

            $identitySnapshot = [
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn ?? null,
                    'name' => $student->name,
                    'gender' => $student->gender,
                ],
                'classroom' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'grade_level' => $classroom->grade_level,
                    'section' => $classroom->section,
                ],
                'term' => [
                    'id' => $term->id,
                    'name' => $term->name,
                    'semester' => $term->semester,
                    'academic_year' => $term->academicYear?->name,
                ],
                'homeroom' => [
                    'id' => $homeroomTeacher?->id,
                    'name' => $homeroomTeacher?->name,
                    'nip' => $homeroomTeacher?->nip,
                ],
                'principal' => [
                    'name' => $principal?->name ?? 'Kepala Madrasah',
                    'nip' => $principal?->nip,
                ],
                'school' => [
                    'name' => 'MI Al-Ghivari',
                    'npsn' => '69900000',
                    'address' => 'Kota Bandung',
                ],
                'transfer' => $freshSnapshot['transfer_info'] ?? null,
            ];

            // Re-sync items with fresh snapshot to ensure complete frozen snapshot integrity
            $reportCard->items()->delete();
            foreach ($freshSnapshot['items'] as $itemData) {
                ReportCardItem::create([
                    'report_card_id' => $reportCard->id,
                    'class_subject_id' => $itemData['class_subject_id'],
                    'subject_snapshot' => $itemData['subject_snapshot'],
                    'teaching_history_snapshot' => $itemData['teaching_history_snapshot'],
                    'component_breakdown_snapshot' => $itemData['component_breakdown_snapshot'],
                    'kkm_snapshot' => $itemData['kkm_snapshot'],
                    'final_score' => $itemData['final_score'],
                    'kkm_status' => $itemData['kkm_status'],
                ]);
            }

            // Update report card metadata to finalized
            $reportCard->update([
                'status' => ReportCardStatus::FINALIZED,
                'availability' => ReportCardAvailability::CURRENT,
                'finalized_at' => now(),
                'finalized_by' => $actor->id,
                'source_version' => $freshSnapshot['source_version'],
                'attendance_summary' => $freshSnapshot['attendance_summary'],
                'identity_snapshot' => $identitySnapshot,
                'version' => $reportCard->version + 1,
            ]);

            return $reportCard->load(['items', 'gradingPolicy', 'homeroomAssignment.teacher', 'finalizedBy']);
        });
    }
}
