<?php

namespace Tests\Feature\Academic;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReportCardLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $homeroomTeacher;

    protected User $otherTeacher;

    protected User $guardian;

    protected User $principal;

    protected AcademicYear $academicYear;

    protected Term $term;

    protected Classroom $classroom;

    protected Student $studentComplete;

    protected Student $studentIncomplete;

    protected Enrollment $enrollmentComplete;

    protected Enrollment $enrollmentIncomplete;

    protected Subject $mathSubject;

    protected ClassSubject $mathClassSubject;

    protected GradingPolicy $policy;

    protected ScoringComponent $assignmentComp;

    protected ScoringComponent $dailyTestComp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAdmin = User::factory()->create();
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->homeroomTeacher = User::factory()->create(['name' => 'Wali Kelas 7A']);
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $this->otherTeacher->assignRole(Role::TEACHER);

        $this->guardian = User::factory()->create();
        $this->guardian->assignRole(Role::GUARDIAN);

        $this->principal = User::factory()->create(['name' => 'Drs. H. Mulyadi, M.Pd']);
        $this->principal->assignRole(Role::PRINCIPAL);

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

        $this->studentComplete = Student::create([
            'nis' => '2001',
            'name' => 'Zaid bin Tsabit',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->studentIncomplete = Student::create([
            'nis' => '2002',
            'name' => 'Umar bin Khattab',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->enrollmentComplete = Enrollment::create([
            'student_id' => $this->studentComplete->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);

        $this->enrollmentIncomplete = Enrollment::create([
            'student_id' => $this->studentIncomplete->id,
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

        // Policy: Tugas (50%), Ulangan Harian (50%), KKM 75.00
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

        // Populate scores: studentComplete has both Tugas (80) & UH (90); studentIncomplete only has Tugas (85)
        $a1 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->otherTeacher->id,
            'scoring_component_id' => $this->assignmentComp->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => 'published',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $a1->id,
            'enrollment_id' => $this->enrollmentComplete->id,
            'score' => 80.00,
            'graded_by' => $this->otherTeacher->id,
        ]);
        Score::create([
            'assessment_id' => $a1->id,
            'enrollment_id' => $this->enrollmentIncomplete->id,
            'score' => 85.00,
            'graded_by' => $this->otherTeacher->id,
        ]);

        $a2 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->otherTeacher->id,
            'scoring_component_id' => $this->dailyTestComp->id,
            'title' => 'UH 1',
            'assessed_on' => '2026-08-15',
            'status' => 'published',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $a2->id,
            'enrollment_id' => $this->enrollmentComplete->id,
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
            'enrollment_id' => $this->enrollmentComplete->id,
            'status' => 'present',
            'recorded_by' => $this->otherTeacher->id,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $sess->id,
            'enrollment_id' => $this->enrollmentIncomplete->id,
            'status' => 'present',
            'recorded_by' => $this->otherTeacher->id,
        ]);
    }

    public function test_create_draft_report_for_complete_student(): void
    {
        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('report_cards', [
            'enrollment_id' => $this->enrollmentComplete->id,
            'status' => 'draft',
            'revision' => 1,
            'version' => 1,
        ]);

        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->first();
        $this->assertEquals(1, $report->items()->count());
        $this->assertEquals('85.00', $report->items()->first()->final_score);
    }

    public function test_create_draft_fails_for_incomplete_student(): void
    {
        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentIncomplete->id,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('report_cards', [
            'enrollment_id' => $this->enrollmentIncomplete->id,
        ]);
    }

    public function test_batch_create_drafts_skips_incomplete_students(): void
    {
        $response = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", []);

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(1, $data['created_count']);
        $this->assertEquals(1, $data['skipped_count']);
        $this->assertContains('Umar bin Khattab', $data['skipped_students']);
    }

    public function test_non_homeroom_teacher_cannot_create_drafts(): void
    {
        $response = $this->actingAs($this->otherTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ]);

        $response->assertForbidden();
    }

    public function test_view_report_card_detail(): void
    {
        // First create draft
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ]);

        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->first();

        // Homeroom teacher can view
        $res = $this->actingAs($this->homeroomTeacher)
            ->getJson("/academic/report-cards/{$report->id}");
        $res->assertOk();
        $this->assertEquals('draft', $res->json('report.status'));

        // Non-homeroom teacher is forbidden
        $resOther = $this->actingAs($this->otherTeacher)
            ->getJson("/academic/report-cards/{$report->id}");
        $resOther->assertForbidden();
    }

    public function test_update_homeroom_note_with_optimistic_locking(): void
    {
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ]);

        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->first();
        $this->assertEquals(1, $report->version);

        // Matching expected_version succeeds
        $res = $this->actingAs($this->homeroomTeacher)
            ->patchJson("/academic/report-cards/{$report->id}", [
                'homeroom_note' => 'Ananda sangat tekun dan rajin belajar.',
                'expected_version' => 1,
            ]);

        $res->assertOk();
        $report->refresh();
        $this->assertEquals('Ananda sangat tekun dan rajin belajar.', $report->homeroom_note);
        $this->assertEquals(2, $report->version);

        // Stale expected_version fails with 409 Conflict
        $staleRes = $this->actingAs($this->homeroomTeacher)
            ->patchJson("/academic/report-cards/{$report->id}", [
                'homeroom_note' => 'Catatan baru.',
                'expected_version' => 1, // Stale!
            ]);

        $staleRes->assertStatus(409);
    }

    public function test_academic_admin_cannot_write_homeroom_note_without_homeroom_grant(): void
    {
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ])->assertOk();
        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->firstOrFail();
        $academic = User::factory()->create();
        $academic->assignRole(Role::ACADEMIC_ADMIN);
        $payload = ['homeroom_note' => 'Catatan tanpa izin', 'expected_version' => $report->version];

        $this->actingAs($academic)->getJson("/academic/report-cards/{$report->id}")->assertOk();
        $this->patchJson("/academic/report-cards/{$report->id}", $payload)->assertForbidden();
        $this->assertNull($report->fresh()->homeroom_note);

        $this->actingAs($this->masterAdmin)->patchJson("/academic/report-cards/{$report->id}", $payload)->assertOk();
        $this->assertSame('Catatan tanpa izin', $report->fresh()->homeroom_note);

        $academic->assignRole(Role::TEACHER);
        HomeroomAssignment::where('classroom_id', $this->classroom->id)->update(['teacher_id' => $academic->id]);
        $this->actingAs($academic)->patchJson("/academic/report-cards/{$report->id}", [
            'homeroom_note' => 'Catatan wali kelas', 'expected_version' => $report->fresh()->version,
        ])->assertOk();
    }

    public function test_guardian_cannot_access_staff_report_projection_even_for_own_published_report(): void
    {
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ])->assertOk();
        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->firstOrFail();
        $report->update(['status' => ReportCardStatus::PUBLISHED, 'published_at' => now()]);
        $this->studentComplete->guardians()->attach($this->guardian, ['relationship' => 'ayah', 'is_primary' => true]);

        $this->actingAs($this->guardian)->getJson("/academic/report-cards/{$report->id}")->assertForbidden();
        $this->get(route('children.report-cards.show', [$this->studentComplete, $report]))->assertOk();

        $this->guardian->assignRole(Role::TEACHER);
        $this->getJson("/academic/report-cards/{$report->id}")->assertForbidden();
        $this->get(route('children.report-cards.show', [$this->studentComplete, $report]))->assertOk();

        $this->guardian->assignRole(Role::ACADEMIC_ADMIN);
        $this->getJson("/academic/report-cards/{$report->id}")->assertOk();
    }

    public function test_report_controls_match_principal_and_homeroom_permissions(): void
    {
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ])->assertOk();
        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->firstOrFail();
        $this->getJson("/academic/report-cards/{$report->id}")->assertOk()
            ->assertJsonPath('canManageReport', true)->assertJsonPath('canUpdateNote', true);
        $principal = User::factory()->create();
        $principal->assignRole(Role::PRINCIPAL);
        $this->actingAs($principal)->getJson("/academic/report-cards/{$report->id}")->assertOk()
            ->assertJsonPath('canManageReport', false)->assertJsonPath('canUpdateNote', false)
            ->assertJsonPath('canDownloadPdf', false)->assertJsonPath('canPrepareFile', false);
    }

    public function test_homeroom_report_list_excludes_unrelated_historical_enrollments(): void
    {
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ])->assertOk();
        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->firstOrFail();
        $report->update(['status' => ReportCardStatus::PUBLISHED, 'published_at' => now()]);
        $otherClassroom = Classroom::create(['term_id' => $this->term->id, 'grade_level' => 7, 'section' => 'B']);
        $oldEnrollment = Enrollment::create([
            'student_id' => $this->studentComplete->id, 'classroom_id' => $otherClassroom->id,
            'term_id' => $this->term->id, 'starts_on' => '2026-06-01', 'ends_on' => '2026-07-01', 'status' => 'transferred',
        ]);
        $otherReport = $report->replicate();
        $otherReport->enrollment_id = $oldEnrollment->id;
        $otherReport->save();

        $this->get(route('children.report-cards.index', $this->studentComplete))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reports', 1)->where('reports.0.id', $report->id));
        $this->get(route('children.report-cards.show', [$this->studentComplete, $otherReport]))->assertForbidden();

        $this->homeroomTeacher->assignRole(Role::GUARDIAN);
        $this->studentComplete->guardians()->attach($this->homeroomTeacher, ['relationship' => 'ayah', 'is_primary' => true]);
        $this->get(route('children.report-cards.index', $this->studentComplete))
            ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->has('reports', 2));
    }

    public function test_finalize_draft_report_card_freezes_snapshot_immutably(): void
    {
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ]);

        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->first();

        $res = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report->id}/finalize", [
                'expected_version' => 1,
                'expected_source_version' => $report->source_version,
            ]);

        $res->assertOk();
        $report->refresh();

        $this->assertEquals(ReportCardStatus::FINALIZED, $report->status);
        $this->assertNotNull($report->finalized_at);
        $this->assertEquals($this->homeroomTeacher->id, $report->finalized_by);
        $this->assertNotNull($report->identity_snapshot);
        $this->assertEquals('Drs. H. Mulyadi, M.Pd', $report->identity_snapshot['principal']['name']);
        $this->assertEquals('Zaid bin Tsabit', $report->identity_snapshot['student']['name']);

        // Subsequent homeroom note edit on finalized report is rejected by policy (403 forbidden)
        $editRes = $this->actingAs($this->homeroomTeacher)
            ->patchJson("/academic/report-cards/{$report->id}", [
                'homeroom_note' => 'Edit dilarang.',
                'expected_version' => 2,
            ]);
        $editRes->assertForbidden();

        // Subsequent finalize on already finalized report is rejected by policy (403 forbidden)
        $refinalizeRes = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report->id}/finalize", [
                'expected_version' => 2,
            ]);
        $refinalizeRes->assertForbidden();
    }

    public function test_finalize_rejects_when_source_data_has_changed(): void
    {
        $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/classrooms/{$this->classroom->id}/report-cards", [
                'enrollment_id' => $this->enrollmentComplete->id,
            ]);

        $report = ReportCard::where('enrollment_id', $this->enrollmentComplete->id)->first();
        $originalSourceVersion = $report->source_version;

        // Teacher updates the score in background after draft was created
        $score = Score::where('enrollment_id', $this->enrollmentComplete->id)
            ->whereHas('assessment', fn ($q) => $q->where('scoring_component_id', $this->dailyTestComp->id))
            ->first();
        $score->update(['score' => 95.00]);

        // Attempting to finalize with original expected_source_version or stored stale source_version
        $res = $this->actingAs($this->homeroomTeacher)
            ->postJson("/academic/report-cards/{$report->id}/finalize", [
                'expected_version' => 1,
                'expected_source_version' => $originalSourceVersion,
            ]);

        // Must reject with 409 and source_changed error key
        $res->assertStatus(409);
        $this->assertArrayHasKey('source_changed', $res->json('errors'));

        $report->refresh();
        $this->assertEquals(ReportCardStatus::DRAFT, $report->status);
    }
}
