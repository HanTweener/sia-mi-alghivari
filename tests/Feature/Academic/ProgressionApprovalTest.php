<?php

namespace Tests\Feature\Academic;

use App\Enums\ProgressionStatus;
use App\Enums\ProgressionType;
use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\GradingPolicy;
use App\Models\HomeroomAssignment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentProgression;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressionApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $principal;

    protected User $masterAdmin;

    protected User $academicAdmin;

    protected User $homeroomTeacher;

    protected AcademicYear $sourceYear;

    protected AcademicYear $targetYear;

    protected Term $sourceTerm;

    protected Term $targetTerm;

    protected Classroom $sourceClassroom;

    protected Classroom $targetClassroom;

    protected Student $student;

    protected Enrollment $enrollment;

    protected GradingPolicy $policy;

    protected ReportCard $publishedReport;

    protected StudentProgression $submittedProgression;

    protected function setUp(): void
    {
        parent::setUp();

        $this->principal = User::factory()->create(['name' => 'Kepala Madrasah']);
        $this->principal->assignRole(Role::PRINCIPAL);

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->academicAdmin = User::factory()->create(['name' => 'Admin Akademik']);
        $this->academicAdmin->assignRole(Role::ACADEMIC_ADMIN);

        $this->homeroomTeacher = User::factory()->create(['name' => 'Wali Kelas']);
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->sourceYear = AcademicYear::create([
            'name' => '2025/2026',
            'starts_on' => '2025-07-15',
            'ends_on' => '2026-06-20',
        ]);

        $this->targetYear = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);

        $this->sourceTerm = Term::create([
            'academic_year_id' => $this->sourceYear->id,
            'semester' => 2,
            'starts_on' => '2026-01-05',
            'ends_on' => '2026-06-20',
            'status' => 'active',
        ]);

        $this->targetTerm = Term::create([
            'academic_year_id' => $this->targetYear->id,
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'draft',
        ]);

        $this->sourceClassroom = Classroom::create([
            'term_id' => $this->sourceTerm->id,
            'grade_level' => 7,
            'section' => 'A',
        ]);

        $this->targetClassroom = Classroom::create([
            'term_id' => $this->targetTerm->id,
            'grade_level' => 8,
            'section' => 'A',
        ]);

        HomeroomAssignment::create([
            'classroom_id' => $this->sourceClassroom->id,
            'teacher_id' => $this->homeroomTeacher->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'nis' => '1001',
            'name' => 'Ahmad Santoso',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);

        $this->policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 75.00,
            'status' => 'active',
            'created_by' => $this->masterAdmin->id,
            'activated_by' => $this->masterAdmin->id,
            'activated_at' => now(),
        ]);

        $this->publishedReport = ReportCard::create([
            'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->sourceTerm->id,
            'grading_policy_id' => $this->policy->id,
            'status' => ReportCardStatus::PUBLISHED,
            'availability' => ReportCardAvailability::CURRENT,
            'source_version' => hash('sha256', 'source_v1'),
            'calculation_version' => '1.0',
            'version' => 1,
            'generated_at' => now(),
            'finalized_at' => now(),
            'published_at' => now(),
            'private_file_path' => 'reports/2026/sample.pdf',
        ]);

        $this->submittedProgression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::SUBMITTED,
            'version' => 2,
            'proposed_by' => $this->homeroomTeacher->id,
            'reason' => 'Tuntas seluruh mata pelajaran',
        ]);
    }

    public function test_only_principal_can_approve_progression(): void
    {
        // Master Admin without Principal role is forbidden (AC-09.1)
        $responseMaster = $this->actingAs($this->masterAdmin)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
            ]);
        $responseMaster->assertForbidden();

        // Academic Admin is forbidden
        $responseAdmin = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
            ]);
        $responseAdmin->assertForbidden();

        // Homeroom Teacher is forbidden
        $responseTeacher = $this->actingAs($this->homeroomTeacher)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
            ]);
        $responseTeacher->assertForbidden();

        // Principal succeeds
        $responsePrincipal = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
            ]);
        $responsePrincipal->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.version', 3);
    }

    public function test_decoupled_approval_does_not_mutate_student_or_enrollments(): void
    {
        // Prior to approval
        $this->assertSame('active', $this->student->fresh()->status);
        $this->assertSame(1, Enrollment::where('student_id', $this->student->id)->count());

        $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
            ])
            ->assertOk();

        // After approval (AC-09.1 Invariant):
        // 1. StudentProgression status is approved
        $freshProgression = $this->submittedProgression->fresh();
        $this->assertSame(ProgressionStatus::APPROVED, $freshProgression->status);
        $this->assertSame($this->principal->id, $freshProgression->approved_by);
        $this->assertNotNull($freshProgression->approved_at);

        // 2. Student status remains active, NO new enrollment created
        $this->assertSame('active', $this->student->fresh()->status);
        $this->assertSame(1, Enrollment::where('student_id', $this->student->id)->count());
        $this->assertDatabaseMissing('enrollments', [
            'classroom_id' => $this->targetClassroom->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_principal_can_approve_with_expected_source_report_and_version(): void
    {
        // Wrong version fails
        $responseWrongVersion = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 999,
            ]);
        $responseWrongVersion->assertStatus(422)
            ->assertJsonValidationErrors(['expected_version']);

        // Wrong expected_source_report_id fails
        $responseWrongReport = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
                'expected_source_report_id' => 99999,
            ]);
        $responseWrongReport->assertStatus(422)
            ->assertJsonValidationErrors(['expected_source_report_id']);

        // Wrong expected_source_report_revision fails
        $responseWrongRevision = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
                'expected_source_report_id' => $this->publishedReport->id,
                'expected_source_report_revision' => 2, // Report revision is 1
            ]);
        $responseWrongRevision->assertStatus(422)
            ->assertJsonValidationErrors(['expected_source_report_revision']);

        // Correct expectations succeed
        $responseSuccess = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
                'expected_source_report_id' => $this->publishedReport->id,
                'expected_source_report_revision' => 1,
            ]);
        $responseSuccess->assertOk()
            ->assertJsonPath('data.status', 'approved');

        // Proposer receives notification
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->homeroomTeacher->id,
            'event_category' => 'progression_approved',
        ]);
    }

    public function test_principal_can_reject_progression_with_mandatory_reason(): void
    {
        // Rejecting without reason fails
        $responseNoReason = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.reject', $this->submittedProgression), [
                'expected_version' => 2,
                'reason' => 'abc', // < 5 chars
            ]);
        $responseNoReason->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        // Rejecting with valid reason succeeds
        $responseSuccess = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.reject', $this->submittedProgression), [
                'expected_version' => 2,
                'reason' => 'Kehadiran belum mencapai batas minimal madrasah',
            ]);
        $responseSuccess->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Kehadiran belum mencapai batas minimal madrasah');

        $this->assertDatabaseHas('student_progressions', [
            'id' => $this->submittedProgression->id,
            'status' => 'rejected',
            'rejection_reason' => 'Kehadiran belum mencapai batas minimal madrasah',
        ]);

        // Proposer receives notification
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->homeroomTeacher->id,
            'event_category' => 'progression_rejected',
        ]);
    }

    public function test_batch_approve_all_or_nothing_atomicity(): void
    {
        // Create second student and progression
        $student2 = Student::create(['nis' => '1002', 'name' => 'Budi', 'gender' => 'L']);
        $enrollment2 = Enrollment::create([
            'student_id' => $student2->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);
        $report2 = ReportCard::create([
            'enrollment_id' => $enrollment2->id,
            'term_id' => $this->sourceTerm->id,
            'grading_policy_id' => $this->policy->id,
            'status' => ReportCardStatus::PUBLISHED,
            'availability' => ReportCardAvailability::CURRENT,
            'version' => 1,
        ]);
        $progression2 = StudentProgression::create([
            'student_id' => $student2->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $report2->id,
            'type' => ProgressionType::PROMOTION,
            'status' => ProgressionStatus::SUBMITTED,
            'version' => 1,
        ]);

        // 1. Partial failure: item 2 has wrong expected_version
        $responseFail = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.batch-approve'), [
                'items' => [
                    ['progression_id' => $this->submittedProgression->id, 'expected_version' => 2],
                    ['progression_id' => $progression2->id, 'expected_version' => 999],
                ],
            ]);

        $responseFail->assertStatus(422);

        // Neither progression is approved (Atomicity verified)
        $this->assertSame(ProgressionStatus::SUBMITTED, $this->submittedProgression->fresh()->status);
        $this->assertSame(ProgressionStatus::SUBMITTED, $progression2->fresh()->status);

        // 2. All valid items succeed atomically
        $responseSuccess = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.batch-approve'), [
                'items' => [
                    ['progression_id' => $this->submittedProgression->id, 'expected_version' => 2],
                    ['progression_id' => $progression2->id, 'expected_version' => 1],
                ],
            ]);

        $responseSuccess->assertOk()
            ->assertJsonPath('approved_count', 2);

        $this->assertSame(ProgressionStatus::APPROVED, $this->submittedProgression->fresh()->status);
        $this->assertSame(ProgressionStatus::APPROVED, $progression2->fresh()->status);
    }

    public function test_batch_reject_all_or_nothing_atomicity(): void
    {
        $student2 = Student::create(['nis' => '1003', 'name' => 'Citra', 'gender' => 'P']);
        $enrollment2 = Enrollment::create([
            'student_id' => $student2->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);
        $report2 = ReportCard::create([
            'enrollment_id' => $enrollment2->id,
            'term_id' => $this->sourceTerm->id,
            'grading_policy_id' => $this->policy->id,
            'status' => ReportCardStatus::PUBLISHED,
            'availability' => ReportCardAvailability::CURRENT,
            'version' => 1,
        ]);
        $progression2 = StudentProgression::create([
            'student_id' => $student2->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $report2->id,
            'type' => ProgressionType::PROMOTION,
            'status' => ProgressionStatus::SUBMITTED,
            'version' => 1,
        ]);

        $responseSuccess = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.batch-reject'), [
                'reason' => 'Perlu penyesuaian kuota rombel',
                'items' => [
                    ['progression_id' => $this->submittedProgression->id, 'expected_version' => 2],
                    ['progression_id' => $progression2->id, 'expected_version' => 1],
                ],
            ]);

        $responseSuccess->assertOk()
            ->assertJsonPath('rejected_count', 2);

        $this->assertSame(ProgressionStatus::REJECTED, $this->submittedProgression->fresh()->status);
        $this->assertSame(ProgressionStatus::REJECTED, $progression2->fresh()->status);
    }

    public function test_source_change_or_revocation_invalidates_progression_to_needs_review(): void
    {
        // Approve the progression first
        $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.approve', $this->submittedProgression), [
                'expected_version' => 2,
            ])
            ->assertOk();

        $this->assertSame(ProgressionStatus::APPROVED, $this->submittedProgression->fresh()->status);

        // Staff revokes the published report card
        $this->actingAs($this->homeroomTeacher)
            ->postJson(route('academic.report-cards.revoke', $this->publishedReport), [
                'reason' => 'Kekeliruan input nilai akhir semester',
                'expected_version' => 1,
            ])
            ->assertOk();

        // The approved progression referencing this report card is automatically transitioned to needs_review!
        $freshProgression = $this->submittedProgression->fresh();
        $this->assertSame(ProgressionStatus::NEEDS_REVIEW, $freshProgression->status);

        // Principal receives notification that progression needs review
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->principal->id,
            'event_category' => 'progression_needs_review',
        ]);
    }
}
