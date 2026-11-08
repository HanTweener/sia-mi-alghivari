<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\DataEntryDelegation;
use App\Models\Enrollment;
use App\Models\Score;
use App\Models\ScoringComponent;
use App\Policies\AssessmentPolicy;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessmentController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display assessment list for a class subject.
     */
    public function index(Request $request, ClassSubject $classSubject): Response
    {
        $this->authorize('viewAny', [Assessment::class, $classSubject]);

        $user = $request->user();

        $classSubject->load([
            'classroom.term.academicYear',
            'subject',
            'activeTeachingAssignment.teacher:id,name',
        ]);

        $assessments = Assessment::where('class_subject_id', $classSubject->id)
            ->with([
                'scoringComponent:id,code,name',
                'creator:id,name',
                'publishedBy:id,name',
            ])
            ->withCount('scores')
            ->withAvg('scores', 'score')
            ->orderBy('assessed_on', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Calculate stats per assessment
        $assessmentsData = $assessments->map(function (Assessment $assessment) {
            $avgScore = $assessment->scores_avg_score;

            return [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'assessed_on' => $assessment->assessed_on->toDateString(),
                'status' => $assessment->status,
                'version' => $assessment->version,
                'scoring_component' => $assessment->scoringComponent,
                'creator' => $assessment->creator,
                'published_by' => $assessment->publishedBy,
                'published_at' => $assessment->published_at?->toIso8601String(),
                'scores_count' => $assessment->scores_count,
                'average_score' => $avgScore !== null ? number_format((float) $avgScore, 2, '.', '') : null,
            ];
        });

        // Get active non-attendance scoring components
        $scoringComponents = ScoringComponent::where('is_active', true)
            ->where('source_type', '!=', 'attendance')
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'source_type']);

        $isTeacher = app(AssessmentPolicy::class)->isAssignedTeacher($user, $classSubject);
        $isAdmin = $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN]);
        $isDataEntry = $user->hasRole(Role::DATA_ENTRY_ADMIN) && ! $isAdmin && ! $isTeacher;

        return Inertia::render('Admin/Academic/Assessments/Index', [
            'classSubject' => [
                'id' => $classSubject->id,
                'classroom' => $classSubject->classroom,
                'subject' => $classSubject->subject,
                'teacher' => $classSubject->activeTeachingAssignment?->teacher,
                'roster_version' => $classSubject->classroom->roster_version,
            ],
            'assessments' => $assessmentsData,
            'scoringComponents' => $scoringComponents,
            'capabilities' => [
                'can_create' => $user->can('create', [Assessment::class, $classSubject]),
                'can_publish' => $isAdmin || $isTeacher,
                'is_data_entry' => $isDataEntry,
            ],
        ]);
    }

    /**
     * Create a new draft assessment.
     */
    public function store(Request $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('create', [Assessment::class, $classSubject]);

        $user = $request->user();

        $validated = $request->validate([
            'scoring_component_id' => ['required', 'exists:scoring_components,id'],
            'title' => ['required', 'string', 'max:255'],
            'assessed_on' => ['required', 'date_format:Y-m-d'],
        ]);

        $scoringComponent = ScoringComponent::findOrFail($validated['scoring_component_id']);
        if ($scoringComponent->isAttendance()) {
            throw ValidationException::withMessages([
                'scoring_component_id' => 'Komponen presensi dihitung otomatis dari kehadiran pertemuan.',
            ]);
        }

        $assignment = $classSubject->activeTeachingAssignment;

        $execution = $this->auditService->executeAtomic(
            operation: function () use ($classSubject, $assignment, $user, $validated) {
                return Assessment::create([
                    'class_subject_id' => $classSubject->id,
                    'teaching_assignment_id' => $assignment?->id,
                    'created_by' => $user->id,
                    'scoring_component_id' => $validated['scoring_component_id'],
                    'title' => $validated['title'],
                    'assessed_on' => $validated['assessed_on'],
                    'status' => Assessment::STATUS_DRAFT,
                    'version' => 1,
                ]);
            },
            auditFactory: fn (Assessment $assessment) => [
                'actor_id' => $user->id,
                'action' => 'CREATE_ASSESSMENT',
                'entity_type' => 'assessment',
                'entity_id' => $assessment->id,
                'scope' => AuditScope::ASSESSMENT,
                'changes' => [
                    'class_subject_id' => $classSubject->id,
                    'scoring_component_id' => $validated['scoring_component_id'],
                    'title' => $validated['title'],
                    'assessed_on' => $validated['assessed_on'],
                ],
                'reason' => 'Membuat draf penilaian baru',
            ]
        );

        $assessment = $execution['result'];

        return redirect()->route('academic.assessments.show', $assessment->id)
            ->with('success', "Draf penilaian '{$assessment->title}' berhasil dibuat.");
    }

    /**
     * Display grading sheet for an assessment.
     */
    public function show(Request $request, Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        $user = $request->user();

        $assessment->load([
            'classSubject.classroom.term.academicYear',
            'classSubject.subject',
            'classSubject.activeTeachingAssignment.teacher:id,name',
            'scoringComponent',
            'creator:id,name',
            'publishedBy:id,name',
        ]);

        $classroom = $assessment->classSubject->classroom;

        // Fetch eligible enrollments on assessed_on date
        $enrollments = Enrollment::where('classroom_id', $classroom->id)
            ->eligibleOn($assessment->assessed_on)
            ->with('student:id,nis,name,gender')
            ->get();

        // Sort by student name
        $enrollments = $enrollments->sortBy(fn ($e) => $e->student?->name)->values();

        // Fetch existing scores for this assessment
        $scores = Score::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('enrollment_id');

        $rows = $enrollments->map(function (Enrollment $enrollment) use ($scores) {
            $scoreRecord = $scores->get($enrollment->id);

            return [
                'enrollment_id' => $enrollment->id,
                'student' => [
                    'id' => $enrollment->student->id,
                    'nis' => $enrollment->student->nis,
                    'name' => $enrollment->student->name,
                    'gender' => $enrollment->student->gender,
                ],
                'score' => $scoreRecord !== null ? (string) $scoreRecord->score : null,
                'feedback' => $scoreRecord?->feedback,
                'has_score' => $scoreRecord !== null,
            ];
        });

        // Compute statistics
        $totalStudents = $rows->count();
        $recordedCount = $rows->where('has_score', true)->count();
        $blankCount = $totalStudents - $recordedCount;
        $zeroCount = $rows->filter(fn ($r) => $r['has_score'] && (float) $r['score'] === 0.0)->count();

        $avgScore = $recordedCount > 0
            ? $scores->avg('score')
            : null;

        $isAdmin = $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN]);
        $isTeacher = app(AssessmentPolicy::class)->isAssignedTeacher($user, $assessment->classSubject);
        $isDataEntry = $user->hasRole(Role::DATA_ENTRY_ADMIN) && ! $isAdmin && ! $isTeacher;

        return Inertia::render('Admin/Academic/Assessments/Show', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'assessed_on' => $assessment->assessed_on->toDateString(),
                'status' => $assessment->status,
                'version' => $assessment->version,
                'published_at' => $assessment->published_at?->toIso8601String(),
                'published_by' => $assessment->publishedBy,
                'creator' => $assessment->creator,
                'scoring_component' => $assessment->scoringComponent,
            ],
            'classSubject' => [
                'id' => $assessment->classSubject->id,
                'classroom' => $classroom,
                'subject' => $assessment->classSubject->subject,
                'teacher' => $assessment->classSubject->activeTeachingAssignment?->teacher,
                'roster_version' => $classroom->roster_version,
            ],
            'rows' => $rows,
            'stats' => [
                'total_students' => $totalStudents,
                'recorded_count' => $recordedCount,
                'blank_count' => $blankCount,
                'zero_count' => $zeroCount,
                'average_score' => $avgScore !== null ? number_format((float) $avgScore, 2, '.', '') : null,
            ],
            'capabilities' => [
                'can_update' => $user->can('update', $assessment),
                'can_publish' => $assessment->isDraft() && ($isAdmin || $isTeacher),
                'can_delete' => $user->can('delete', $assessment),
                'is_data_entry' => $isDataEntry,
            ],
        ]);
    }

    /**
     * Update assessment metadata.
     */
    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'assessed_on' => ['required', 'date_format:Y-m-d'],
            'scoring_component_id' => ['required', 'exists:scoring_components,id'],
        ]);

        if ($assessment->version !== (int) $validated['expected_version']) {
            throw ValidationException::withMessages([
                'expected_version' => 'Data penilaian telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
            ]);
        }

        $scoringComponent = ScoringComponent::findOrFail($validated['scoring_component_id']);
        if ($scoringComponent->isAttendance()) {
            throw ValidationException::withMessages([
                'scoring_component_id' => 'Komponen presensi tidak dapat digunakan untuk penilaian langsung.',
            ]);
        }

        $user = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($assessment, $validated) {
                $assessment->update([
                    'title' => $validated['title'],
                    'assessed_on' => $validated['assessed_on'],
                    'scoring_component_id' => $validated['scoring_component_id'],
                    'version' => $assessment->version + 1,
                ]);

                return $assessment;
            },
            auditFactory: fn () => [
                'actor_id' => $user->id,
                'action' => 'UPDATE_ASSESSMENT',
                'entity_type' => 'assessment',
                'entity_id' => $assessment->id,
                'scope' => AuditScope::ASSESSMENT,
                'changes' => $validated,
                'reason' => 'Memperbarui detail penilaian',
            ]
        );

        return redirect()->back()->with('success', 'Detail penilaian berhasil diperbarui.');
    }

    /**
     * Update scores for an assessment.
     */
    public function updateScores(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('updateScores', $assessment);

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
            'expected_roster_version' => ['nullable', 'integer'],
            'entries' => ['required', 'array'],
            'entries.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'entries.*.score' => [
                'nullable',
                'regex:/^\d+(\.\d{1,2})?$/',
                'numeric',
                'min:0',
                'max:100',
            ],
            'entries.*.feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        // Optimistic lock check
        if ($assessment->version !== (int) $validated['expected_version']) {
            throw ValidationException::withMessages([
                'expected_version' => 'Data penilaian telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
            ]);
        }

        // Roster version check
        $classroom = $assessment->classSubject->classroom;
        if (isset($validated['expected_roster_version']) && (int) $validated['expected_roster_version'] !== $classroom->roster_version) {
            throw ValidationException::withMessages([
                'roster_changed' => 'Daftar siswa dalam kelas ini telah berubah. Silakan tinjau kembali daftar siswa.',
            ]);
        }

        // Validate all enrollments belong to this classroom and are eligible on assessed_on
        $submittedEnrollmentIds = collect($validated['entries'])->pluck('enrollment_id')->unique();
        $eligibleEnrollmentIds = Enrollment::where('classroom_id', $classroom->id)
            ->eligibleOn($assessment->assessed_on)
            ->whereIn('id', $submittedEnrollmentIds)
            ->pluck('id');

        if ($submittedEnrollmentIds->count() !== $eligibleEnrollmentIds->count()) {
            throw ValidationException::withMessages([
                'entries' => 'Sebagian siswa yang dikirim tidak terdaftar atau tidak aktif pada tanggal penilaian ini.',
            ]);
        }

        $user = $request->user();
        $isDataEntry = $user->hasRole(Role::DATA_ENTRY_ADMIN) && ! $user->can('delete', $assessment);

        // Delegation check & Clear-Existing restriction for Data Entry
        $delegationId = null;
        if ($isDataEntry) {
            $delegation = DataEntryDelegation::where('user_id', $user->id)
                ->where('teaching_assignment_id', $assessment->classSubject->activeTeachingAssignment?->id)
                ->where('capability', 'grade_entry')
                ->active()
                ->first();

            $delegationId = $delegation?->id;

            // Existing scores check: Data Entry cannot clear existing score row
            $existingScoreEnrollmentIds = Score::where('assessment_id', $assessment->id)
                ->whereIn('enrollment_id', $submittedEnrollmentIds)
                ->pluck('enrollment_id')
                ->all();

            foreach ($validated['entries'] as $entry) {
                $scoreValue = $entry['score'] ?? null;
                $isCleared = ($scoreValue === null || trim((string) $scoreValue) === '');

                if ($isCleared && in_array($entry['enrollment_id'], $existingScoreEnrollmentIds, true)) {
                    throw ValidationException::withMessages([
                        'entries' => 'Petugas delegasi tidak diizinkan mengosongkan nilai yang sudah tersimpan.',
                    ]);
                }
            }
        }

        $this->auditService->executeAtomic(
            operation: function () use ($assessment, $validated, $user, $delegationId) {
                foreach ($validated['entries'] as $entry) {
                    $enrollmentId = $entry['enrollment_id'];
                    $scoreValue = $entry['score'] ?? null;
                    $feedback = $entry['feedback'] ?? null;

                    if ($scoreValue === null || trim((string) $scoreValue) === '') {
                        // Blank: remove row if exists
                        Score::where('assessment_id', $assessment->id)
                            ->where('enrollment_id', $enrollmentId)
                            ->delete();
                    } else {
                        // Format score to exact 2 decimal places
                        $formattedScore = number_format((float) $scoreValue, 2, '.', '');

                        Score::updateOrCreate(
                            [
                                'assessment_id' => $assessment->id,
                                'enrollment_id' => $enrollmentId,
                            ],
                            [
                                'score' => $formattedScore,
                                'feedback' => $feedback,
                                'graded_by' => $user->id,
                                'delegation_id' => $delegationId,
                            ]
                        );
                    }
                }

                $assessment->version += 1;
                $assessment->save();

                return $assessment;
            },
            auditFactory: fn () => [
                'actor_id' => $user->id,
                'action' => 'UPDATE_SCORES',
                'entity_type' => 'assessment',
                'entity_id' => $assessment->id,
                'scope' => AuditScope::ASSESSMENT,
                'changes' => [
                    'entries_count' => count($validated['entries']),
                    'delegation_id' => $delegationId,
                ],
                'reason' => 'Menyimpan nilai siswa untuk penilaian',
            ]
        );

        return redirect()->back()->with('success', 'Nilai siswa berhasil disimpan.');
    }

    /**
     * Publish an assessment.
     */
    public function publish(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('publish', $assessment);

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
        ]);

        if ($assessment->version !== (int) $validated['expected_version']) {
            throw ValidationException::withMessages([
                'expected_version' => 'Data penilaian telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
            ]);
        }

        $user = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($assessment, $user) {
                $assessment->update([
                    'status' => Assessment::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'published_by' => $user->id,
                    'version' => $assessment->version + 1,
                ]);

                return $assessment;
            },
            auditFactory: fn () => [
                'actor_id' => $user->id,
                'action' => 'PUBLISH_ASSESSMENT',
                'entity_type' => 'assessment',
                'entity_id' => $assessment->id,
                'scope' => AuditScope::ASSESSMENT,
                'changes' => [
                    'status' => Assessment::STATUS_PUBLISHED,
                    'published_at' => now()->toIso8601String(),
                ],
                'reason' => 'Memublikasikan nilai penilaian aktivitas mapel',
            ]
        );

        return redirect()->back()->with('success', "Penilaian '{$assessment->title}' berhasil dipublikasikan.");
    }

    /**
     * Delete a draft assessment.
     */
    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->authorize('delete', $assessment);

        $classSubjectId = $assessment->class_subject_id;
        $title = $assessment->title;

        $this->auditService->executeAtomic(
            operation: function () use ($assessment) {
                $assessment->delete();

                return true;
            },
            auditFactory: fn () => [
                'actor_id' => auth()->id(),
                'action' => 'DELETE_ASSESSMENT',
                'entity_type' => 'assessment',
                'entity_id' => $assessment->id,
                'scope' => AuditScope::ASSESSMENT,
                'changes' => [
                    'title' => $title,
                    'class_subject_id' => $classSubjectId,
                ],
                'reason' => 'Menghapus draf penilaian',
            ]
        );

        return redirect()->route('academic.class-subjects.assessments.index', $classSubjectId)
            ->with('success', "Draf penilaian '{$title}' berhasil dihapus.");
    }

    /**
     * Download CSV template for assessment score entry.
     */
    public function exportTemplate(Request $request, Assessment $assessment): StreamedResponse
    {
        $this->authorize('view', $assessment);

        $assessment->load(['classSubject.classroom', 'scores']);
        $classroom = $assessment->classSubject->classroom;

        $enrollments = Enrollment::where('classroom_id', $classroom->id)
            ->eligibleOn($assessment->assessed_on)
            ->with('student')
            ->get()
            ->sortBy('student.name');

        $scores = $assessment->scores->keyBy('enrollment_id');

        $filename = "format_nilai_asesmen_{$assessment->id}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($enrollments, $scores) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['enrollment_id', 'nis', 'student_name', 'score', 'feedback']);

            foreach ($enrollments as $enrollment) {
                $score = $scores->get($enrollment->id);
                fputcsv($handle, [
                    $enrollment->id,
                    $enrollment->student?->nis ?? '',
                    $enrollment->student?->name ?? '',
                    $score ? $score->score : '',
                    $score ? $score->feedback : '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Bulk import assessment scores from CSV file.
     */
    public function importScores(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('updateScores', $assessment);

        $request->validate([
            'expected_version' => ['required', 'integer'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        if ($assessment->version !== (int) $request->expected_version) {
            throw ValidationException::withMessages([
                'expected_version' => 'Data penilaian telah diubah oleh pengguna lain. Silakan muat ulang halaman.',
            ]);
        }

        if ($assessment->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya penilaian berstatus draf yang dapat diimpor nilainya.',
            ]);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'Gagal membaca file CSV.']);
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'File CSV kosong atau tidak memiliki baris tajuk.']);
        }

        $headerMap = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        if (! in_array('enrollment_id', $headerMap, true) || ! in_array('score', $headerMap, true)) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => "Format kolom tidak sesuai. Kolom 'enrollment_id' dan 'score' wajib ada.",
            ]);
        }

        $enrollmentIndex = array_search('enrollment_id', $headerMap, true);
        $scoreIndex = array_search('score', $headerMap, true);
        $feedbackIndex = in_array('feedback', $headerMap, true) ? array_search('feedback', $headerMap, true) : false;

        $classroom = $assessment->classSubject->classroom;
        $eligibleEnrollmentIds = Enrollment::where('classroom_id', $classroom->id)
            ->eligibleOn($assessment->assessed_on)
            ->pluck('id')
            ->flip()
            ->all();

        $user = $request->user();
        $isDataEntry = $user->hasRole(Role::DATA_ENTRY_ADMIN) && ! $user->can('delete', $assessment);
        $delegationId = null;

        if ($isDataEntry) {
            $delegation = DataEntryDelegation::where('user_id', $user->id)
                ->where('teaching_assignment_id', $assessment->classSubject->activeTeachingAssignment?->id)
                ->where('capability', 'grade_entry')
                ->active()
                ->first();

            $delegationId = $delegation?->id;
        }

        $existingScores = Score::where('assessment_id', $assessment->id)->pluck('score', 'enrollment_id')->all();

        $entries = [];
        $lineNum = 1;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $enrollmentId = (int) trim((string) ($row[$enrollmentIndex] ?? ''));
            $scoreRaw = trim((string) ($row[$scoreIndex] ?? ''));
            $feedback = $feedbackIndex !== false ? trim((string) ($row[$feedbackIndex] ?? '')) : null;

            if (! isset($eligibleEnrollmentIds[$enrollmentId])) {
                $errors[] = "Baris {$lineNum}: ID pendaftaran '{$enrollmentId}' tidak terdaftar atau tidak aktif di kelas ini.";

                continue;
            }

            if ($scoreRaw === '') {
                if ($isDataEntry && isset($existingScores[$enrollmentId])) {
                    $errors[] = "Baris {$lineNum}: Petugas delegasi tidak diizinkan mengosongkan nilai yang sudah tersimpan.";
                }
                $entries[] = [
                    'enrollment_id' => $enrollmentId,
                    'score' => null,
                    'feedback' => $feedback ?: null,
                ];

                continue;
            }

            if (! is_numeric($scoreRaw) || ! preg_match('/^\d+(\.\d{1,2})?$/', $scoreRaw)) {
                $errors[] = "Baris {$lineNum}: Nilai '{$scoreRaw}' tidak valid (harus angka positif maksimal 2 desimal).";

                continue;
            }

            $scoreFloat = (float) $scoreRaw;
            if ($scoreFloat < 0 || $scoreFloat > 100) {
                $errors[] = "Baris {$lineNum}: Nilai {$scoreFloat} berada di luar rentang sah (0 - 100).";

                continue;
            }

            $entries[] = [
                'enrollment_id' => $enrollmentId,
                'score' => number_format($scoreFloat, 2, '.', ''),
                'feedback' => $feedback ?: null,
            ];
        }
        fclose($handle);

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'file' => implode(' | ', array_slice($errors, 0, 10)).(count($errors) > 10 ? ' (dan kesalahan lainnya)' : ''),
            ]);
        }

        $this->auditService->executeAtomic(
            operation: function () use ($assessment, $entries, $user, $delegationId) {
                foreach ($entries as $entry) {
                    if ($entry['score'] === null) {
                        Score::where('assessment_id', $assessment->id)
                            ->where('enrollment_id', $entry['enrollment_id'])
                            ->delete();
                    } else {
                        Score::updateOrCreate(
                            [
                                'assessment_id' => $assessment->id,
                                'enrollment_id' => $entry['enrollment_id'],
                            ],
                            [
                                'score' => $entry['score'],
                                'feedback' => $entry['feedback'],
                                'graded_by' => $user->id,
                                'delegation_id' => $delegationId,
                            ]
                        );
                    }
                }

                $assessment->version += 1;
                $assessment->save();

                return true;
            },
            auditFactory: fn () => [
                'actor_id' => $user?->id,
                'action' => 'IMPORT_SCORES',
                'entity_type' => 'assessment',
                'entity_id' => $assessment->id,
                'scope' => AuditScope::ASSESSMENT,
                'changes' => [
                    'imported_scores_count' => count($entries),
                    'assessment_id' => $assessment->id,
                ],
                'reason' => "Impor skor penilaian asesmen #{$assessment->id} via file CSV",
            ]
        );

        return back()->with('success', 'Skor penilaian berhasil diimpor dari file CSV.');
    }
}
