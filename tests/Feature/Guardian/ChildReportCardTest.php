<?php

namespace Tests\Feature\Guardian;

use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
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
use App\Models\ReportCard;
use App\Models\Score;
use App\Models\ScoringComponent;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\Reports\ReportSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChildReportCardTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $homeroomTeacher;

    protected User $otherTeacher;

    protected User $guardianA;

    protected User $guardianB;

    protected User $principal;

    protected Student $studentA;

    protected Student $studentB;

    protected Classroom $classroom;

    protected Term $term;

    protected Enrollment $enrollmentA;

    protected Enrollment $enrollmentB;

    protected Subject $mathSubject;

    protected ClassSubject $mathClassSubject;

    protected GradingPolicy $policy;

    protected ScoringComponent $assignmentComp;

    protected ScoringComponent $dailyTestComp;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->homeroomTeacher = User::factory()->create(['name' => 'Ustadz Ahmad']);
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $this->otherTeacher->assignRole(Role::TEACHER);

        $this->principal = User::factory()->create(['name' => 'Kepala Madrasah']);
        $this->principal->assignRole(Role::PRINCIPAL);

        $this->guardianA = User::factory()->create(['name' => 'Wali Murid A']);
        $this->guardianA->assignRole(Role::GUARDIAN);

        $this->guardianB = User::factory()->create(['name' => 'Wali Murid B']);
        $this->guardianB->assignRole(Role::GUARDIAN);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'status' => 'active',
        ]);

        $this->term = Term::create([
            'academic_year_id' => $year->id,
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

        $this->studentA = Student::create([
            'nis' => '1001',
            'name' => 'Ahmad Dahlan',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->studentB = Student::create([
            'nis' => '1002',
            'name' => 'Fatmawati',
            'gender' => 'P',
            'status' => 'active',
        ]);

        // Link Guardian A -> Student A, Guardian B -> Student B
        $this->studentA->guardians()->attach($this->guardianA->id, ['relationship' => 'Ayah Kandung']);
        $this->studentB->guardians()->attach($this->guardianB->id, ['relationship' => 'Ibu Kandung']);

        $this->enrollmentA = Enrollment::create([
            'student_id' => $this->studentA->id,
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
            'teacher_id' => $this->homeroomTeacher->id,
            'starts_on' => '2026-07-01',
            'status' => 'assigned',
            'assigned_by' => $this->masterAdmin->id,
        ]);

        $this->policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 75.00,
            'status' => 'active',
            'created_by' => $this->masterAdmin->id,
            'activated_by' => $this->masterAdmin->id,
            'activated_at' => now(),
        ]);

        $this->assignmentComp = ScoringComponent::where('code', 'assignment')->first();
        $this->dailyTestComp = ScoringComponent::where('code', 'daily_test')->first();

        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $this->assignmentComp->id,
            'weight_percentage' => 50.00,
        ]);
        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $this->dailyTestComp->id,
            'weight_percentage' => 50.00,
        ]);

        // Create assessments
        $a1 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->homeroomTeacher->id,
            'scoring_component_id' => $this->assignmentComp->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => 'published',
            'version' => 1,
        ]);

        $a2 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->homeroomTeacher->id,
            'scoring_component_id' => $this->dailyTestComp->id,
            'title' => 'Ulangan Harian 1',
            'assessed_on' => '2026-08-15',
            'status' => 'published',
            'version' => 1,
        ]);

        $sess = AttendanceSession::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->homeroomTeacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'finalized',
            'version' => 1,
        ]);

        // Complete scores and attendance for both students
        foreach ([$this->enrollmentA, $this->enrollmentB] as $enr) {
            Score::create([
                'assessment_id' => $a1->id,
                'enrollment_id' => $enr->id,
                'score' => 80.00,
                'graded_by' => $this->homeroomTeacher->id,
            ]);

            Score::create([
                'assessment_id' => $a2->id,
                'enrollment_id' => $enr->id,
                'score' => 90.00,
                'graded_by' => $this->homeroomTeacher->id,
            ]);

            AttendanceRecord::create([
                'attendance_session_id' => $sess->id,
                'enrollment_id' => $enr->id,
                'status' => 'present',
                'recorded_by' => $this->homeroomTeacher->id,
            ]);
        }
    }

    /**
     * Helper to create and finalize a report card for student.
     */
    protected function createAndFinalizeReport(Enrollment $enrollment): ReportCard
    {
        $snapshotService = app(ReportSnapshotService::class);
        $draft = $snapshotService->buildDraft($enrollment, $this->policy);
        $draft->update(['homeroom_note' => 'Pertahankan prestasimu.']);

        return $snapshotService->finalizeReportCard($draft, $this->homeroomTeacher);
    }

    public function test_staff_can_publish_finalized_report_card_with_ready_pdf(): void
    {
        $report = $this->createAndFinalizeReport($this->enrollmentA);

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report->id}/publish", [
                'expected_version' => $report->version,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('report_cards', [
            'id' => $report->id,
            'status' => 'published',
            'availability' => 'current',
            'published_by' => $this->homeroomTeacher->id,
        ]);

        $report->refresh();
        $this->assertNotNull($report->published_at);
        $this->assertNotNull($report->private_file_path);
        Storage::disk('local')->assertExists($report->private_file_path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PUBLISH_REPORT_CARD',
            'entity_id' => $report->id,
        ]);
    }

    public function test_cannot_publish_draft_report_card(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $draft = $snapshotService->buildDraft($this->enrollmentA, $this->policy);

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$draft->id}/publish", [
                'expected_version' => $draft->version,
            ]);

        // Policy forbids publishing non-finalized reports
        $response->assertForbidden();
    }

    public function test_publishing_supersedes_previous_published_report_card_for_same_enrollment(): void
    {
        $report1 = $this->createAndFinalizeReport($this->enrollmentA);

        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/publish");

        $report1->refresh();
        $this->assertEquals(ReportCardStatus::PUBLISHED, $report1->status);
        $this->assertEquals(ReportCardAvailability::CURRENT, $report1->availability);

        // Create revision 2
        $report2 = ReportCard::create([
            'enrollment_id' => $this->enrollmentA->id,
            'term_id' => $this->term->id,
            'grading_policy_id' => $this->policy->id,
            'homeroom_assignment_id' => $report1->homeroom_assignment_id,
            'revision' => 2,
            'supersedes_id' => $report1->id,
            'status' => ReportCardStatus::FINALIZED,
            'availability' => ReportCardAvailability::CURRENT,
            'version' => 1,
            'source_version' => $report1->source_version,
            'finalized_at' => now(),
            'finalized_by' => $this->homeroomTeacher->id,
        ]);

        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report2->id}/publish");

        $report1->refresh();
        $report2->refresh();

        $this->assertEquals(ReportCardAvailability::SUPERSEDED, $report1->availability);
        $this->assertEquals(ReportCardAvailability::CURRENT, $report2->availability);
        $this->assertEquals(ReportCardStatus::PUBLISHED, $report2->status);
    }

    public function test_publishing_blocked_if_source_data_changed_since_finalization(): void
    {
        $report = $this->createAndFinalizeReport($this->enrollmentA);

        // Alter underlying score after finalization
        $score = Score::where('enrollment_id', $this->enrollmentA->id)->first();
        $score->update(['score' => 95.00]);

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report->id}/publish");

        $response->assertStatus(409);
        $response->assertJsonStructure(['errors' => ['source_changed']]);

        $report->refresh();
        $this->assertEquals(ReportCardStatus::FINALIZED, $report->status);
    }

    public function test_non_homeroom_teacher_cannot_publish_report_card(): void
    {
        $report = $this->createAndFinalizeReport($this->enrollmentA);

        $response = $this->actingAs($this->otherTeacher)
            ->postJson("/academic/report-cards/{$report->id}/publish");

        $response->assertForbidden();
    }

    public function test_guardian_portal_zero_leak_draft_or_finalized_reports_are_not_visible(): void
    {
        // Report is only finalized, NOT published yet
        $report = $this->createAndFinalizeReport($this->enrollmentA);

        $response = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentA->id}/report-cards");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Guardian/Reports/Index')
            ->has('reports', 0) // Zero Leak!
            ->where('student.name', 'Ahmad Dahlan')
        );
    }

    public function test_guardian_cannot_access_unpublished_report_detail_or_pdf(): void
    {
        $report = $this->createAndFinalizeReport($this->enrollmentA);

        // Attempting to view detail of non-published report yields 404 (Zero Leak)
        $responseDetail = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentA->id}/report-cards/{$report->id}");

        $responseDetail->assertNotFound();

        // Attempting to download PDF of non-published report yields 404 (Zero Leak)
        $responsePdf = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentA->id}/report-cards/{$report->id}/pdf");

        $responsePdf->assertNotFound();
    }

    public function test_guardian_can_view_and_download_published_report_card_of_their_child(): void
    {
        $report = $this->createAndFinalizeReport($this->enrollmentA);

        // Publish report
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report->id}/publish");

        // 1. Index view
        $responseIndex = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentA->id}/report-cards");

        $responseIndex->assertOk();
        $responseIndex->assertInertia(fn (Assert $page) => $page
            ->component('Guardian/Reports/Index')
            ->has('reports', 1)
            ->where('reports.0.id', $report->id)
            ->where('reports.0.classroom_name', '7-A')
        );

        // 2. Show detail view
        $responseShow = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentA->id}/report-cards/{$report->id}");

        $responseShow->assertOk();
        $responseShow->assertInertia(fn (Assert $page) => $page
            ->component('Guardian/Reports/Show')
            ->where('report.id', $report->id)
            ->where('report.homeroom_note', 'Pertahankan prestasimu.')
            ->has('report.items', 1)
        );

        // 3. PDF download
        $responsePdf = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentA->id}/report-cards/{$report->id}/pdf");

        $responsePdf->assertOk();
        $responsePdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_guardian_cannot_access_other_students_report_card_idor_protection(): void
    {
        // Report A for Student A, Report B for Student B
        $reportA = $this->createAndFinalizeReport($this->enrollmentA);
        $reportB = $this->createAndFinalizeReport($this->enrollmentB);

        // Publish both
        $this->actingAs($this->homeroomTeacher)->postJson("/academic/report-cards/{$reportA->id}/publish");
        $this->actingAs($this->homeroomTeacher)->postJson("/academic/report-cards/{$reportB->id}/publish");

        // Guardian A tries to view Student B's report index -> Forbidden (403)
        $response = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentB->id}/report-cards");
        $response->assertForbidden();

        // Guardian A tries to view Student B's report card under Student A's URL -> 404 (IDOR)
        $responseShowMismatch = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentA->id}/report-cards/{$reportB->id}");
        $responseShowMismatch->assertNotFound();

        // Guardian A tries to view Student B's report card directly under Student B's URL -> 403 (IDOR)
        $responseShowDirect = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentB->id}/report-cards/{$reportB->id}");
        $responseShowDirect->assertForbidden();

        // Guardian A tries to download Student B's PDF under Student A's URL -> 404 (IDOR)
        $responsePdfMismatch = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentA->id}/report-cards/{$reportB->id}/pdf");
        $responsePdfMismatch->assertNotFound();

        // Guardian A tries to download Student B's PDF directly under Student B's URL -> 403 (IDOR)
        $responsePdfDirect = $this->actingAs($this->guardianA)
            ->get("/children/{$this->studentB->id}/report-cards/{$reportB->id}/pdf");
        $responsePdfDirect->assertForbidden();
    }
}
