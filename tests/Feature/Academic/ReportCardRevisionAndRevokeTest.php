<?php

namespace Tests\Feature\Academic;

use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
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

class ReportCardRevisionAndRevokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $homeroomTeacher;

    protected User $otherTeacher;

    protected User $guardian;

    protected Student $student;

    protected Classroom $classroom;

    protected Term $term;

    protected Enrollment $enrollment;

    protected GradingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->homeroomTeacher = User::factory()->create(['name' => 'Wali Kelas 7A']);
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $this->otherTeacher->assignRole(Role::TEACHER);

        $this->guardian = User::factory()->create(['name' => 'Wali Murid']);
        $this->guardian->assignRole(Role::GUARDIAN);

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

        $this->student = Student::create([
            'nis' => '1001',
            'name' => 'Ahmad Dahlan',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->student->guardians()->attach($this->guardian->id, ['relationship' => 'Ayah Kandung']);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);

        $mathSubject = Subject::create([
            'code' => 'MTK',
            'name' => 'Matematika',
            'order' => 1,
        ]);

        $mathClassSubject = ClassSubject::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $mathSubject->id,
        ]);

        TeachingAssignment::create([
            'class_subject_id' => $mathClassSubject->id,
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

        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();
        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $assignmentComp->id,
            'weight_percentage' => 100.00,
        ]);

        $a1 = Assessment::create([
            'class_subject_id' => $mathClassSubject->id,
            'created_by' => $this->homeroomTeacher->id,
            'scoring_component_id' => $assignmentComp->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => 'published',
            'version' => 1,
        ]);

        Score::create([
            'assessment_id' => $a1->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 85.00,
            'graded_by' => $this->homeroomTeacher->id,
        ]);
    }

    /**
     * Helper to create, finalize, and publish a report card.
     */
    protected function createAndPublishReport(): ReportCard
    {
        $snapshotService = app(ReportSnapshotService::class);
        $draft = $snapshotService->buildDraft($this->enrollment, 'Catatan awal.', $this->policy);
        $finalized = $snapshotService->finalizeReportCard($draft, $this->homeroomTeacher);

        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$finalized->id}/publish");

        return $finalized->fresh();
    }

    public function test_staff_can_create_revision_draft_from_published_report(): void
    {
        $report1 = $this->createAndPublishReport();
        $this->assertEquals(1, $report1->revision);
        $this->assertEquals(ReportCardStatus::PUBLISHED, $report1->status);

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revisions", [
                'reason' => 'Perbaikan nilai susulan tugas matematika',
            ]);

        $response->assertStatus(201);
        $data = $response->json();

        $this->assertDatabaseHas('report_cards', [
            'enrollment_id' => $this->enrollment->id,
            'revision' => 2,
            'supersedes_id' => $report1->id,
            'status' => 'draft',
            'availability' => 'current',
        ]);

        $report1->refresh();
        $this->assertEquals(ReportCardAvailability::NEEDS_REVISION, $report1->availability);
        $this->assertEquals(ReportCardStatus::PUBLISHED, $report1->status); // History preserved!

        $draft = ReportCard::where('enrollment_id', $this->enrollment->id)
            ->where('revision', 2)
            ->first();
        $this->assertEquals('Catatan awal.', $draft->homeroom_note); // Note carried over
        $this->assertEquals('Perbaikan nilai susulan tugas matematika', $draft->lifecycle_reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREATE_REPORT_CARD_REVISION',
            'entity_id' => $draft->id,
        ]);
    }

    public function test_cannot_create_revision_from_draft_report(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $draft = $snapshotService->buildDraft($this->enrollment, 'Draft.', $this->policy);

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$draft->id}/revisions", []);

        $response->assertForbidden();
    }

    public function test_cannot_create_revision_if_active_draft_already_exists(): void
    {
        $report1 = $this->createAndPublishReport();

        // Create revision 2
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revisions");

        // Attempting to create another revision while revision 2 is still draft
        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revisions");

        $response->assertStatus(409);
        $this->assertStringContainsString('sudah ada', $response->json('message'));
    }

    public function test_non_homeroom_teacher_cannot_create_revision(): void
    {
        $report1 = $this->createAndPublishReport();

        $response = $this->actingAs($this->otherTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revisions");

        $response->assertForbidden();
    }

    public function test_staff_can_revoke_published_report_card_with_reason(): void
    {
        $report1 = $this->createAndPublishReport();

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revoke", [
                'reason' => 'Ditemukan ketidaksesuaian nilai fatal pada dokumen cetak.',
            ]);

        $response->assertOk();

        $report1->refresh();
        $this->assertEquals(ReportCardStatus::REVOKED, $report1->status);
        $this->assertEquals(ReportCardAvailability::SUPERSEDED, $report1->availability);
        $this->assertEquals($this->homeroomTeacher->id, $report1->revoked_by);
        $this->assertNotNull($report1->revoked_at);
        $this->assertEquals('Ditemukan ketidaksesuaian nilai fatal pada dokumen cetak.', $report1->lifecycle_reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'REVOKE_REPORT_CARD',
            'entity_id' => $report1->id,
        ]);
    }

    public function test_revoke_requires_valid_reason(): void
    {
        $report1 = $this->createAndPublishReport();

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revoke", [
                'reason' => '', // empty
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_cannot_revoke_draft_report_card(): void
    {
        $snapshotService = app(ReportSnapshotService::class);
        $draft = $snapshotService->buildDraft($this->enrollment, 'Draft note', $this->policy);

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$draft->id}/revoke", [
                'reason' => 'Alasan pencabutan',
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_revoke_already_revoked_report_card(): void
    {
        $report1 = $this->createAndPublishReport();

        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revoke", [
                'reason' => 'Pencabutan pertama',
            ]);

        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revoke", [
                'reason' => 'Pencabutan kedua kali',
            ]);

        $response->assertForbidden();
    }

    public function test_non_homeroom_teacher_cannot_revoke_report(): void
    {
        $report1 = $this->createAndPublishReport();

        $response = $this->actingAs($this->otherTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revoke", [
                'reason' => 'Mencoba menarik tanpa otorisasi',
            ]);

        $response->assertForbidden();
    }

    public function test_revoked_report_is_completely_removed_from_guardian_portal(): void
    {
        $report1 = $this->createAndPublishReport();

        // Guardian can view published report
        $resGuardianBefore = $this->actingAs($this->guardian)
            ->get("/children/{$this->student->id}/report-cards");
        $resGuardianBefore->assertOk();

        // Revoke the report
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revoke", [
                'reason' => 'Penarikan karena kesalahan teknis perhitungan',
            ]);

        // Zero Leak: List shows 0 reports
        $resGuardianAfter = $this->actingAs($this->guardian)
            ->get("/children/{$this->student->id}/report-cards");
        $resGuardianAfter->assertOk();
        $resGuardianAfter->assertInertia(fn ($page) => $page
            ->component('Guardian/Reports/Index')
            ->has('reports', 0)
        );

        // Zero Leak: Show detail is 404
        $resShow = $this->actingAs($this->guardian)
            ->get("/children/{$this->student->id}/report-cards/{$report1->id}");
        $resShow->assertNotFound();

        // Zero Leak: PDF download is 404
        $resPdf = $this->actingAs($this->guardian)
            ->get("/children/{$this->student->id}/report-cards/{$report1->id}/pdf");
        $resPdf->assertNotFound();
    }

    public function test_publishing_new_revision_supersedes_needs_revision_report(): void
    {
        $report1 = $this->createAndPublishReport();

        // Create revision 2
        $resRev = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report1->id}/revisions");
        $draft2Id = $resRev->json('report.id');

        $draft2 = ReportCard::find($draft2Id);
        $snapshotService = app(ReportSnapshotService::class);
        $finalized2 = $snapshotService->finalizeReportCard($draft2, $this->homeroomTeacher);

        // Publish revision 2
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$finalized2->id}/publish");

        $report1->refresh();
        $finalized2->refresh();

        $this->assertEquals(ReportCardAvailability::SUPERSEDED, $report1->availability);
        $this->assertEquals(ReportCardAvailability::CURRENT, $finalized2->availability);
        $this->assertEquals(ReportCardStatus::PUBLISHED, $finalized2->status);

        // Guardian views report cards and sees revision 2
        $resGuardian = $this->actingAs($this->guardian)
            ->get("/children/{$this->student->id}/report-cards");
        $resGuardian->assertOk();
        $resGuardian->assertInertia(fn ($page) => $page
            ->component('Guardian/Reports/Index')
            ->has('reports', 1)
            ->where('reports.0.id', $finalized2->id)
            ->where('reports.0.revision', 2)
        );
    }
}
