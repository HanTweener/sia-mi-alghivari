<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeachingAssignmentController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display multi-class teaching assignments overview and management.
     */
    public function index(Request $request): Response
    {
        $this->authorize('manageTeaching', Classroom::class);

        $terms = Term::with('academicYear:id,name')
            ->orderByDesc('id')
            ->get(['id', 'academic_year_id', 'semester', 'status']);

        $defaultTermId = rescue(
            fn () => $terms->firstWhere('status', 'active')?->id,
            fn () => $terms->first()?->id,
            report: false
        ) ?? $terms->first()?->id;

        $selectedTermId = $request->filled('term_id') ? (int) $request->input('term_id') : $defaultTermId;
        $selectedTerm = $terms->firstWhere('id', $selectedTermId);

        $classrooms = $selectedTermId
            ? Classroom::where('term_id', $selectedTermId)
                ->orderBy('grade_level')
                ->orderBy('section')
                ->get(['id', 'term_id', 'grade_level', 'section'])
            : collect();

        $subjects = Subject::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category']);

        $teachers = User::whereHas('userRoles', fn ($q) => $q->where('role', Role::TEACHER->value))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $classroomIds = $classrooms->pluck('id');

        $totalAssignments = ClassSubject::whereIn('classroom_id', $classroomIds)->count();
        $assignedWithTeacher = ClassSubject::whereIn('classroom_id', $classroomIds)
            ->whereHas('activeTeachingAssignment')
            ->count();
        $vacantCount = $totalAssignments - $assignedWithTeacher;

        $classSubjectsQuery = ClassSubject::whereIn('classroom_id', $classroomIds)
            ->join('classrooms', 'classrooms.id', '=', 'class_subjects.classroom_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->select('class_subjects.*')
            ->with([
                'classroom:id,grade_level,section,term_id',
                'subject:id,code,name,category',
                'activeTeachingAssignment.teacher:id,name',
            ]);

        if ($request->filled('subject_id')) {
            $classSubjectsQuery->where('class_subjects.subject_id', (int) $request->input('subject_id'));
        }

        if ($request->filled('grade_level')) {
            $grade = (int) $request->input('grade_level');
            $classSubjectsQuery->where('classrooms.grade_level', $grade);
        }

        if ($request->filled('teacher_id')) {
            $teacherId = (int) $request->input('teacher_id');
            $classSubjectsQuery->whereHas('activeTeachingAssignment', fn ($q) => $q->where('teacher_id', $teacherId));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $classSubjectsQuery->where(function ($q) use ($search) {
                $q->whereHas('subject', fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('activeTeachingAssignment.teacher', fn ($t) => $t->where('name', 'like', "%{$search}%"))
                    ->orWhere('classrooms.section', 'like', "%{$search}%");
            });
        }

        $classSubjectsQuery->orderBy('classrooms.grade_level')
            ->orderBy('classrooms.section')
            ->orderBy('subjects.name');

        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $classSubjects = $classSubjectsQuery->paginate($perPage)->withQueryString()->through(fn (ClassSubject $cs) => [
            'id' => $cs->id,
            'classroom_id' => $cs->classroom_id,
            'classroom' => [
                'id' => $cs->classroom->id,
                'name' => $cs->classroom->full_name,
                'grade_level' => $cs->classroom->grade_level,
                'section' => $cs->classroom->section,
            ],
            'subject_id' => $cs->subject_id,
            'subject' => [
                'id' => $cs->subject->id,
                'code' => $cs->subject->code,
                'name' => $cs->subject->name,
                'category' => $cs->subject->category,
            ],
            'teacher' => $cs->activeTeachingAssignment?->teacher ? [
                'id' => $cs->activeTeachingAssignment->teacher->id,
                'name' => $cs->activeTeachingAssignment->teacher->name,
            ] : null,
            'assignment' => $cs->activeTeachingAssignment ? [
                'id' => $cs->activeTeachingAssignment->id,
                'starts_on' => $cs->activeTeachingAssignment->starts_on->toDateString(),
                'status' => $cs->activeTeachingAssignment->status,
            ] : null,
        ]);

        return Inertia::render('Admin/Academic/TeachingAssignments/Index', [
            'terms' => $terms->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->full_name,
                'status' => $t->status,
                'academic_year' => $t->academicYear?->name,
            ]),
            'selectedTermId' => $selectedTermId,
            'selectedTerm' => $selectedTerm ? [
                'id' => $selectedTerm->id,
                'name' => $selectedTerm->full_name,
                'status' => $selectedTerm->status,
            ] : null,
            'classrooms' => $classrooms->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->full_name,
                'grade_level' => $c->grade_level,
                'section' => $c->section,
            ]),
            'subjects' => $subjects,
            'teachers' => $teachers,
            'classSubjects' => $classSubjects,
            'filters' => [
                'term_id' => $selectedTermId,
                'subject_id' => $request->input('subject_id', ''),
                'grade_level' => $request->input('grade_level', ''),
                'teacher_id' => $request->input('teacher_id', ''),
                'search' => $request->input('search', ''),
                'per_page' => $perPage,
            ],
            'stats' => [
                'total_classrooms' => $classrooms->count(),
                'total_assignments' => $totalAssignments,
                'with_teacher_count' => $assignedWithTeacher,
                'vacant_count' => $vacantCount,
            ],
        ]);
    }

    /**
     * Store bulk multi-classroom subject assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageTeaching', Classroom::class);

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'classroom_ids' => ['required', 'array', 'min:1'],
            'classroom_ids.*' => ['required', 'exists:classrooms,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'starts_on' => ['nullable', 'date'],
        ]);

        $subject = Subject::findOrFail($validated['subject_id']);
        $teacher = null;

        if (! empty($validated['teacher_id'])) {
            $teacher = User::findOrFail($validated['teacher_id']);
            if (! $teacher->hasRole(Role::TEACHER)) {
                return back()->withErrors(['teacher_id' => 'Pengguna yang dipilih tidak memiliki peran Guru.']);
            }
        }

        $actor = $request->user();
        $startsOn = $validated['starts_on'] ?? now()->toDateString();
        $classroomIds = array_unique($validated['classroom_ids']);
        $newLinkCount = 0;
        $assignedTeacherCount = 0;

        $this->auditService->executeAtomic(
            operation: function () use ($classroomIds, $subject, $teacher, $startsOn, $actor, &$newLinkCount, &$assignedTeacherCount) {
                foreach ($classroomIds as $classroomId) {
                    $classSubject = ClassSubject::firstOrCreate([
                        'classroom_id' => $classroomId,
                        'subject_id' => $subject->id,
                    ]);

                    if ($classSubject->wasRecentlyCreated) {
                        $newLinkCount++;
                    }

                    if ($teacher) {
                        $active = $classSubject->activeTeachingAssignment;
                        if ($active) {
                            if ($active->teacher_id !== $teacher->id) {
                                $active->update(['ends_on' => $startsOn]);
                                $classSubject->teachingAssignments()->create([
                                    'teacher_id' => $teacher->id,
                                    'starts_on' => $startsOn,
                                    'status' => 'assigned',
                                    'assigned_by' => $actor?->id,
                                ]);
                                $assignedTeacherCount++;
                            }
                        } else {
                            $classSubject->teachingAssignments()->create([
                                'teacher_id' => $teacher->id,
                                'starts_on' => $startsOn,
                                'status' => 'assigned',
                                'assigned_by' => $actor?->id,
                            ]);
                            $assignedTeacherCount++;
                        }
                    }
                }

                return $subject;
            },
            auditFactory: fn () => [
                'actor_id' => $actor?->id,
                'action' => 'BULK_ASSIGN_TEACHING',
                'entity_type' => TeachingAssignment::class,
                'entity_id' => $subject->id,
                'scope' => AuditScope::ASSIGNMENT,
                'changes' => [
                    'subject_id' => $subject->id,
                    'classroom_ids' => $classroomIds,
                    'teacher_id' => $teacher?->id,
                    'starts_on' => $startsOn,
                ],
                'reason' => "Penugasan massal mapel {$subject->name} ke ".count($classroomIds).' kelas rombel'.($teacher ? " dengan pengampu {$teacher->name}" : ''),
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        $message = "Mata pelajaran {$subject->name} berhasil dikonfigurasikan ke ".count($classroomIds).' rombel kelas.';
        if ($teacher) {
            $message .= " Guru pengampu: {$teacher->name}.";
        }

        return back()->with('success', $message);
    }

    /**
     * Remove a subject from a classroom if no assessments or attendance exist.
     */
    public function destroy(ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('manageTeaching', $classSubject->classroom);

        if ($classSubject->assessments()->exists() || $classSubject->attendanceSessions()->exists()) {
            return back()->withErrors(['error' => 'Mata pelajaran ini tidak dapat dihapus karena sudah memiliki data riwayat asesmen atau presensi pertemuan di kelas ini.']);
        }

        $actor = auth()->user();
        $subjectName = $classSubject->subject?->name ?? 'Mapel';
        $classroomName = $classSubject->classroom?->full_name ?? 'Kelas';

        $this->auditService->executeAtomic(
            operation: function () use ($classSubject) {
                $classSubject->teachingAssignments()->delete();
                $classSubject->scheduleSlots()->delete();
                $classSubject->delete();
            },
            auditFactory: fn () => [
                'actor_id' => $actor?->id,
                'action' => 'REMOVE_CLASS_SUBJECT',
                'entity_type' => ClassSubject::class,
                'entity_id' => $classSubject->id,
                'scope' => AuditScope::ASSIGNMENT,
                'changes' => [
                    'class_subject_id' => $classSubject->id,
                    'classroom_id' => $classSubject->classroom_id,
                    'subject_id' => $classSubject->subject_id,
                ],
                'reason' => "Penugasan mapel {$subjectName} di kelas {$classroomName} dihapus",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Penugasan mata pelajaran {$subjectName} di {$classroomName} berhasil dihapus.");
    }
}
