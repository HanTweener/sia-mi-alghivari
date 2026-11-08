<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\ScheduleSlot;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Academic\AcademicScheduleService;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ClassSubjectController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected AcademicScheduleService $scheduleService
    ) {}

    /**
     * Display subjects, active teaching assignments, and schedule slots for classroom.
     */
    public function index(Classroom $classroom): Response
    {
        $this->authorize('view', $classroom);

        $classroom->load(['term.academicYear', 'activeHomeroom.teacher:id,name']);
        $user = auth()->user();
        $canManage = $user->can('manageTeaching', $classroom);
        $canReadAllSubjects = $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::DATA_ENTRY_ADMIN, Role::PRINCIPAL])
            || $classroom->activeHomeroom?->teacher_id === $user->id;

        $withRelations = [
            'subject',
            'activeTeachingAssignment.teacher:id,name',
            'scheduleSlots' => fn ($q) => $q->orderBy('weekday')->orderBy('starts_at'),
        ];
        if ($canManage) {
            $withRelations[] = 'teachingAssignments.teacher:id,name';
        }

        $classSubjects = $classroom->classSubjects()
            ->with($withRelations)
            ->when(! $canReadAllSubjects, fn ($query) => $query
                ->whereHas('activeTeachingAssignment', fn ($assignment) => $assignment->where('teacher_id', $user->id)))
            ->get();

        $existingSubjectIds = $classSubjects->pluck('subject_id');

        $availableSubjects = $canManage ? Subject::where('is_active', true)
            ->whereNotIn('id', $existingSubjectIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category']) : [];

        $teachers = $canManage ? User::whereHas('userRoles', fn ($q) => $q->where('role', Role::TEACHER->value))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']) : [];

        return Inertia::render('Admin/Academic/Classrooms/Subjects', [
            'classroom' => $classroom,
            'classSubjects' => $classSubjects,
            'availableSubjects' => $availableSubjects,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Link subject to classroom (class_subjects).
     */
    public function store(Request $request, Classroom $classroom): RedirectResponse
    {
        $this->authorize('manageTeaching', $classroom);

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'starts_on' => ['nullable', 'date'],
        ]);

        $subject = Subject::findOrFail($validated['subject_id']);

        if ($classroom->classSubjects()->where('subject_id', $subject->id)->exists()) {
            return back()->withErrors(['subject_id' => 'Mata pelajaran ini sudah ditambahkan ke kelas.']);
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($classroom, $subject, $validated, $actor) {
                $classSubject = $classroom->classSubjects()->create([
                    'subject_id' => $subject->id,
                ]);

                if (! empty($validated['teacher_id'])) {
                    $teacher = User::findOrFail($validated['teacher_id']);
                    if ($teacher->hasRole(Role::TEACHER)) {
                        $classSubject->teachingAssignments()->create([
                            'teacher_id' => $teacher->id,
                            'starts_on' => $validated['starts_on'] ?? now(),
                            'status' => 'assigned',
                            'assigned_by' => $actor?->id,
                        ]);
                    }
                }

                return $classSubject;
            },
            auditFactory: fn (ClassSubject $cs) => [
                'actor_id' => $actor?->id,
                'action' => 'LINK_CLASS_SUBJECT',
                'entity_type' => ClassSubject::class,
                'entity_id' => $cs->id,
                'scope' => AuditScope::ASSIGNMENT,
                'changes' => [
                    'classroom_id' => $classroom->id,
                    'subject_id' => $subject->id,
                ],
                'reason' => "Mata pelajaran {$subject->name} ditambahkan ke {$classroom->full_name}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Mata pelajaran {$subject->name} berhasil ditambahkan ke kelas.");
    }

    /**
     * Assign teacher to class subject with handover support (D-26/D-27).
     * Closes previous active assignment without altering historical records.
     */
    public function assignTeacher(Request $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('manageTeaching', $classSubject->classroom);

        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
            'starts_on' => ['nullable', 'date'],
        ]);

        $teacher = User::findOrFail($validated['teacher_id']);
        if (! $teacher->hasRole(Role::TEACHER)) {
            return back()->withErrors(['teacher_id' => 'Pengguna yang dipilih tidak memiliki peran Guru.']);
        }

        $actor = $request->user();
        $startsOn = $validated['starts_on'] ?? now()->toDateString();

        $this->auditService->executeAtomic(
            operation: function () use ($classSubject, $teacher, $startsOn, $actor) {
                // Handover: close active assignment
                $activeAssignment = $classSubject->activeTeachingAssignment;
                if ($activeAssignment) {
                    $activeAssignment->update([
                        'ends_on' => $startsOn,
                    ]);
                }

                // Create new teaching assignment
                return $classSubject->teachingAssignments()->create([
                    'teacher_id' => $teacher->id,
                    'starts_on' => $startsOn,
                    'status' => 'assigned',
                    'assigned_by' => $actor?->id,
                ]);
            },
            auditFactory: fn (TeachingAssignment $assignment) => [
                'actor_id' => $actor?->id,
                'action' => 'ASSIGN_TEACHER',
                'entity_type' => TeachingAssignment::class,
                'entity_id' => $assignment->id,
                'scope' => AuditScope::ASSIGNMENT,
                'changes' => [
                    'class_subject_id' => $classSubject->id,
                    'teacher_id' => $teacher->id,
                    'starts_on' => $startsOn,
                ],
                'reason' => "Penugasan guru {$teacher->name} untuk mapel {$classSubject->subject?->name} di {$classSubject->classroom?->full_name}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Guru pengampu {$teacher->name} berhasil ditugaskan.");
    }

    /**
     * Add a schedule timetable slot with conflict validation.
     */
    public function storeScheduleSlot(Request $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('manageSchedule', $classSubject->classroom);

        $validated = $request->validate([
            'weekday' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ]);

        try {
            $this->scheduleService->validateNoConflict(
                $classSubject,
                (int) $validated['weekday'],
                $validated['starts_at'],
                $validated['ends_at']
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['schedule_conflict' => $e->getMessage()]);
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($classSubject, $validated) {
                return $classSubject->scheduleSlots()->create([
                    'weekday' => $validated['weekday'],
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'],
                ]);
            },
            auditFactory: fn (ScheduleSlot $slot) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_SCHEDULE_SLOT',
                'entity_type' => ScheduleSlot::class,
                'entity_id' => $slot->id,
                'scope' => AuditScope::ASSIGNMENT,
                'changes' => [
                    'weekday' => $slot->weekday,
                    'starts_at' => $slot->starts_at,
                    'ends_at' => $slot->ends_at,
                ],
                'reason' => "Penambahan jadwal {$classSubject->subject?->name} ({$slot->weekday_name} {$slot->starts_at}-{$slot->ends_at})",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    /**
     * Remove a schedule slot.
     */
    public function destroyScheduleSlot(Request $request, ScheduleSlot $slot): RedirectResponse
    {
        $slot->load('classSubject.subject', 'classSubject.classroom');
        $this->authorize('manageSchedule', $slot->classSubject->classroom);
        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($slot) {
                $slot->delete();

                return $slot;
            },
            auditFactory: fn (ScheduleSlot $s) => [
                'actor_id' => $actor?->id,
                'action' => 'DELETE_SCHEDULE_SLOT',
                'entity_type' => ScheduleSlot::class,
                'entity_id' => $s->id,
                'scope' => AuditScope::ASSIGNMENT,
                'changes' => ['deleted' => true],
                'reason' => "Penghapusan jadwal {$s->classSubject?->subject?->name} ({$s->weekday_name} {$s->starts_at}-{$s->ends_at})",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }
}
