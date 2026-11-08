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

class ChildProgressTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guardianA;

    protected User $guardianB;

    protected User $teacher;

    protected Student $studentA1;

    protected Student $studentA2;

    protected Student $studentB;

    protected Term $term;

    protected Classroom $classroom;

    protected ClassSubject $classSubject;

    protected ScoringComponent $componentUH;

    protected Enrollment $enrollmentA1;

    protected Enrollment $enrollmentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Master Admin']);
        $this->admin->assignRole(Role::MASTER_ADMIN);

        $this->guardianA = User::factory()->create(['name' => 'Wali Murid A']);
        $this->guardianA->assignRole(Role::GUARDIAN);

        $this->guardianB = User::factory()->create(['name' => 'Wali Murid B']);
        $this->guardianB->assignRole(Role::GUARDIAN);

        $this->teacher = User::factory()->create(['name' => 'Ustadz Fajar']);
        $this->teacher->assignRole(Role::TEACHER);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
        ]);

        $this->term = Term::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'status' => 'active',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-12-31',
        ]);

        $this->classroom = Classroom::create([
            'term_id' => $this->term->id,
            'grade_level' => 1,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        // Assign homeroom teacher
        HomeroomAssignment::create([
            'classroom_id' => $this->classroom->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);

        $subject = Subject::create([
            'code' => 'MTK',
            'name' => 'Matematika',
            'category' => 'Umum',
            'is_active' => true,
        ]);

        $this->classSubject = ClassSubject::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $subject->id,
        ]);

        TeachingAssignment::create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
            'starts_on' => '2026-07-01',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        $this->componentUH = ScoringComponent::create([
            'code' => 'UH',
            'name' => 'Ulangan Harian',
            'source_type' => 'exam',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Students
        $this->studentA1 = Student::create(['nis' => '1001', 'name' => 'Anak A1 (Ahmad)', 'gender' => 'L', 'status' => 'active']);
        $this->studentA2 = Student::create(['nis' => '1002', 'name' => 'Anak A2 (Aisyah)', 'gender' => 'P', 'status' => 'active']);
        $this->studentB = Student::create(['nis' => '1003', 'name' => 'Anak B (Budi)', 'gender' => 'L', 'status' => 'active']);

        // Link Guardian A to A1 and A2
        $this->studentA1->guardians()->attach($this->guardianA->id, ['relationship' => 'ayah', 'is_primary' => true]);
        $this->studentA2->guardians()->attach($this->guardianA->id, ['relationship' => 'ayah', 'is_primary' => false]);

        // Link Guardian B to B
        $this->studentB->guardians()->attach($this->guardianB->id, ['relationship' => 'ibu', 'is_primary' => true]);

        // Enrollments
        $this->enrollmentA1 = Enrollment::create([
            'student_id' => $this->studentA1->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);

        $this->enrollmentB = Enrollment::create([
            'student_id' => $this->studentB->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);
    }

    public function test_guardian_can_view_own_children_list(): void
    {
        $this->actingAs($this->guardianA);

        $response = $this->get(route('children.index'));
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Guardian/Children/Index')
            ->has('children', 2)
            ->where('children.0.name', 'Anak A1 (Ahmad)')
            ->where('children.1.name', 'Anak A2 (Aisyah)')
        );
    }

    public function test_guardian_with_single_child_redirects_to_progress(): void
    {
        $this->actingAs($this->guardianB);

        $response = $this->get(route('children.index'));
        $response->assertRedirect(route('children.progress', $this->studentB->id));
    }

    public function test_guardian_can_view_own_child_progress_page(): void
    {
        $this->actingAs($this->guardianA);

        $response = $this->get(route('children.progress', $this->studentA1->id));
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Guardian/Progress/Show')
            ->where('student.id', $this->studentA1->id)
            ->where('student.name', 'Anak A1 (Ahmad)')
            ->where('hasEnrollment', true)
            ->where('homeroom.name', 'Ustadz Fajar')
        );
    }

    public function test_guardian_cannot_access_other_child_progress_idor_protection(): void
    {
        // Guardian A attempts to view Student B's progress
        $this->actingAs($this->guardianA);

        $response = $this->get(route('children.progress', $this->studentB->id));
        $response->assertForbidden();
    }

    public function test_draft_assessments_and_draft_sessions_do_not_leak_to_guardian(): void
    {
        // 1. Published assessment with score 88 for Student A1
        $publishedAssessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'UH 1 Aljabar',
            'assessed_on' => '2026-08-10',
            'status' => Assessment::STATUS_PUBLISHED,
            'version' => 2,
            'created_by' => $this->teacher->id,
        ]);
        Score::create([
            'assessment_id' => $publishedAssessment->id,
            'enrollment_id' => $this->enrollmentA1->id,
            'score' => '88.00',
            'feedback' => 'Bagus sekali',
            'graded_by' => $this->teacher->id,
        ]);

        // 2. Draft assessment with score 60 for Student A1 (Must NOT leak!)
        $draftAssessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'UH 2 Geometri (DRAF RAHASIA GURU)',
            'assessed_on' => '2026-08-20',
            'status' => Assessment::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->teacher->id,
        ]);
        Score::create([
            'assessment_id' => $draftAssessment->id,
            'enrollment_id' => $this->enrollmentA1->id,
            'score' => '60.00',
            'graded_by' => $this->teacher->id,
        ]);

        // 3. Finalized attendance session (Present)
        $finalizedSession = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'topic' => 'Pengenalan Bilangan',
            'status' => AttendanceSession::STATUS_FINALIZED,
            'version' => 2,
            'created_by' => $this->teacher->id,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $finalizedSession->id,
            'enrollment_id' => $this->enrollmentA1->id,
            'status' => 'present',
            'recorded_by' => $this->teacher->id,
        ]);

        // 4. Draft attendance session (Must NOT leak!)
        $draftSession = AttendanceSession::create([
            'class_subject_id' => $this->classSubject->id,
            'meeting_number' => 2,
            'held_on' => '2026-08-08',
            'topic' => 'Draf Pertemuan',
            'status' => AttendanceSession::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->teacher->id,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $draftSession->id,
            'enrollment_id' => $this->enrollmentA1->id,
            'status' => 'absent',
            'recorded_by' => $this->teacher->id,
        ]);

        // Guardian A views progress
        $this->actingAs($this->guardianA);
        $response = $this->get(route('children.progress', $this->studentA1->id));
        $response->assertOk();

        $response->assertInertia(function (Assert $page) {
            $page->component('Guardian/Progress/Show')
                // Only 2 visible activities (1 published assessment + 1 finalized attendance)
                ->has('activities', 2)
                ->where('activities.0.title', 'UH 1 Aljabar')
                ->where('activities.0.score', '88.00')
                ->where('activities.1.title', 'Presensi · Pertemuan ke-1')
                ->where('activities.1.status', 'present');

            // Verify draft titles are nowhere in the activities
            $activities = $page->toArray()['props']['activities'];
            $titles = array_column($activities, 'title');
            $this->assertNotContains('UH 2 Geometri (DRAF RAHASIA GURU)', $titles);
            $this->assertNotContains('Presensi · Pertemuan ke-2', $titles);
        });
    }

    public function test_other_students_scores_do_not_leak(): void
    {
        $assessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'UH 1',
            'assessed_on' => '2026-08-10',
            'status' => Assessment::STATUS_PUBLISHED,
            'version' => 2,
            'created_by' => $this->teacher->id,
        ]);

        // Student A1 has 85
        Score::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollmentA1->id,
            'score' => '85.00',
            'feedback' => 'Catatan khusus Ahmad',
            'graded_by' => $this->teacher->id,
        ]);

        // Student B has 99
        Score::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollmentB->id,
            'score' => '99.00',
            'feedback' => 'Catatan rahasia Budi',
            'graded_by' => $this->teacher->id,
        ]);

        $this->actingAs($this->guardianA);
        $response = $this->get(route('children.progress', $this->studentA1->id));
        $response->assertOk();

        $response->assertInertia(function (Assert $page) {
            $page->component('Guardian/Progress/Show')
                ->has('activities', 1)
                ->where('activities.0.score', '85.00')
                ->where('activities.0.feedback', 'Catatan khusus Ahmad');

            // Ensure Budi's score is nowhere in the page props
            $propsJson = json_encode($page->toArray()['props']);
            $this->assertStringNotContainsString('99.00', $propsJson);
            $this->assertStringNotContainsString('Catatan rahasia Budi', $propsJson);
        });
    }

    public function test_admin_can_view_any_student_progress_oversight(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('children.progress', $this->studentA1->id));
        $response->assertOk();

        $responseB = $this->get(route('children.progress', $this->studentB->id));
        $responseB->assertOk();
    }

    public function test_homeroom_contact_endpoint_returns_homeroom_info(): void
    {
        $this->actingAs($this->guardianA);

        $response = $this->get(route('children.homeroom-contact', $this->studentA1->id));
        $response->assertOk();
        $response->assertJson([
            'teacher' => [
                'name' => 'Ustadz Fajar',
                'email' => $this->teacher->email,
                'classroom' => '1-A',
            ],
        ]);
    }

    public function test_unauthorized_user_cannot_access_homeroom_contact(): void
    {
        // Guardian A cannot access student B's homeroom contact
        $this->actingAs($this->guardianA);

        $response = $this->get(route('children.homeroom-contact', $this->studentB->id));
        $response->assertForbidden();
    }
}
