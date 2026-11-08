<?php

namespace Tests\Feature\Academic;

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
use Tests\TestCase;

class ReportCardPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $homeroomTeacher;

    protected User $otherTeacher;

    protected User $guardian;

    protected User $otherGuardian;

    protected AcademicYear $academicYear;

    protected Term $term;

    protected Classroom $classroom;

    protected Student $student;

    protected Enrollment $enrollment;

    protected Subject $mathSubject;

    protected ClassSubject $mathClassSubject;

    protected GradingPolicy $policy;

    protected ReportCard $reportCard;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->masterAdmin = User::factory()->create();
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->homeroomTeacher = User::factory()->create(['name' => 'Wali Kelas 7A']);
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $this->otherTeacher->assignRole(Role::TEACHER);

        $this->guardian = User::factory()->create(['name' => 'Wali Siswa Zaid']);
        $this->guardian->assignRole(Role::GUARDIAN);

        $this->otherGuardian = User::factory()->create(['name' => 'Wali Siswa Lain']);
        $this->otherGuardian->assignRole(Role::GUARDIAN);

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

        $this->student = Student::create([
            'nis' => '3001',
            'name' => 'Zaid bin Tsabit',
            'gender' => 'L',
            'status' => 'active',
        ]);

        // Link student to guardian
        $this->student->guardians()->attach($this->guardian->id, ['relationship' => 'Ayah']);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
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

        $this->policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 75.00,
            'status' => 'active',
            'created_by' => $this->masterAdmin->id,
            'activated_by' => $this->masterAdmin->id,
            'activated_at' => now(),
        ]);

        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();
        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $assignmentComp->id,
            'weight_percentage' => 100.00,
        ]);

        $a1 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->otherTeacher->id,
            'scoring_component_id' => $assignmentComp->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => 'published',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $a1->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 90.00,
            'graded_by' => $this->otherTeacher->id,
        ]);

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
            'enrollment_id' => $this->enrollment->id,
            'status' => 'present',
            'recorded_by' => $this->otherTeacher->id,
        ]);

        // Build draft report card
        $snapshotService = app(ReportSnapshotService::class);
        $this->reportCard = $snapshotService->buildDraft($this->enrollment, 'Catatan awal wali kelas');
    }

    public function test_prepare_file_rejects_on_draft_report_card(): void
    {
        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$this->reportCard->id}/prepare-file", [
                'expected_version' => 1,
            ]);

        // Policy denies prepareFile on draft reports
        $response->assertForbidden();
    }

    public function test_prepare_file_succeeds_on_finalized_report_card(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $finalized = $snapshotService->finalizeReportCard($this->reportCard, $this->homeroomTeacher);

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$finalized->id}/prepare-file", [
                'expected_version' => 2,
            ]);

        $response->assertOk();
        $finalized->refresh();

        $this->assertNotNull($finalized->private_file_path);
        Storage::disk('local')->assertExists($finalized->private_file_path);
    }

    public function test_non_homeroom_teacher_cannot_prepare_file(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $finalized = $snapshotService->finalizeReportCard($this->reportCard, $this->homeroomTeacher);

        $response = $this->actingAs($this->otherTeacher)
            ->postJson("/academic/report-cards/{$finalized->id}/prepare-file", [
                'expected_version' => 2,
            ]);

        $response->assertForbidden();
    }

    public function test_authorized_staff_can_download_finalized_pdf(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $finalized = $snapshotService->finalizeReportCard($this->reportCard, $this->homeroomTeacher);

        // Homeroom teacher downloads PDF
        $response = $this->actingAs($this->homeroomTeacher)
            ->get("/academic/report-cards/{$finalized->id}/pdf");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        // Master admin can also download
        $adminResponse = $this->actingAs($this->masterAdmin)
            ->get("/academic/report-cards/{$finalized->id}/pdf");

        $adminResponse->assertOk();
        $this->assertEquals('application/pdf', $adminResponse->headers->get('Content-Type'));
    }

    public function test_guardian_cannot_download_pdf_when_report_is_only_finalized(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $finalized = $snapshotService->finalizeReportCard($this->reportCard, $this->homeroomTeacher);

        // Finalized is NOT published -> guardian must be forbidden (403)
        $response = $this->actingAs($this->guardian)
            ->get("/academic/report-cards/{$finalized->id}/pdf");

        $response->assertForbidden();
    }

    public function test_guardian_can_download_pdf_when_report_is_published(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $finalized = $snapshotService->finalizeReportCard($this->reportCard, $this->homeroomTeacher);

        // Publish report
        $finalized->update([
            'status' => ReportCardStatus::PUBLISHED,
            'published_at' => now(),
            'published_by' => $this->masterAdmin->id,
            'availability' => ReportCardAvailability::CURRENT,
        ]);

        $response = $this->actingAs($this->guardian)
            ->get("/academic/report-cards/{$finalized->id}/pdf");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_guardian_cannot_download_other_student_report_card_idor_protection(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $finalized = $snapshotService->finalizeReportCard($this->reportCard, $this->homeroomTeacher);

        $finalized->update([
            'status' => ReportCardStatus::PUBLISHED,
            'published_at' => now(),
            'published_by' => $this->masterAdmin->id,
            'availability' => ReportCardAvailability::CURRENT,
        ]);

        // Other guardian attempts to download
        $response = $this->actingAs($this->otherGuardian)
            ->get("/academic/report-cards/{$finalized->id}/pdf");

        $response->assertForbidden();
    }
}
