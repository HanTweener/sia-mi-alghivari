<?php

namespace App\Http\Controllers\Guardian;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Score;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChildProgressController extends Controller
{
    /**
     * Display children list for guardian or oversight list for admin.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole(Role::GUARDIAN)) {
            $children = $user->children()
                ->where('students.status', 'active')
                ->orderBy('name')
                ->get();

            // If guardian only has one child, redirect directly to their progress page
            if ($children->count() === 1) {
                return redirect()->route('children.progress', $children->first()->id);
            }

            return Inertia::render('Guardian/Children/Index', [
                'children' => $children->map(fn ($c) => [
                    'id' => $c->id,
                    'nis' => $c->nis,
                    'name' => $c->name,
                    'gender' => $c->gender,
                    'relationship' => $c->pivot->relationship ?? 'Wali',
                ]),
                'is_admin' => false,
            ]);
        }

        // Master / Academic Admin oversight
        if ($user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::PRINCIPAL])) {
            $search = $request->input('search');
            $students = Student::where('status', 'active')
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();

            return Inertia::render('Guardian/Children/Index', [
                'students' => $students,
                'is_admin' => true,
                'search' => $search,
            ]);
        }

        abort(403, 'Akses terbatas untuk wali murid atau pengawas akademik.');
    }

    /**
     * Display learning progress portal for a child.
     */
    public function progress(Request $request, Student $student): Response
    {
        $this->authorize('viewProgress', $student);

        $request->validate([
            'term_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        // Available terms for this student
        $enrollments = $student->enrollments()->get()
            ->filter(fn (Enrollment $enrollment) => $user->can('viewProgress', [$student, $enrollment]));
        $terms = Term::whereIn('id', $enrollments->pluck('term_id'))
            ->with('academicYear')
            ->orderBy('starts_on', 'desc')
            ->get();

        // Resolve requested term or active term
        $requestedTermId = $request->input('term_id');
        $selectedTerm = null;

        if ($requestedTermId) {
            $selectedTerm = $terms->firstWhere('id', (int) $requestedTermId);
            abort_unless($selectedTerm, 403);
        } else {
            $selectedTerm = $terms->firstWhere('status', 'active') ?? $terms->first();
        }

        if (! $selectedTerm) {
            return Inertia::render('Guardian/Progress/Show', [
                'student' => $student,
                'term' => null,
                'availableTerms' => [],
                'hasEnrollment' => false,
                'classroom' => null,
                'homeroom' => null,
                'subjectSummaries' => [],
                'activities' => [],
                'siblingChildren' => $user->hasRole(Role::GUARDIAN) ? $user->children()->get(['students.id', 'students.nis', 'students.name']) : [],
            ]);
        }

        // Student's enrollment in this term (prefers active enrollment if transferred mid-term)
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('term_id', $selectedTerm->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->latest('starts_on')
            ->with(['classroom.term.academicYear', 'classroom.activeHomeroom.teacher'])
            ->first();

        if (! $enrollment) {
            return Inertia::render('Guardian/Progress/Show', [
                'student' => $student,
                'term' => [
                    'id' => $selectedTerm->id,
                    'name' => $selectedTerm->name,
                    'semester' => $selectedTerm->semester,
                    'academic_year' => $selectedTerm->academicYear?->name,
                ],
                'availableTerms' => $terms->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => "{$t->academicYear?->name} - Semester {$t->semester}",
                    'is_active' => $t->status === 'active',
                ]),
                'hasEnrollment' => false,
                'classroom' => null,
                'homeroom' => null,
                'subjectSummaries' => [],
                'activities' => [],
                'siblingChildren' => $user->hasRole(Role::GUARDIAN) ? $user->children()->get(['students.id', 'students.nis', 'students.name']) : [],
            ]);
        }

        $this->authorize('viewProgress', [$student, $enrollment]);

        $classroom = $enrollment->classroom;
        $classSubjects = ClassSubject::where('classroom_id', $classroom->id)
            ->when(! $user->can('viewAllProgress', [$student, $enrollment]), fn ($query) => $query
                ->whereHas('teachingAssignments', fn ($assignment) => $assignment->active()->where('teacher_id', $user->id)))
            ->with('subject')
            ->get();
        $classSubjectIds = $classSubjects->pluck('id');

        $subjectFilterId = $request->input('subject_id');
        $filteredClassSubjectIds = $subjectFilterId
            ? $classSubjects->where('subject_id', (int) $subjectFilterId)->pluck('id')->all()
            : $classSubjectIds->all();

        // 1. Published Assessments & Scores (Strict zero leak of drafts or other students)
        $scoresQuery = Score::where('enrollment_id', $enrollment->id)
            ->whereHas('assessment', fn ($q) => $q
                ->whereIn('class_subject_id', $filteredClassSubjectIds)
                ->where('status', Assessment::STATUS_PUBLISHED)
            )
            ->with([
                'assessment.scoringComponent:id,code,name',
                'assessment.classSubject.subject:id,code,name',
            ]);

        $scores = $scoresQuery->get();

        $assessmentActivities = $scores->map(function (Score $s) {
            return [
                'type' => 'assessment',
                'id' => $s->assessment->id,
                'title' => $s->assessment->title,
                'date' => $s->assessment->assessed_on->toDateString(),
                'subject_id' => $s->assessment->classSubject->subject->id,
                'subject_name' => $s->assessment->classSubject->subject->name,
                'subject_code' => $s->assessment->classSubject->subject->code,
                'component_name' => $s->assessment->scoringComponent->name,
                'component_code' => $s->assessment->scoringComponent->code,
                'score' => (string) $s->score,
                'feedback' => $s->feedback,
            ];
        });

        // 2. Finalized Attendance Records (Strict zero leak of draft sessions or other students)
        $attendanceQuery = AttendanceRecord::where('enrollment_id', $enrollment->id)
            ->whereHas('attendanceSession', fn ($q) => $q
                ->whereIn('class_subject_id', $filteredClassSubjectIds)
                ->where('status', AttendanceSession::STATUS_FINALIZED)
            )
            ->with([
                'attendanceSession.classSubject.subject:id,code,name',
            ]);

        $attendanceRecords = $attendanceQuery->get();

        $attendanceActivities = $attendanceRecords->map(function (AttendanceRecord $r) {
            return [
                'type' => 'attendance',
                'id' => $r->attendanceSession->id,
                'title' => "Presensi · Pertemuan ke-{$r->attendanceSession->meeting_number}",
                'date' => $r->attendanceSession->held_on->toDateString(),
                'topic' => $r->attendanceSession->topic,
                'subject_id' => $r->attendanceSession->classSubject->subject->id,
                'subject_name' => $r->attendanceSession->classSubject->subject->name,
                'subject_code' => $r->attendanceSession->classSubject->subject->code,
                'status' => $r->status, // present, excused, sick, absent
            ];
        });

        // Combine activities sorted by date descending
        $combinedActivities = $assessmentActivities->concat($attendanceActivities)
            ->sortByDesc('date')
            ->values();

        // 3. Subject-by-Subject Temporary Recap
        $allPublishedScoresForEnrollment = Score::where('enrollment_id', $enrollment->id)
            ->whereHas('assessment', fn ($q) => $q->whereIn('class_subject_id', $classSubjectIds)->where('status', Assessment::STATUS_PUBLISHED))
            ->with(['assessment.classSubject', 'assessment.scoringComponent'])
            ->get();

        $allFinalizedAttendanceForEnrollment = AttendanceRecord::where('enrollment_id', $enrollment->id)
            ->whereHas('attendanceSession', fn ($q) => $q->whereIn('class_subject_id', $classSubjectIds)->where('status', AttendanceSession::STATUS_FINALIZED))
            ->with('attendanceSession.classSubject')
            ->get();

        $subjectSummaries = $classSubjects->map(function (ClassSubject $cs) use ($allPublishedScoresForEnrollment, $allFinalizedAttendanceForEnrollment) {
            $subjScores = $allPublishedScoresForEnrollment->filter(
                fn ($s) => $s->assessment->class_subject_id === $cs->id
            );

            $subjAtt = $allFinalizedAttendanceForEnrollment->filter(
                fn ($a) => $a->attendanceSession->class_subject_id === $cs->id
            );

            $totalAtt = $subjAtt->count();
            $presentCount = $subjAtt->where('status', 'present')->count();
            $excusedCount = $subjAtt->where('status', 'excused')->count();
            $sickCount = $subjAtt->where('status', 'sick')->count();
            $absentCount = $subjAtt->where('status', 'absent')->count();

            $attRate = $totalAtt > 0
                ? number_format(($presentCount / $totalAtt) * 100, 1, '.', '')
                : null;

            $scoreCount = $subjScores->count();
            $avgScore = $scoreCount > 0
                ? number_format((float) $subjScores->avg('score'), 2, '.', '')
                : null;

            return [
                'subject_id' => $cs->subject->id,
                'subject_name' => $cs->subject->name,
                'subject_code' => $cs->subject->code,
                'attendance' => [
                    'total_sessions' => $totalAtt,
                    'present' => $presentCount,
                    'excused' => $excusedCount,
                    'sick' => $sickCount,
                    'absent' => $absentCount,
                    'rate' => $attRate,
                ],
                'assessments' => [
                    'count' => $scoreCount,
                    'average' => $avgScore,
                ],
            ];
        });

        // Homeroom contact
        $homeroomTeacher = $classroom->activeHomeroom?->teacher;

        return Inertia::render('Guardian/Progress/Show', [
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
            ],
            'term' => [
                'id' => $selectedTerm->id,
                'name' => $selectedTerm->name,
                'semester' => $selectedTerm->semester,
                'academic_year' => $selectedTerm->academicYear?->name,
            ],
            'availableTerms' => $terms->map(fn ($t) => [
                'id' => $t->id,
                'name' => "{$t->academicYear?->name} - Semester {$t->semester}",
                'is_active' => $t->status === 'active',
            ]),
            'hasEnrollment' => true,
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'section' => $classroom->section,
            ],
            'homeroom' => $homeroomTeacher ? [
                'name' => $homeroomTeacher->name,
                'email' => $homeroomTeacher->email,
            ] : null,
            'classSubjects' => $classSubjects->map(fn ($cs) => [
                'id' => $cs->subject->id,
                'name' => $cs->subject->name,
                'code' => $cs->subject->code,
            ]),
            'selectedSubjectId' => $subjectFilterId ? (int) $subjectFilterId : null,
            'subjectSummaries' => $subjectSummaries,
            'activities' => $combinedActivities,
            'siblingChildren' => $user->hasRole(Role::GUARDIAN)
                ? $user->children()->where('students.id', '!=', $student->id)->get(['students.id', 'students.nis', 'students.name'])
                : [],
        ]);
    }

    /**
     * Get homeroom teacher contact info for consultation.
     */
    public function homeroomContact(Request $request, Student $student): JsonResponse
    {
        $this->authorize('viewProgress', $student);

        $activeEnrollment = $student->enrollments()
            ->where('status', 'active')
            ->with(['classroom.activeHomeroom.teacher'])
            ->first();

        if ($activeEnrollment) {
            $this->authorize('viewProgress', [$student, $activeEnrollment]);
        }

        if (! $activeEnrollment || ! $activeEnrollment->classroom->activeHomeroom?->teacher) {
            return response()->json([
                'teacher' => null,
                'message' => 'Wali kelas belum ditugaskan untuk rombel aktif siswa.',
            ]);
        }

        $teacher = $activeEnrollment->classroom->activeHomeroom->teacher;

        return response()->json([
            'teacher' => [
                'name' => $teacher->name,
                'email' => $teacher->email,
                'classroom' => $activeEnrollment->classroom->name,
            ],
        ]);
    }
}
