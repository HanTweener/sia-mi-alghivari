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

class ProgressionApplyTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $academicAdmin;

    protected User $principal;

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

    protected StudentProgression $approvedProgression;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->academicAdmin = User::factory()->create(['name' => 'Admin Akademik']);
        $this->academicAdmin->assignRole(Role::ACADEMIC_ADMIN);

        $this->principal = User::factory()->create(['name' => 'Kepala Madrasah']);
        $this->principal->assignRole(Role::PRINCIPAL);

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

        $this->approvedProgression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::APPROVED,
            'version' => 3,
            'proposed_by' => $this->homeroomTeacher->id,
            'approved_by' => $this->principal->id,
            'approved_at' => now(),
            'reason' => 'Disetujui untuk naik kelas',
        ]);
    }

    public function test_authorized_admin_can_apply_approved_promotion(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $this->approvedProgression), [
                'expected_version' => 3,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'applied')
            ->assertJsonPath('data.version', 4);

        // Verify StudentProgression record
        $this->assertDatabaseHas('student_progressions', [
            'id' => $this->approvedProgression->id,
            'status' => 'applied',
            'applied_by' => $this->academicAdmin->id,
            'version' => 4,
        ]);

        // Verify Historical enrollment is preserved (AC-09.2)
        $this->assertDatabaseHas('enrollments', [
            'id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
        ]);

        // Verify New enrollment is created in target classroom
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'classroom_id' => $this->targetClassroom->id,
            'term_id' => $this->targetTerm->id,
            'status' => 'active',
        ]);

        // Total enrollments for student is now 2
        $this->assertSame(2, Enrollment::where('student_id', $this->student->id)->count());

        // Student status remains active
        $this->assertSame('active', $this->student->fresh()->status);

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PROGRESSION_APPLIED',
            'scope' => 'progression',
        ]);
    }

    public function test_authorized_admin_can_apply_approved_retention(): void
    {
        $retentionClassroom = Classroom::create([
            'term_id' => $this->targetTerm->id,
            'grade_level' => 7, // same grade level
            'section' => 'B',
        ]);

        $studentRetention = Student::create([
            'nis' => '1002',
            'name' => 'Siswa Tinggal',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $enrollmentRetention = Enrollment::create([
            'student_id' => $studentRetention->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);

        $reportRetention = ReportCard::create([
            'enrollment_id' => $enrollmentRetention->id,
            'term_id' => $this->sourceTerm->id,
            'grading_policy_id' => $this->policy->id,
            'status' => ReportCardStatus::PUBLISHED,
            'availability' => ReportCardAvailability::CURRENT,
            'version' => 1,
        ]);

        $retentionProgression = StudentProgression::create([
            'student_id' => $studentRetention->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $reportRetention->id,
            'type' => ProgressionType::RETENTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $retentionClassroom->id,
            'status' => ProgressionStatus::APPROVED,
            'version' => 2,
            'proposed_by' => $this->homeroomTeacher->id,
            'approved_by' => $this->principal->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->masterAdmin)
            ->postJson(route('academic.progressions.apply', $retentionProgression), [
                'expected_version' => 2,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'applied');

        // New enrollment in retention classroom
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $studentRetention->id,
            'classroom_id' => $retentionClassroom->id,
            'term_id' => $this->targetTerm->id,
            'status' => 'active',
        ]);

        $this->assertSame('active', $studentRetention->fresh()->status);
    }

    public function test_authorized_admin_can_apply_approved_graduation(): void
    {
        $studentGrad = Student::create([
            'nis' => '1003',
            'name' => 'Siswa Lulus',
            'gender' => 'P',
            'status' => 'active',
        ]);

        $enrollmentGrad = Enrollment::create([
            'student_id' => $studentGrad->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);

        $reportGrad = ReportCard::create([
            'enrollment_id' => $enrollmentGrad->id,
            'term_id' => $this->sourceTerm->id,
            'grading_policy_id' => $this->policy->id,
            'status' => ReportCardStatus::PUBLISHED,
            'availability' => ReportCardAvailability::CURRENT,
            'version' => 1,
        ]);

        $gradProgression = StudentProgression::create([
            'student_id' => $studentGrad->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $reportGrad->id,
            'type' => ProgressionType::GRADUATION,
            'status' => ProgressionStatus::APPROVED,
            'version' => 2,
            'proposed_by' => $this->homeroomTeacher->id,
            'approved_by' => $this->principal->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $gradProgression), [
                'expected_version' => 2,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'applied');

        // Student status updated to graduated
        $this->assertSame('graduated', $studentGrad->fresh()->status);

        // No new enrollment created
        $this->assertSame(1, Enrollment::where('student_id', $studentGrad->id)->count());
    }

    public function test_only_administrative_staff_can_apply_progressions(): void
    {
        // Principal is forbidden from applying (AC-09.2)
        $responsePrincipal = $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.apply', $this->approvedProgression), [
                'expected_version' => 3,
            ]);
        $responsePrincipal->assertForbidden();

        // Homeroom teacher is forbidden
        $responseTeacher = $this->actingAs($this->homeroomTeacher)
            ->postJson(route('academic.progressions.apply', $this->approvedProgression), [
                'expected_version' => 3,
            ]);
        $responseTeacher->assertForbidden();
    }

    public function test_cannot_apply_unapproved_progression(): void
    {
        $studentDraft = Student::create([
            'nis' => '1004',
            'name' => 'Siswa Draf',
            'gender' => 'L',
        ]);

        $enrollmentDraft = Enrollment::create([
            'student_id' => $studentDraft->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);

        $reportDraft = ReportCard::create([
            'enrollment_id' => $enrollmentDraft->id,
            'term_id' => $this->sourceTerm->id,
            'grading_policy_id' => $this->policy->id,
            'status' => ReportCardStatus::PUBLISHED,
            'availability' => ReportCardAvailability::CURRENT,
            'version' => 1,
        ]);

        $draftProgression = StudentProgression::create([
            'student_id' => $studentDraft->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $reportDraft->id,
            'type' => ProgressionType::PROMOTION,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $draftProgression), [
                'expected_version' => 1,
            ]);

        $response->assertForbidden();
    }

    public function test_idempotency_guard_prevents_duplicate_apply(): void
    {
        // First apply succeeds
        $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $this->approvedProgression), [
                'expected_version' => 3,
            ])
            ->assertOk();

        // Second apply is rejected (apply-once)
        $responseDuplicate = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $this->approvedProgression), [
                'expected_version' => 4,
            ]);

        $responseDuplicate->assertForbidden();
    }

    public function test_source_data_changed_or_revoked_blocks_apply_and_flags_needs_review(): void
    {
        // Source report is revoked before apply
        $this->publishedReport->update([
            'status' => ReportCardStatus::REVOKED,
            'availability' => ReportCardAvailability::SUPERSEDED,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $this->approvedProgression), [
                'expected_version' => 3,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.source.0', 'Rapor sumber telah berubah atau dicabut. Status usulan ditandai needs_review dan penerapan dibatalkan.');

        // Status is automatically transitioned to needs_review
        $this->assertSame(ProgressionStatus::NEEDS_REVIEW, $this->approvedProgression->fresh()->status);
    }

    public function test_batch_apply_all_or_nothing_atomicity(): void
    {
        $student2 = Student::create(['nis' => '1005', 'name' => 'Budi', 'gender' => 'L']);
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
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::APPROVED,
            'version' => 1,
        ]);

        // 1. Partial failure: item 2 has wrong expected_version
        $responseFail = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.batch-apply'), [
                'items' => [
                    ['progression_id' => $this->approvedProgression->id, 'expected_version' => 3],
                    ['progression_id' => $progression2->id, 'expected_version' => 999],
                ],
            ]);

        $responseFail->assertStatus(422);

        // Neither progression is applied
        $this->assertSame(ProgressionStatus::APPROVED, $this->approvedProgression->fresh()->status);
        $this->assertSame(ProgressionStatus::APPROVED, $progression2->fresh()->status);
        $this->assertDatabaseMissing('enrollments', [
            'classroom_id' => $this->targetClassroom->id,
            'student_id' => $this->student->id,
        ]);

        // 2. All valid items succeed atomically
        $responseSuccess = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.batch-apply'), [
                'items' => [
                    ['progression_id' => $this->approvedProgression->id, 'expected_version' => 3],
                    ['progression_id' => $progression2->id, 'expected_version' => 1],
                ],
            ]);

        $responseSuccess->assertOk()
            ->assertJsonPath('applied_count', 2);

        $this->assertSame(ProgressionStatus::APPLIED, $this->approvedProgression->fresh()->status);
        $this->assertSame(ProgressionStatus::APPLIED, $progression2->fresh()->status);
        $this->assertSame(2, Enrollment::where('classroom_id', $this->targetClassroom->id)->count());
    }

    public function test_applied_progression_is_strictly_terminal_and_cannot_be_deleted_modified_or_undone(): void
    {
        // 1. Apply progression first
        $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $this->approvedProgression), [
                'expected_version' => 3,
            ])
            ->assertOk();

        $progression = $this->approvedProgression->fresh();
        $this->assertSame(ProgressionStatus::APPLIED, $progression->status);
        $this->assertTrue($progression->status->isTerminal());
        $this->assertFalse($progression->status->canBeEdited());

        // 2. Cannot be deleted by anyone (including Master Admin)
        $deleteResponse = $this->actingAs($this->masterAdmin)
            ->deleteJson(route('academic.progressions.destroy', $progression), [
                'expected_version' => $progression->version,
            ]);
        $deleteResponse->assertForbidden();

        // 3. Cannot be modified/updated
        $updateResponse = $this->actingAs($this->masterAdmin)
            ->patchJson(route('academic.progressions.update', $progression), [
                'expected_version' => $progression->version,
                'type' => 'retention',
            ]);
        $updateResponse->assertForbidden();

        // 4. Cannot be resubmitted or reapproved
        $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.submit', $progression))
            ->assertForbidden();

        $this->actingAs($this->principal)
            ->postJson(route('academic.progressions.approve', $progression), [
                'expected_version' => $progression->version,
            ])
            ->assertForbidden();

        // 5. Cannot be re-applied
        $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $progression), [
                'expected_version' => $progression->version,
            ])
            ->assertForbidden();

        // 6. Record persists unchanged in database
        $this->assertDatabaseHas('student_progressions', [
            'id' => $progression->id,
            'status' => 'applied',
        ]);
    }

    public function test_administrative_correction_uses_classroom_transfer_preserving_applied_progression_history(): void
    {
        // 1. Apply progression: student is promoted to Class 8-A
        $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.apply', $this->approvedProgression), [
                'expected_version' => 3,
            ])
            ->assertOk();

        $targetEnrollment = Enrollment::where('student_id', $this->student->id)
            ->where('classroom_id', $this->targetClassroom->id)
            ->firstOrFail();

        // 2. An administrative misplacement is discovered: student should actually be in Class 8-B
        $correctedClassroom = Classroom::create([
            'term_id' => $this->targetTerm->id,
            'grade_level' => 8,
            'section' => 'B',
        ]);

        // 3. Perform administrative classroom transfer (Feature 1-A) in the new term
        $transferResponse = $this->actingAs($this->academicAdmin)
            ->post(route('academic.enrollments.transfer', $targetEnrollment), [
                'target_classroom_id' => $correctedClassroom->id,
                'transfer_date' => '2026-07-20',
                'reason' => 'Koreksi penempatan rombel siswa tahun ajaran baru',
            ]);

        $transferResponse->assertSessionHasNoErrors();
        $transferResponse->assertRedirect();

        // 4. Historical StudentProgression record remains strictly intact and applied
        $this->assertDatabaseHas('student_progressions', [
            'id' => $this->approvedProgression->id,
            'status' => 'applied',
            'target_classroom_id' => $this->targetClassroom->id,
        ]);

        // 5. Old source enrollment remains intact as historical record
        $this->assertDatabaseHas('enrollments', [
            'id' => $this->enrollment->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
        ]);

        // 6. Previous target enrollment is closed with ends_on
        $targetEnrollment->refresh();
        $this->assertSame('transferred', $targetEnrollment->status);
        $this->assertSame('2026-07-20', $targetEnrollment->ends_on->format('Y-m-d'));

        // 7. New corrected enrollment is active in Classroom 8-B
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'classroom_id' => $correctedClassroom->id,
            'term_id' => $this->targetTerm->id,
            'status' => 'active',
            'starts_on' => '2026-07-20 00:00:00',
        ]);
    }
}
