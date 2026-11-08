<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Exceptions\SourceDataChangedException;
use App\Http\Controllers\Controller;
use App\Jobs\SendExternalNotificationJob;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularAssessment;
use App\Models\GradingPolicy;
use App\Models\P5Assessment;
use App\Models\ReportCard;
use App\Services\Academic\ProgressionService;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use App\Services\Reports\ReportPdfService;
use App\Services\Reports\ReportSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ReportCardController extends Controller
{
    public function __construct(
        protected ReportSnapshotService $reportSnapshotService,
        protected ReportPdfService $reportPdfService,
        protected AuditService $auditService,
        protected ProgressionService $progressionService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Display report card evaluation and preview for a classroom (SCR-08).
     */
    public function preview(Request $request, Classroom $classroom): Response|JsonResponse
    {
        $this->authorize('viewReportPreview', $classroom);

        $classroom->loadMissing([
            'term.academicYear',
            'activeHomeroom.teacher',
            'classSubjects.subject',
        ]);

        $policy = GradingPolicy::where('status', 'active')
            ->with('components.scoringComponent')
            ->first();

        $enrollments = $classroom->enrollments()
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->sortBy(fn (Enrollment $e) => $e->student->name)
            ->values();

        $studentsEvaluations = [];
        $completeCount = 0;
        $incompleteCount = 0;

        if ($policy) {
            $existingReports = ReportCard::whereIn('enrollment_id', $enrollments->pluck('id'))
                ->orderByDesc('revision')
                ->get()
                ->groupBy('enrollment_id');

            foreach ($enrollments as $enrollment) {
                $snapshot = $this->reportSnapshotService->calculateSnapshot($enrollment, $policy);

                $existingReport = $existingReports->get($enrollment->id)?->first();

                if ($snapshot['is_complete']) {
                    $completeCount++;
                } else {
                    $incompleteCount++;
                }

                $studentsEvaluations[] = [
                    'enrollment_id' => $enrollment->id,
                    'student' => [
                        'id' => $enrollment->student->id,
                        'nis' => $enrollment->student->nis,
                        'name' => $enrollment->student->name,
                        'gender' => $enrollment->student->gender,
                    ],
                    'is_complete' => $snapshot['is_complete'],
                    'can_finalize' => $snapshot['can_finalize'],
                    'source_version' => $snapshot['source_version'],
                    'blocked_reasons' => $snapshot['blocked_reasons'],
                    'attendance_summary' => $snapshot['attendance_summary'],
                    'subjects' => array_map(function ($item) {
                        return [
                            'class_subject_id' => $item['class_subject_id'],
                            'subject_id' => $item['subject_snapshot']['id'],
                            'subject_name' => $item['subject_snapshot']['name'],
                            'subject_code' => $item['subject_snapshot']['code'],
                            'kkm' => $item['kkm_snapshot'],
                            'final_score' => $item['final_score'],
                            'kkm_status' => $item['kkm_status'],
                            'is_complete' => $item['is_complete'],
                            'missing_components' => $item['missing_components'],
                            'attendance' => $item['attendance'],
                        ];
                    }, $snapshot['items']),
                    'existing_report' => $existingReport ? [
                        'id' => $existingReport->id,
                        'status' => $existingReport->status->value,
                        'availability' => $existingReport->availability->value,
                        'revision' => $existingReport->revision,
                        'version' => $existingReport->version,
                        'source_version' => $existingReport->source_version,
                        'homeroom_note' => $existingReport->homeroom_note,
                        'finalized_at' => $existingReport->finalized_at?->format('Y-m-d H:i'),
                        'published_at' => $existingReport->published_at?->format('Y-m-d H:i'),
                    ] : null,
                ];
            }
        }

        $classSubjects = $classroom->classSubjects
            ->sortBy(fn ($cs) => $cs->subject->sort_order ?? $cs->subject->order ?? $cs->subject->name)
            ->map(fn ($cs) => [
                'id' => $cs->id,
                'subject_id' => $cs->subject->id,
                'code' => $cs->subject->code,
                'name' => $cs->subject->name,
            ])
            ->values();

        $data = [
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'section' => $classroom->section,
                'term' => [
                    'id' => $classroom->term->id,
                    'name' => $classroom->term->name,
                    'semester' => $classroom->term->semester,
                    'academic_year' => $classroom->term->academicYear?->name,
                ],
                'homeroom' => $classroom->activeHomeroom?->teacher ? [
                    'id' => $classroom->activeHomeroom->teacher->id,
                    'name' => $classroom->activeHomeroom->teacher->name,
                    'nip' => $classroom->activeHomeroom->teacher->nip,
                ] : null,
            ],
            'policy' => $policy ? [
                'id' => $policy->id,
                'revision' => $policy->revision,
                'kkm' => (string) $policy->kkm,
                'components' => $policy->components->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->scoringComponent?->code,
                    'name' => $c->scoringComponent?->name,
                    'weight_percentage' => (string) $c->weight_percentage,
                ]),
            ] : null,
            'summary' => [
                'total_students' => $enrollments->count(),
                'complete_students' => $completeCount,
                'incomplete_students' => $incompleteCount,
                'is_all_complete' => $incompleteCount === 0 && $enrollments->count() > 0,
            ],
            'classSubjects' => $classSubjects,
            'students' => $studentsEvaluations,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Academic/Reports/ClassroomPreview', $data);
    }

    /**
     * Create draft report cards for complete students in a classroom.
     */
    public function storeDrafts(Request $request, Classroom $classroom): RedirectResponse|JsonResponse
    {
        $this->authorize('create', [ReportCard::class, $classroom]);

        $policy = GradingPolicy::where('status', 'active')
            ->with('components.scoringComponent')
            ->first();

        if (! $policy) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Tidak ada kebijakan penilaian (grading policy) yang aktif.'], 422);
            }

            return back()->withErrors(['_form' => 'Tidak ada kebijakan penilaian (grading policy) yang aktif.']);
        }

        $enrollmentId = $request->input('enrollment_id');

        if ($enrollmentId) {
            $enrollment = $classroom->enrollments()
                ->where('id', $enrollmentId)
                ->where('status', 'active')
                ->with('student')
                ->firstOrFail();

            try {
                $reportCard = $this->auditService->executeAtomic(
                    function () use ($enrollment, $policy) {
                        return $this->reportSnapshotService->buildDraft($enrollment, policy: $policy, requireComplete: true);
                    },
                    function (ReportCard $rc) use ($request, $enrollment) {
                        return [
                            'actor_id' => $request->user()->id,
                            'action' => 'CREATE_DRAFT_REPORT',
                            'entity_type' => ReportCard::class,
                            'entity_id' => $rc->id,
                            'scope' => AuditScope::REPORT,
                            'changes' => [
                                'enrollment_id' => $enrollment->id,
                                'student_name' => $enrollment->student->name,
                                'revision' => $rc->revision,
                                'status' => $rc->status->value,
                            ],
                            'reason' => 'Pembuatan draf rapor semester siswa',
                        ];
                    }
                )['result'];

                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => "Draf rapor untuk {$enrollment->student->name} berhasil dibuat.",
                        'report_card' => $reportCard,
                    ]);
                }

                return back()->with('success', "Draf rapor untuk {$enrollment->student->name} berhasil dibuat.");
            } catch (RuntimeException $e) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => $e->getMessage()], 422);
                }

                return back()->withErrors(['_form' => $e->getMessage()]);
            }
        }

        // Batch create drafts for all complete students
        $enrollments = $classroom->enrollments()
            ->where('status', 'active')
            ->with('student')
            ->get();

        $createdCount = 0;
        $failedStudents = [];

        foreach ($enrollments as $enrollment) {
            try {
                $this->auditService->executeAtomic(
                    function () use ($enrollment, $policy) {
                        return $this->reportSnapshotService->buildDraft($enrollment, policy: $policy, requireComplete: true);
                    },
                    function (ReportCard $rc) use ($request, $enrollment) {
                        return [
                            'actor_id' => $request->user()->id,
                            'action' => 'CREATE_DRAFT_REPORT',
                            'entity_type' => ReportCard::class,
                            'entity_id' => $rc->id,
                            'scope' => AuditScope::REPORT,
                            'changes' => [
                                'enrollment_id' => $enrollment->id,
                                'student_name' => $enrollment->student->name,
                                'revision' => $rc->revision,
                                'status' => $rc->status->value,
                            ],
                        ];
                    }
                );
                $createdCount++;
            } catch (RuntimeException) {
                $failedStudents[] = $enrollment->student->name;
            }
        }

        $msg = "Berhasil membuat {$createdCount} draf rapor.";
        if (count($failedStudents) > 0) {
            $msg .= ' '.count($failedStudents).' siswa dilewati karena komponen nilai belum lengkap.';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'created_count' => $createdCount,
                'skipped_count' => count($failedStudents),
                'skipped_students' => $failedStudents,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Display detailed report card view (Admin / Homeroom preview).
     */
    public function show(Request $request, ReportCard $report): Response|JsonResponse
    {
        $this->authorize('viewStaff', $report);

        $report->loadMissing([
            'enrollment.student',
            'enrollment.classroom.activeHomeroom.teacher',
            'term.academicYear',
            'items.classSubject.subject',
            'extracurricularAssessments',
            'p5Assessments',
            'gradingPolicy.components.scoringComponent',
            'homeroomAssignment.teacher',
            'finalizedBy',
            'publishedBy',
            'revokedBy',
        ]);

        $classroom = $report->enrollment->classroom;
        $student = $report->enrollment->student;

        // Freshness check: compare current source_version with stored source_version
        $freshSnapshot = $this->reportSnapshotService->calculateSnapshot($report->enrollment, $report->gradingPolicy);
        $isSourceStale = $report->source_version !== $freshSnapshot['source_version'];

        // Lineage of all revisions for this enrollment
        $lineage = ReportCard::where('enrollment_id', $report->enrollment_id)
            ->orderBy('revision', 'asc')
            ->get(['id', 'revision', 'status', 'availability', 'published_at', 'revoked_at', 'lifecycle_reason'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'revision' => $r->revision,
                'status' => $r->status->value,
                'availability' => $r->availability->value,
                'is_current' => $r->id === $report->id,
                'published_at' => $r->published_at?->format('d M Y H:i'),
                'revoked_at' => $r->revoked_at?->format('d M Y H:i'),
                'lifecycle_reason' => $r->lifecycle_reason,
            ]);

        $data = [
            'report' => [
                'id' => $report->id,
                'enrollment_id' => $report->enrollment_id,
                'revision' => $report->revision,
                'supersedes_id' => $report->supersedes_id,
                'status' => $report->status->value,
                'availability' => $report->availability->value,
                'version' => $report->version,
                'source_version' => $report->source_version,
                'homeroom_note' => $report->homeroom_note,
                'lifecycle_reason' => $report->lifecycle_reason,
                'calculation_version' => $report->calculation_version,
                'generated_at' => $report->generated_at?->format('Y-m-d H:i'),
                'finalized_at' => $report->finalized_at?->format('Y-m-d H:i'),
                'finalized_by' => $report->finalizedBy ? [
                    'id' => $report->finalizedBy->id,
                    'name' => $report->finalizedBy->name,
                ] : null,
                'published_at' => $report->published_at?->format('Y-m-d H:i'),
                'published_by' => $report->publishedBy ? [
                    'id' => $report->publishedBy->id,
                    'name' => $report->publishedBy->name,
                ] : null,
                'revoked_at' => $report->revoked_at?->format('Y-m-d H:i'),
                'revoked_by' => $report->revokedBy ? [
                    'id' => $report->revokedBy->id,
                    'name' => $report->revokedBy->name,
                ] : null,
                'attendance_summary' => $report->attendance_summary,
                'identity_snapshot' => $report->identity_snapshot,
                'is_source_stale' => $isSourceStale,
                'fresh_source_version' => $freshSnapshot['source_version'],
                'can_finalize' => $freshSnapshot['can_finalize'],
                'blocked_reasons' => $freshSnapshot['blocked_reasons'],
                'items' => $report->items->map(fn ($item) => [
                    'id' => $item->id,
                    'class_subject_id' => $item->class_subject_id,
                    'subject_snapshot' => $item->subject_snapshot,
                    'teaching_history_snapshot' => $item->teaching_history_snapshot,
                    'component_breakdown_snapshot' => $item->component_breakdown_snapshot,
                    'kkm_snapshot' => $item->kkm_snapshot,
                    'final_score' => $item->final_score,
                    'kkm_status' => $item->kkm_status,
                ]),
                'extracurriculars' => $report->extracurricularAssessments->map(fn ($e) => [
                    'id' => $e->id,
                    'activity_name' => $e->activity_name,
                    'predicate' => $e->predicate,
                    'predicate_label' => $e->predicate_label,
                    'description' => $e->description,
                ]),
                'p5_assessments' => $report->p5Assessments->map(fn ($p) => [
                    'id' => $p->id,
                    'project_title' => $p->project_title,
                    'dimension' => $p->dimension,
                    'predicate' => $p->predicate,
                    'predicate_label' => $p->predicate_label,
                    'description' => $p->description,
                ]),
            ],
            'lineage' => $lineage,
            'canUpdateNote' => $request->user()->can('updateHomeroomNote', $report),
            'canManageReport' => $request->user()->can('create', [ReportCard::class, $classroom]),
            'canDownloadPdf' => $request->user()->can('downloadPdf', $report),
            'canPrepareFile' => $request->user()->can('prepareFile', $report),
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
            ],
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'section' => $classroom->section,
                'term' => [
                    'id' => $report->term->id,
                    'name' => $report->term->name,
                    'semester' => $report->term->semester,
                    'academic_year' => $report->term->academicYear?->name,
                ],
                'homeroom' => $classroom->activeHomeroom?->teacher ? [
                    'id' => $classroom->activeHomeroom->teacher->id,
                    'name' => $classroom->activeHomeroom->teacher->name,
                    'nip' => $classroom->activeHomeroom->teacher->nip,
                ] : null,
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Academic/Reports/Show', $data);
    }

    /**
     * Update homeroom note on a draft report card with optimistic locking.
     */
    public function update(Request $request, ReportCard $report): RedirectResponse|JsonResponse
    {
        $this->authorize('updateHomeroomNote', $report);

        if (! $report->isDraft()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Catatan wali kelas hanya dapat diubah saat rapor berstatus draf.'], 422);
            }

            return back()->withErrors(['_form' => 'Catatan wali kelas hanya dapat diubah saat rapor berstatus draf.']);
        }

        $expectedVersion = $request->input('expected_version');
        if ($expectedVersion === null || (int) $expectedVersion !== $report->version) {
            $msg = 'Data rapor telah diperbarui oleh pengguna lain (versi tidak cocok). Silakan muat ulang halaman.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['version' => [$msg]]], 409);
            }

            return back()->withErrors(['version' => $msg]);
        }

        $validated = $request->validate([
            'homeroom_note' => 'nullable|string|max:2000',
            'expected_version' => 'required|integer',
        ]);

        $oldNote = $report->homeroom_note;

        $updatedReport = $this->auditService->executeAtomic(
            function () use ($report, $validated) {
                $report->update([
                    'homeroom_note' => $validated['homeroom_note'] ?? null,
                    'version' => $report->version + 1,
                ]);

                return $report;
            },
            function (ReportCard $rc) use ($request, $oldNote, $validated) {
                return [
                    'actor_id' => $request->user()->id,
                    'action' => 'UPDATE_HOMEROOM_NOTE',
                    'entity_type' => ReportCard::class,
                    'entity_id' => $rc->id,
                    'scope' => AuditScope::REPORT,
                    'changes' => [
                        'homeroom_note' => [
                            'old' => $oldNote,
                            'new' => $validated['homeroom_note'] ?? null,
                        ],
                        'version' => $rc->version,
                    ],
                    'reason' => 'Pembaruan catatan wali kelas pada draf rapor',
                ];
            }
        )['result'];

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Catatan wali kelas berhasil disimpan.',
                'report' => $updatedReport,
            ]);
        }

        return back()->with('success', 'Catatan wali kelas berhasil disimpan.');
    }

    /**
     * Finalize report card snapshot into an immutable record.
     */
    public function finalize(Request $request, ReportCard $report): RedirectResponse|JsonResponse
    {
        $this->authorize('finalize', $report);

        if (! $report->isDraft()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Hanya rapor berstatus draf yang dapat difinalisasi.'], 422);
            }

            return back()->withErrors(['_form' => 'Hanya rapor berstatus draf yang dapat difinalisasi.']);
        }

        $expectedVersion = $request->input('expected_version');
        if ($expectedVersion === null || (int) $expectedVersion !== $report->version) {
            $msg = 'Data draf rapor telah berubah sebelum difinalisasi. Silakan muat ulang halaman.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['version' => [$msg]]], 409);
            }

            return back()->withErrors(['version' => $msg]);
        }

        $validated = $request->validate([
            'expected_version' => 'required|integer',
            'expected_source_version' => 'nullable|string',
            'expected_policy_id' => 'nullable',
        ]);

        try {
            $finalizedReport = $this->auditService->executeAtomic(
                function () use ($report, $request, $validated) {
                    return $this->reportSnapshotService->finalizeReportCard(
                        $report,
                        $request->user(),
                        $validated['expected_source_version'] ?? null
                    );
                },
                function (ReportCard $rc) use ($request) {
                    return [
                        'actor_id' => $request->user()->id,
                        'action' => 'FINALIZE_REPORT_CARD',
                        'entity_type' => ReportCard::class,
                        'entity_id' => $rc->id,
                        'scope' => AuditScope::REPORT,
                        'changes' => [
                            'status' => ReportCardStatus::FINALIZED->value,
                            'finalized_at' => $rc->finalized_at?->toIso8601String(),
                            'source_version' => $rc->source_version,
                            'version' => $rc->version,
                        ],
                        'reason' => 'Finalisasi pembekuan data snapshot rapor semester',
                    ];
                }
            )['result'];

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Rapor semester berhasil difinalisasi menjadi dokumen snapshot permanen.',
                    'report' => $finalizedReport,
                ]);
            }

            return back()->with('success', 'Rapor semester berhasil difinalisasi menjadi dokumen snapshot permanen.');
        } catch (SourceDataChangedException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['source_changed' => [$e->getMessage()]],
                ], 409);
            }

            return back()->withErrors(['source_changed' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['_form' => $e->getMessage()]);
        }
    }

    /**
     * Publish a finalized report card to the guardian portal.
     */
    public function publish(Request $request, ReportCard $report): RedirectResponse|JsonResponse
    {
        $this->authorize('publish', $report);

        if (! $report->isFinalized()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Hanya rapor berstatus final (finalized) yang dapat dipublikasikan.'], 422);
            }

            return back()->withErrors(['_form' => 'Hanya rapor berstatus final (finalized) yang dapat dipublikasikan.']);
        }

        if (! $report->isCurrent()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Hanya draf versi terkini (current) yang dapat dipublikasikan.'], 422);
            }

            return back()->withErrors(['_form' => 'Hanya draf versi terkini (current) yang dapat dipublikasikan.']);
        }

        $expectedVersion = $request->input('expected_version');
        if ($expectedVersion !== null && (int) $expectedVersion !== $report->version) {
            $msg = 'Versi rapor telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['version' => [$msg]]], 409);
            }

            return back()->withErrors(['version' => $msg]);
        }

        // Freshness check: source data must not have changed since finalization
        $report->loadMissing(['enrollment', 'gradingPolicy']);
        $freshSnapshot = $this->reportSnapshotService->calculateSnapshot($report->enrollment, $report->gradingPolicy);
        if ($freshSnapshot['source_version'] !== $report->source_version) {
            $msg = 'Data nilai atau presensi telah berubah sejak finalisasi. Publikasi dibatalkan. Silakan tinjau kembali data rapor.';
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $msg,
                    'errors' => ['source_changed' => [$msg]],
                ], 409);
            }

            return back()->withErrors(['source_changed' => $msg]);
        }

        // Ensure PDF is generated and ready in private storage
        if (! $report->private_file_path || ! Storage::disk('local')->exists($report->private_file_path)) {
            $this->reportPdfService->renderAndStore($report);
        }

        $publishedReport = $this->auditService->executeAtomic(
            function () use ($report, $request) {
                // Supercede any previously published report cards for this enrollment
                ReportCard::where('enrollment_id', $report->enrollment_id)
                    ->where('id', '!=', $report->id)
                    ->where('status', ReportCardStatus::PUBLISHED)
                    ->update(['availability' => ReportCardAvailability::SUPERSEDED]);

                $report->update([
                    'status' => ReportCardStatus::PUBLISHED,
                    'published_at' => now(),
                    'published_by' => $request->user()->id,
                    'version' => $report->version + 1,
                ]);

                return $report;
            },
            function (ReportCard $rc) use ($request) {
                return [
                    'actor_id' => $request->user()->id,
                    'action' => 'PUBLISH_REPORT_CARD',
                    'entity_type' => ReportCard::class,
                    'entity_id' => $rc->id,
                    'scope' => AuditScope::REPORT,
                    'changes' => [
                        'status' => ReportCardStatus::PUBLISHED->value,
                        'published_at' => $rc->published_at?->toIso8601String(),
                        'published_by' => $rc->published_by,
                        'version' => $rc->version,
                    ],
                    'reason' => 'Publikasi resmi rapor semester ke portal wali murid',
                ];
            }
        )['result'];

        // Notify guardians (In-app + Queued External WhatsApp/SMS)
        $report->loadMissing('enrollment.student.guardians');
        $student = $report->enrollment?->student;
        $studentName = $student?->name ?? 'Siswa';
        $guardians = $student?->guardians ?? collect();

        foreach ($guardians as $guardian) {
            $this->notificationService->createInboxNotification(
                recipient: $guardian,
                eventCategory: 'report_published',
                summary: "Rapor semester resmi untuk {$studentName} telah diterbitkan dan siap diakses.",
                scopeType: 'report',
                scopeId: $report->id
            );

            if (! empty($guardian->phone)) {
                $phone = $guardian->phone;
                $msg = "Assalamu'alaikum, rapor semester resmi untuk {$studentName} telah diterbitkan oleh pihak madrasah. Silakan periksa di portal SIA MI Al-Ghivari.";
                $ctx = [
                    'channel' => 'whatsapp',
                    'report_id' => $report->id,
                    'student_id' => $student?->id,
                ];

                DB::afterCommit(function () use ($phone, $msg, $ctx) {
                    try {
                        SendExternalNotificationJob::dispatch($phone, $msg, $ctx);
                    } catch (\Throwable $e) {
                        Log::warning('External message dispatch failed: '.$e->getMessage());
                    }
                });
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Rapor semester berhasil dipublikasikan ke portal wali murid.',
                'report' => $publishedReport,
            ]);
        }

        return back()->with('success', 'Rapor semester berhasil dipublikasikan ke portal wali murid.');
    }

    /**
     * Create a new draft revision of a finalized, published, or revoked report card (D-28).
     */
    public function createRevision(Request $request, ReportCard $report): RedirectResponse|JsonResponse
    {
        $this->authorize('createRevision', $report);

        if ($report->isDraft()) {
            $msg = 'Rapor masih berstatus draf, silakan sunting draf yang ada secara langsung.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['_form' => $msg]);
        }

        // Concurrency check if expected_version is provided
        $expectedVersion = $request->input('expected_version');
        if ($expectedVersion !== null && (int) $expectedVersion !== $report->version) {
            $msg = 'Versi rapor telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['version' => [$msg]]], 409);
            }

            return back()->withErrors(['version' => $msg]);
        }

        // Check if an active draft already exists for this enrollment
        $existingDraft = ReportCard::where('enrollment_id', $report->enrollment_id)
            ->where('status', ReportCardStatus::DRAFT)
            ->first();

        if ($existingDraft) {
            $msg = "Draf revisi untuk siswa ini sudah ada (Revisi {$existingDraft->revision}). Silakan selesaikan draf tersebut terlebih dahulu.";
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'existing_draft_id' => $existingDraft->id], 409);
            }

            return redirect()->route('academic.report-cards.show', $existingDraft->id)
                ->with('info', $msg);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $newDraft = $this->auditService->executeAtomic(
            function () use ($report, $validated, $request) {
                // If the report being revised was published, mark it as needing revision
                if ($report->isPublished()) {
                    $report->update([
                        'availability' => ReportCardAvailability::NEEDS_REVISION,
                    ]);

                    $this->progressionService->invalidateProgressionsForReport(
                        $report,
                        'Rapor sumber direvisi: draf revisi baru dibuat',
                        $request->user()
                    );
                }

                $draft = $this->reportSnapshotService->buildDraft(
                    enrollment: $report->enrollment,
                    homeroomNote: $report->homeroom_note,
                    policy: null,
                    requireComplete: false,
                    supersedesId: $report->id,
                    lifecycleReason: $validated['reason'] ?? null
                );

                return $draft;
            },
            function (ReportCard $draft) use ($report, $request, $validated) {
                return [
                    'actor_id' => $request->user()->id,
                    'action' => 'CREATE_REPORT_CARD_REVISION',
                    'entity_type' => ReportCard::class,
                    'entity_id' => $draft->id,
                    'scope' => AuditScope::REPORT,
                    'changes' => [
                        'parent_report_id' => $report->id,
                        'previous_revision' => $report->revision,
                        'new_revision' => $draft->revision,
                        'reason' => $validated['reason'] ?? 'Pembuatan draf revisi baru',
                    ],
                    'reason' => 'Pembuatan draf revisi baru dari rapor sebelumnya',
                ];
            }
        )['result'];

        $msg = "Draf revisi baru (Revisi {$newDraft->revision}) berhasil dibuat.";
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'report' => $newDraft,
            ], 201);
        }

        return redirect()->route('academic.report-cards.show', $newDraft->id)
            ->with('success', $msg);
    }

    /**
     * Revoke a published or finalized report card due to fatal errors (D-28).
     */
    public function revoke(Request $request, ReportCard $report): RedirectResponse|JsonResponse
    {
        $this->authorize('revoke', $report);

        if ($report->isDraft()) {
            $msg = 'Hanya rapor berstatus final atau terbit yang dapat ditarik.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['_form' => $msg]);
        }

        if ($report->isRevoked()) {
            $msg = 'Rapor ini telah ditarik sebelumnya.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->withErrors(['_form' => $msg]);
        }

        $expectedVersion = $request->input('expected_version');
        if ($expectedVersion !== null && (int) $expectedVersion !== $report->version) {
            $msg = 'Versi rapor telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['version' => [$msg]]], 409);
            }

            return back()->withErrors(['version' => $msg]);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $revokedReport = $this->auditService->executeAtomic(
            function () use ($report, $request, $validated) {
                $report->update([
                    'status' => ReportCardStatus::REVOKED,
                    'availability' => ReportCardAvailability::SUPERSEDED,
                    'revoked_at' => now(),
                    'revoked_by' => $request->user()->id,
                    'lifecycle_reason' => $validated['reason'],
                    'version' => $report->version + 1,
                ]);

                $this->progressionService->invalidateProgressionsForReport(
                    $report,
                    "Rapor sumber dicabut: {$validated['reason']}",
                    $request->user()
                );

                return $report;
            },
            function (ReportCard $rc) use ($request, $validated) {
                return [
                    'actor_id' => $request->user()->id,
                    'action' => 'REVOKE_REPORT_CARD',
                    'entity_type' => ReportCard::class,
                    'entity_id' => $rc->id,
                    'scope' => AuditScope::REPORT,
                    'changes' => [
                        'status' => ReportCardStatus::REVOKED->value,
                        'availability' => ReportCardAvailability::SUPERSEDED->value,
                        'revoked_at' => $rc->revoked_at?->toIso8601String(),
                        'revoked_by' => $rc->revoked_by,
                        'lifecycle_reason' => $validated['reason'],
                        'version' => $rc->version,
                    ],
                    'reason' => "Penarikan rapor resmi: {$validated['reason']}",
                ];
            }
        )['result'];

        $msg = 'Rapor semester resmi berhasil ditarik.';
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'report' => $revokedReport,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Render and prepare private PDF file for a finalized report card.
     */
    public function prepareFile(Request $request, ReportCard $report): RedirectResponse|JsonResponse
    {
        $this->authorize('prepareFile', $report);

        $expectedVersion = $request->input('expected_version');
        if ($expectedVersion !== null && (int) $expectedVersion !== $report->version) {
            $msg = 'Versi rapor telah diperbarui. Silakan muat ulang halaman.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['version' => [$msg]]], 409);
            }

            return back()->withErrors(['version' => $msg]);
        }

        try {
            $filePath = $this->reportPdfService->renderAndStore($report);

            $this->auditService->record(
                actorUserId: $request->user()->id,
                action: 'PREPARE_REPORT_PDF',
                entityType: ReportCard::class,
                entityId: $report->id,
                scope: AuditScope::REPORT,
                allowedChanges: [
                    'private_file_path' => $filePath,
                    'revision' => $report->revision,
                ],
                reason: 'Generasi berkas PDF rapor resmi madrasah'
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Berkas PDF rapor resmi berhasil disiapkan.',
                    'file_ready' => true,
                    'file_path' => $filePath,
                ]);
            }

            return back()->with('success', 'Berkas PDF rapor resmi berhasil disiapkan.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal merender berkas PDF: '.$e->getMessage()], 500);
            }

            return back()->withErrors(['_form' => 'Gagal merender berkas PDF: '.$e->getMessage()]);
        }
    }

    /**
     * Download or stream the report card PDF file.
     */
    public function pdf(Request $request, ReportCard $report)
    {
        $this->authorize('downloadPdf', $report);

        // Ensure file exists; auto prepare if missing
        if (! $report->private_file_path || ! Storage::disk('local')->exists($report->private_file_path)) {
            $this->reportPdfService->renderAndStore($report);
        }

        $report->loadMissing(['enrollment.student']);
        $nis = $report->enrollment?->student?->nis ?? $report->id;
        $fileName = "rapor_{$nis}_rev{$report->revision}.pdf";

        return Storage::disk('local')->response(
            $report->private_file_path,
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ]
        );
    }

    /**
     * Add extracurricular assessment to a draft report card.
     */
    public function storeExtracurricular(Request $request, ReportCard $report): RedirectResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'activity_name' => ['required', 'string', 'max:100'],
            'predicate' => ['required', 'string', 'in:A,B,C,D'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($report, $validated) {
                return $report->extracurricularAssessments()->create($validated);
            },
            auditFactory: fn (ExtracurricularAssessment $item) => [
                'actor_id' => $actor?->id,
                'action' => 'ADD_EXTRACURRICULAR',
                'entity_type' => ExtracurricularAssessment::class,
                'entity_id' => $item->id,
                'scope' => AuditScope::REPORT,
                'changes' => [
                    'report_card_id' => $report->id,
                    'activity_name' => $validated['activity_name'],
                    'predicate' => $validated['predicate'],
                ],
                'reason' => "Penilaian ekstrakurikuler {$validated['activity_name']} ditambahkan ke rapor #{$report->id}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Penilaian ekstrakurikuler berhasil ditambahkan.');
    }

    /**
     * Delete extracurricular assessment from draft report card.
     */
    public function destroyExtracurricular(Request $request, ExtracurricularAssessment $extracurricular): RedirectResponse
    {
        $report = $extracurricular->reportCard;
        $this->authorize('update', $report);

        $actor = $request->user();
        $activityName = $extracurricular->activity_name;

        $this->auditService->executeAtomic(
            operation: function () use ($extracurricular) {
                $extracurricular->delete();

                return true;
            },
            auditFactory: fn () => [
                'actor_id' => $actor?->id,
                'action' => 'DELETE_EXTRACURRICULAR',
                'entity_type' => ExtracurricularAssessment::class,
                'entity_id' => $extracurricular->id,
                'scope' => AuditScope::REPORT,
                'changes' => [
                    'report_card_id' => $report->id,
                    'activity_name' => $activityName,
                ],
                'reason' => "Penilaian ekstrakurikuler {$activityName} dihapus dari rapor #{$report->id}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Penilaian ekstrakurikuler berhasil dihapus.');
    }

    /**
     * Add P5-PRA assessment to a draft report card.
     */
    public function storeP5(Request $request, ReportCard $report): RedirectResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'project_title' => ['required', 'string', 'max:255'],
            'dimension' => ['required', 'string', 'max:100'],
            'predicate' => ['required', 'string', 'in:BB,MB,BSH,SAB'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($report, $validated) {
                return $report->p5Assessments()->create($validated);
            },
            auditFactory: fn (P5Assessment $item) => [
                'actor_id' => $actor?->id,
                'action' => 'ADD_P5_ASSESSMENT',
                'entity_type' => P5Assessment::class,
                'entity_id' => $item->id,
                'scope' => AuditScope::REPORT,
                'changes' => [
                    'report_card_id' => $report->id,
                    'project_title' => $validated['project_title'],
                    'dimension' => $validated['dimension'],
                    'predicate' => $validated['predicate'],
                ],
                'reason' => "Penilaian P5-PRA '{$validated['project_title']}' ditambahkan ke rapor #{$report->id}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Penilaian P5-PRA berhasil ditambahkan.');
    }

    /**
     * Delete P5-PRA assessment from draft report card.
     */
    public function destroyP5(Request $request, P5Assessment $p5): RedirectResponse
    {
        $report = $p5->reportCard;
        $this->authorize('update', $report);

        $actor = $request->user();
        $title = $p5->project_title;

        $this->auditService->executeAtomic(
            operation: function () use ($p5) {
                $p5->delete();

                return true;
            },
            auditFactory: fn () => [
                'actor_id' => $actor?->id,
                'action' => 'DELETE_P5_ASSESSMENT',
                'entity_type' => P5Assessment::class,
                'entity_id' => $p5->id,
                'scope' => AuditScope::REPORT,
                'changes' => [
                    'report_card_id' => $report->id,
                    'project_title' => $title,
                ],
                'reason' => "Penilaian P5-PRA '{$title}' dihapus dari rapor #{$report->id}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Penilaian P5-PRA berhasil dihapus.');
    }
}
