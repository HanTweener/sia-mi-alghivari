<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display classroom roster and enrollments.
     */
    public function index(Classroom $classroom): Response
    {
        $this->authorize('view', $classroom);

        $classroom->load(['term.academicYear', 'activeHomeroom.teacher:id,name']);
        $canManage = auth()->user()->can('manageEnrollments', $classroom);

        $enrollments = $classroom->enrollments()
            ->with($canManage ? 'student' : 'student:id,nis,name,gender,status')
            ->orderBy('created_at')
            ->get();

        // Available students (students not yet actively enrolled in this term) - only queried for authorized managers
        $availableStudents = $canManage ? Student::where('status', 'active')
            ->whereDoesntHave('enrollments', fn ($q) => $q->where('term_id', $classroom->term_id)->where('status', 'active'))
            ->orderBy('name')
            ->select('id', 'nis', 'name', 'gender')
            ->get() : [];

        $teachers = auth()->user()->can('manageHomeroom', $classroom) ? User::whereHas('userRoles', fn ($q) => $q->where('role', Role::TEACHER->value))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']) : [];

        $otherClassrooms = auth()->user()->can('withdrawEnrollment', $classroom) ? Classroom::where('term_id', $classroom->term_id)
            ->where('id', '!=', $classroom->id)
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get(['id', 'grade_level', 'section'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->full_name,
            ]) : [];

        return Inertia::render('Admin/Academic/Classrooms/Roster', [
            'classroom' => $classroom,
            'enrollments' => $enrollments,
            'availableStudents' => $availableStudents,
            'otherClassrooms' => $otherClassrooms,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Enroll a student into a classroom.
     */
    public function store(Request $request, Classroom $classroom): RedirectResponse
    {
        $this->authorize('manageEnrollments', $classroom);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'starts_on' => ['nullable', 'date'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Invariant: Student can only be enrolled in one classroom per term
        $alreadyEnrolled = Enrollment::where('term_id', $classroom->term_id)
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->first();

        if ($alreadyEnrolled) {
            return back()->withErrors([
                'student_id' => "Siswa {$student->name} sudah terdaftar di kelas lain pada semester ini.",
            ]);
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($classroom, $student, $validated) {
                $enrollment = Enrollment::create([
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'term_id' => $classroom->term_id,
                    'starts_on' => $validated['starts_on'] ?? now(),
                    'ends_on' => null,
                    'status' => 'active',
                ]);

                // Increment roster version
                $classroom->incrementRosterVersion();

                return $enrollment;
            },
            auditFactory: fn (Enrollment $enrollment) => [
                'actor_id' => $actor?->id,
                'action' => 'ENROLL_STUDENT',
                'entity_type' => Enrollment::class,
                'entity_id' => $enrollment->id,
                'scope' => AuditScope::STUDENT,
                'changes' => [
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'term_id' => $classroom->term_id,
                ],
                'reason' => "Siswa {$student->name} ({$student->nis}) didaftarkan ke {$classroom->full_name}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Siswa {$student->name} berhasil didaftarkan ke {$classroom->full_name}.");
    }

    /**
     * Withdraw student from classroom.
     */
    public function destroy(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $classroom = $enrollment->classroom;
        $this->authorize('withdrawEnrollment', $classroom);
        $student = $enrollment->student;
        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($enrollment, $classroom) {
                $enrollment->update([
                    'status' => 'withdrawn',
                    'ends_on' => now(),
                ]);

                $classroom->incrementRosterVersion();

                return $enrollment;
            },
            auditFactory: fn (Enrollment $e) => [
                'actor_id' => $actor?->id,
                'action' => 'WITHDRAW_ENROLLMENT',
                'entity_type' => Enrollment::class,
                'entity_id' => $e->id,
                'scope' => AuditScope::STUDENT,
                'changes' => ['status' => 'withdrawn'],
                'reason' => "Keanggotaan siswa {$student?->name} dari {$classroom->full_name} dinonaktifkan",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Siswa {$student?->name} berhasil dikeluarkan dari kelas.");
    }

    /**
     * Transfer a student to another classroom within the same term.
     */
    public function transfer(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $sourceClassroom = $enrollment->classroom;
        $this->authorize('withdrawEnrollment', $sourceClassroom);

        $validated = $request->validate([
            'target_classroom_id' => ['required', 'exists:classrooms,id'],
            'transfer_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($enrollment->status !== 'active') {
            throw ValidationException::withMessages([
                'enrollment' => 'Hanya keanggotaan aktif yang dapat dipindahkan ke rombel lain.',
            ]);
        }

        $targetClassroom = Classroom::with('term')->findOrFail($validated['target_classroom_id']);

        if ($targetClassroom->id === $sourceClassroom->id) {
            throw ValidationException::withMessages([
                'target_classroom_id' => 'Rombel tujuan tidak boleh sama dengan rombel asal.',
            ]);
        }

        if ($targetClassroom->term_id !== $sourceClassroom->term_id) {
            throw ValidationException::withMessages([
                'target_classroom_id' => 'Pemindahan antarkelas tengah semester harus berada dalam semester yang sama.',
            ]);
        }

        $transferDate = Carbon::parse($validated['transfer_date'])->startOfDay();
        $sourceStartsOn = Carbon::parse($enrollment->starts_on)->startOfDay();

        if ($transferDate->lt($sourceStartsOn)) {
            throw ValidationException::withMessages([
                'transfer_date' => "Tanggal transfer tidak boleh lebih awal dari tanggal masuk rombel asal ({$sourceStartsOn->toDateString()}).",
            ]);
        }

        $term = $sourceClassroom->term;
        if ($term && $term->starts_on && $transferDate->lt(Carbon::parse($term->starts_on)->startOfDay())) {
            throw ValidationException::withMessages([
                'transfer_date' => "Tanggal transfer tidak boleh lebih awal dari awal semester ({$term->starts_on->toDateString()}).",
            ]);
        }
        if ($term && $term->ends_on && $transferDate->gt(Carbon::parse($term->ends_on)->startOfDay())) {
            throw ValidationException::withMessages([
                'transfer_date' => "Tanggal transfer tidak boleh melebihi akhir semester ({$term->ends_on->toDateString()}).",
            ]);
        }

        $alreadyInTarget = Enrollment::where('student_id', $enrollment->student_id)
            ->where('classroom_id', $targetClassroom->id)
            ->where('term_id', $targetClassroom->term_id)
            ->where('status', 'active')
            ->exists();

        if ($alreadyInTarget) {
            throw ValidationException::withMessages([
                'target_classroom_id' => 'Siswa sudah terdaftar aktif di rombel tujuan.',
            ]);
        }

        $actor = $request->user();
        $student = $enrollment->student;

        $this->auditService->executeAtomic(
            operation: function () use ($enrollment, $sourceClassroom, $targetClassroom, $transferDate, $validated) {
                // 1. Close source enrollment on transfer date
                $enrollment->update([
                    'ends_on' => $transferDate->toDateString(),
                    'status' => 'transferred',
                    'transfer_reason' => $validated['reason'] ?? null,
                ]);

                // 2. Open new enrollment in target classroom
                $newEnrollment = Enrollment::create([
                    'student_id' => $enrollment->student_id,
                    'classroom_id' => $targetClassroom->id,
                    'term_id' => $targetClassroom->term_id,
                    'starts_on' => $transferDate->toDateString(),
                    'ends_on' => null,
                    'status' => 'active',
                    'transferred_from_enrollment_id' => $enrollment->id,
                    'transfer_reason' => $validated['reason'] ?? null,
                ]);

                // 3. Increment roster versions on both source and target classrooms
                $sourceClassroom->incrementRosterVersion();
                $targetClassroom->incrementRosterVersion();

                return $newEnrollment;
            },
            auditFactory: fn (Enrollment $newEnrollment) => [
                'actor_id' => $actor?->id,
                'action' => 'TRANSFER_STUDENT',
                'entity_type' => Enrollment::class,
                'entity_id' => $newEnrollment->id,
                'scope' => AuditScope::STUDENT,
                'changes' => [
                    'student_id' => $student->id,
                    'source_classroom_id' => $sourceClassroom->id,
                    'target_classroom_id' => $targetClassroom->id,
                    'transfer_date' => $transferDate->toDateString(),
                    'reason' => $validated['reason'] ?? null,
                ],
                'reason' => "Transfer siswa {$student->name} dari {$sourceClassroom->full_name} ke {$targetClassroom->full_name}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Siswa {$student->name} berhasil dipindahkan ke {$targetClassroom->full_name}.");
    }
}
