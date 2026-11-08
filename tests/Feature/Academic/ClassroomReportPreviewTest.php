<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\GradingPolicy;
use App\Models\GradingPolicyComponent;
use App\Models\HomeroomAssignment;
use App\Models\Score;
use App\Models\ScoringComponent;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomReportPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $academicAdmin;

    protected User $homeroomTeacher;

    protected User $otherTeacher;

    protected User $dataEntryAdmin;

    protected User $guardian;

    protected AcademicYear $academicYear;

    protected Term $term;

    protected Classroom $classroom;

    protected Student $student1;

    protected Student $student2;

    protected Enrollment $enrollment1;

    protected Enrollment $enrollment2;

    protected Subject $mathSubject;

    protected ClassSubject $mathClassSubject;

    protected GradingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAdmin = User::factory()->create();
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->academicAdmin = User::factory()->create();
        $this->academicAdmin->assignRole(Role::ACADEMIC_ADMIN);

        $this->homeroomTeacher = User::factory()->create(['name' => 'Wali Kelas 7A']);
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $this->otherTeacher->assignRole(Role::TEACHER);

        $this->dataEntryAdmin = User::factory()->create();
        $this->dataEntryAdmin->assignRole(Role::DATA_ENTRY_ADMIN);

        $this->guardian = User::factory()->create();
        $this->guardian->assignRole(Role::GUARDIAN);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'status' => 'active',
        ]);

        $this->term = Term::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Ganjil 2026/2027',
            'semester' => 1,
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-12-31',
            'status' => 'active',
        ]);

        $this->classroom = Classroom::create([
            'term_id' => $this->term->id,
            'grade_level' => 7,
            'section' => 'A',
            'capacity' => 30,
            'status' => 'active',
        ]);

        HomeroomAssignment::create([
            'classroom_id' => $this->classroom->id,
            'teacher_id' => $this->homeroomTeacher->id,
            'status' => 'active',
            'assigned_by' => $this->masterAdmin->id,
        ]);

        $this->student1 = Student::create([
            'nis' => '1001',
            'name' => 'Ahmad Fulan',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->student2 = Student::create([
            'nis' => '1002',
            'name' => 'Budi Santoso',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->enrollment1 = Enrollment::create([
            'student_id' => $this->student1->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);

        $this->enrollment2 = Enrollment::create([
            'student_id' => $this->student2->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);

        $this->mathSubject = Subject::create([
            'code' => 'MTK',
            'name' => 'Matematika',
            'order' => 1,
        ]);

        $this->mathClassSubject = ClassSubject::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->mathSubject->id,
        ]);

        TeachingAssignment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'teacher_id' => $this->otherTeacher->id,
            'starts_on' => '2026-07-01',
            'status' => 'assigned',
            'assigned_by' => $this->masterAdmin->id,
        ]);

        // Active grading policy: Tugas (50%), Ulangan Harian (50%)
        $this->policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 75.00,
            'status' => 'active',
            'created_by' => $this->masterAdmin->id,
            'activated_by' => $this->masterAdmin->id,
            'activated_at' => now(),
        ]);

        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();
        $dailyTestComp = ScoringComponent::where('code', 'daily_test')->first();

        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $assignmentComp->id,
            'weight_percentage' => 50.00,
        ]);
        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $dailyTestComp->id,
            'weight_percentage' => 50.00,
        ]);
    }

    public function test_master_admin_can_access_report_preview(): void
    {
        $response = $this->actingAs($this->masterAdmin)
            ->get("/academic/classrooms/{$this->classroom->id}/report-preview");

        $response->assertOk();
    }

    public function test_academic_admin_can_access_report_preview(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->get("/academic/classrooms/{$this->classroom->id}/report-preview");

        $response->assertOk();
    }

    public function test_active_homeroom_teacher_can_access_report_preview(): void
    {
        $response = $this->actingAs($this->homeroomTeacher)
            ->get("/academic/classrooms/{$this->classroom->id}/report-preview");

        $response->assertOk();
    }

    public function test_non_homeroom_subject_teacher_is_forbidden(): void
    {
        $response = $this->actingAs($this->otherTeacher)
            ->get("/academic/classrooms/{$this->classroom->id}/report-preview");

        $response->assertForbidden();
    }

    public function test_data_entry_admin_is_forbidden(): void
    {
        $response = $this->actingAs($this->dataEntryAdmin)
            ->get("/academic/classrooms/{$this->classroom->id}/report-preview");

        $response->assertForbidden();
    }

    public function test_guardian_is_forbidden(): void
    {
        $response = $this->actingAs($this->guardian)
            ->get("/academic/classrooms/{$this->classroom->id}/report-preview");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_report_preview(): void
    {
        $response = $this->getJson("/academic/classrooms/{$this->classroom->id}/report-preview");

        $response->assertUnauthorized();
    }

    public function test_completeness_evaluation_and_blocking_reasons_in_preview(): void
    {
        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();
        $dailyTestComp = ScoringComponent::where('code', 'daily_test')->first();

        // 1. Published Assignment assessment
        $a1 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->otherTeacher->id,
            'scoring_component_id' => $assignmentComp->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => 'published',
            'version' => 1,
        ]);

        // Student 1 gets score 80
        Score::create([
            'assessment_id' => $a1->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => 80.00,
            'graded_by' => $this->otherTeacher->id,
        ]);

        // Student 2 gets score 85
        Score::create([
            'assessment_id' => $a1->id,
            'enrollment_id' => $this->enrollment2->id,
            'score' => 85.00,
            'graded_by' => $this->otherTeacher->id,
        ]);

        // 2. Published Daily Test assessment
        $a2 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->otherTeacher->id,
            'scoring_component_id' => $dailyTestComp->id,
            'title' => 'UH 1',
            'assessed_on' => '2026-08-15',
            'status' => 'published',
            'version' => 1,
        ]);

        // Only student 1 gets score 90; student 2 has NO score for UH 1
        Score::create([
            'assessment_id' => $a2->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => 90.00,
            'graded_by' => $this->otherTeacher->id,
        ]);

        // Finalized attendance session
        $sess = AttendanceSession::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->otherTeacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'finalized',
            'version' => 1,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $sess->id,
            'enrollment_id' => $this->enrollment1->id,
            'status' => 'present',
            'recorded_by' => $this->otherTeacher->id,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $sess->id,
            'enrollment_id' => $this->enrollment2->id,
            'status' => 'present',
            'recorded_by' => $this->otherTeacher->id,
        ]);

        $response = $this->actingAs($this->homeroomTeacher)
            ->getJson("/academic/classrooms/{$this->classroom->id}/report-preview");

        $response->assertOk();

        $data = $response->json();

        // Summary checks
        $this->assertEquals(2, $data['summary']['total_students']);
        $this->assertEquals(1, $data['summary']['complete_students']);
        $this->assertEquals(1, $data['summary']['incomplete_students']);
        $this->assertFalse($data['summary']['is_all_complete']);

        // Student 1 (Ahmad Fulan): Complete (50% * 80 + 50% * 90 = 85.00 >= 75.00)
        $s1 = collect($data['students'])->firstWhere('enrollment_id', $this->enrollment1->id);
        $this->assertTrue($s1['is_complete']);
        $this->assertTrue($s1['can_finalize']);
        $this->assertEmpty($s1['blocked_reasons']);
        $this->assertEquals('85.00', $s1['subjects'][0]['final_score']);
        $this->assertEquals('Mencapai KKM', $s1['subjects'][0]['kkm_status']);
        $this->assertEmpty($s1['subjects'][0]['missing_components']);

        // Student 2 (Budi Santoso): Incomplete (Missing daily_test component)
        $s2 = collect($data['students'])->firstWhere('enrollment_id', $this->enrollment2->id);
        $this->assertFalse($s2['is_complete']);
        $this->assertFalse($s2['can_finalize']);
        $this->assertNull($s2['subjects'][0]['final_score']);
        $this->assertEquals('Belum tersedia', $s2['subjects'][0]['kkm_status']);
        $this->assertContains('daily_test', $s2['subjects'][0]['missing_components']);
        $this->assertNotEmpty($s2['blocked_reasons']);
    }
}
