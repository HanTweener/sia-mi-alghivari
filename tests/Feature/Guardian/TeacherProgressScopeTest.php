<?php

namespace Tests\Feature\Guardian;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\HomeroomAssignment;
use App\Models\Score;
use App\Models\ScoringComponent;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeacherProgressScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Student $student;

    private Term $term;

    private Term $oldTerm;

    private Classroom $classroom;

    private ClassSubject $assignedSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 2));
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(Role::TEACHER);
        $year = AcademicYear::create(['name' => '2026/2027', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $this->oldTerm = Term::create([
            'academic_year_id' => $year->id, 'semester' => 1, 'status' => 'closed',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-06-30',
        ]);
        $this->term = Term::create([
            'academic_year_id' => $year->id, 'semester' => 2, 'status' => 'active',
            'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31',
        ]);
        $this->classroom = Classroom::create(['term_id' => $this->term->id, 'grade_level' => 2, 'section' => 'A']);
        $oldClassroom = Classroom::create(['term_id' => $this->oldTerm->id, 'grade_level' => 1, 'section' => 'A']);
        $this->student = Student::create(['nis' => 'SCOPE-1', 'name' => 'Siswa Uji', 'gender' => 'L', 'status' => 'active']);
        Enrollment::create([
            'student_id' => $this->student->id, 'classroom_id' => $oldClassroom->id,
            'term_id' => $this->oldTerm->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-07-01', 'status' => 'completed',
        ]);
        $enrollment = Enrollment::create([
            'student_id' => $this->student->id, 'classroom_id' => $this->classroom->id,
            'term_id' => $this->term->id, 'starts_on' => '2026-07-01', 'status' => 'active',
        ]);
        $component = ScoringComponent::create(['code' => 'UH', 'name' => 'Ulangan', 'source_type' => 'exam', 'sort_order' => 1, 'is_active' => true]);
        foreach (['MTK', 'IPA'] as $code) {
            $subject = Subject::create(['code' => $code, 'name' => $code, 'is_active' => true]);
            $classSubject = ClassSubject::create(['classroom_id' => $this->classroom->id, 'subject_id' => $subject->id]);
            if ($code === 'MTK') {
                $this->assignedSubject = $classSubject;
                TeachingAssignment::create([
                    'class_subject_id' => $classSubject->id, 'teacher_id' => $this->teacher->id,
                    'starts_on' => '2026-07-01', 'status' => 'assigned', 'assigned_by' => $this->teacher->id,
                ]);
            }
            $assessment = Assessment::create([
                'class_subject_id' => $classSubject->id, 'scoring_component_id' => $component->id,
                'title' => $code, 'assessed_on' => '2026-10-01', 'status' => Assessment::STATUS_PUBLISHED,
                'version' => 1, 'created_by' => $this->teacher->id,
            ]);
            Score::create(['assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id, 'score' => '88.00', 'graded_by' => $this->teacher->id]);
            $session = AttendanceSession::create([
                'class_subject_id' => $classSubject->id, 'meeting_number' => 1, 'held_on' => '2026-10-01',
                'status' => AttendanceSession::STATUS_FINALIZED, 'version' => 1, 'created_by' => $this->teacher->id,
            ]);
            AttendanceRecord::create(['attendance_session_id' => $session->id, 'enrollment_id' => $enrollment->id, 'status' => 'present', 'recorded_by' => $this->teacher->id]);
        }
    }

    public function test_teacher_only_sees_assigned_subject_and_accessible_terms(): void
    {
        $this->actingAs($this->teacher)->get(route('children.progress', $this->student))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('availableTerms', 1)->where('availableTerms.0.id', $this->term->id)
            ->has('classSubjects', 1)->where('classSubjects.0.code', 'MTK')
            ->has('subjectSummaries', 1)->has('activities', 2)
            ->where('activities.0.subject_code', 'MTK')->where('activities.1.subject_code', 'MTK'));

        $this->get(route('children.progress', [$this->student, 'subject_id' => Subject::where('code', 'IPA')->value('id')]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('activities', 0)->has('subjectSummaries', 1));
        $this->get(route('children.progress', [$this->student, 'term_id' => $this->oldTerm->id]))->assertForbidden();
    }

    public function test_unrelated_guardian_role_does_not_remove_teacher_access(): void
    {
        $this->teacher->assignRole(Role::GUARDIAN);
        $this->actingAs($this->teacher)->get(route('children.progress', $this->student))->assertOk();
    }

    public function test_own_guardian_role_can_see_all_subjects_and_historical_terms(): void
    {
        $this->teacher->assignRole(Role::GUARDIAN);
        $this->student->guardians()->attach($this->teacher, ['relationship' => 'ayah', 'is_primary' => true]);
        $this->actingAs($this->teacher)->get(route('children.progress', $this->student))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('availableTerms', 2)->has('classSubjects', 2)->has('activities', 4));
        $this->get(route('children.progress', [$this->student, 'term_id' => $this->oldTerm->id]))->assertOk();
    }

    public function test_homeroom_and_oversight_roles_see_all_subjects(): void
    {
        HomeroomAssignment::create([
            'classroom_id' => $this->classroom->id, 'teacher_id' => $this->teacher->id,
            'status' => 'active', 'assigned_by' => $this->teacher->id,
        ]);
        $this->actingAs($this->teacher)->get(route('children.progress', $this->student))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('activities', 4)->has('classSubjects', 2));
        HomeroomAssignment::query()->update(['status' => 'inactive']);
        $this->teacher->assignRole(Role::PRINCIPAL);
        $this->get(route('children.progress', [$this->student, 'term_id' => $this->oldTerm->id]))->assertOk();
    }

    public function test_future_or_revoked_assignment_cannot_read_progress(): void
    {
        $assignment = TeachingAssignment::where('class_subject_id', $this->assignedSubject->id)->firstOrFail();
        $assignment->update(['starts_on' => '2026-12-01']);
        $this->actingAs($this->teacher)->get(route('children.progress', $this->student))->assertForbidden();
        $assignment->update(['starts_on' => '2026-07-01', 'ends_on' => '2026-11-01', 'status' => 'ended']);
        $this->get(route('children.progress', $this->student))->assertForbidden();
    }

    public function test_same_term_transfer_does_not_grant_new_classroom_or_contact_access(): void
    {
        $enrollment = $this->student->enrollments()->where('term_id', $this->term->id)->firstOrFail();
        $enrollment->update(['status' => 'transferred', 'ends_on' => '2026-10-01']);
        $newClassroom = Classroom::create(['term_id' => $this->term->id, 'grade_level' => 2, 'section' => 'B']);
        Enrollment::create([
            'student_id' => $this->student->id, 'classroom_id' => $newClassroom->id,
            'term_id' => $this->term->id, 'starts_on' => '2026-10-01', 'status' => 'active',
        ]);

        $this->actingAs($this->teacher)->get(route('children.progress', [$this->student, 'term_id' => $this->term->id]))->assertForbidden();
        $this->get(route('children.homeroom-contact', $this->student))->assertForbidden();
    }

    public function test_active_historical_homeroom_grant_is_limited_to_its_enrollment(): void
    {
        $oldEnrollment = $this->student->enrollments()->where('term_id', $this->oldTerm->id)->firstOrFail();
        HomeroomAssignment::create([
            'classroom_id' => $oldEnrollment->classroom_id, 'teacher_id' => $this->teacher->id,
            'status' => 'active', 'assigned_by' => $this->teacher->id,
        ]);
        TeachingAssignment::query()->update(['status' => 'ended', 'ends_on' => '2026-11-01']);

        $this->actingAs($this->teacher)->get(route('children.progress', $this->student))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('availableTerms', 1)->where('term.id', $this->oldTerm->id));
        $this->get(route('children.progress', [$this->student, 'term_id' => $this->term->id]))->assertForbidden();
        $this->get(route('children.homeroom-contact', $this->student))->assertForbidden();
    }
}
