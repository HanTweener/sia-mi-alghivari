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
use App\Models\Notification;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentProgression;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressionProposalTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $academicAdmin;

    protected User $homeroomTeacher;

    protected User $otherTeacher;

    protected User $principal;

    protected User $guardian;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->academicAdmin = User::factory()->create(['name' => 'Admin Akademik']);
        $this->academicAdmin->assignRole(Role::ACADEMIC_ADMIN);

        $this->homeroomTeacher = User::factory()->create(['name' => 'Wali Kelas 7A']);
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $this->otherTeacher->assignRole(Role::TEACHER);

        $this->principal = User::factory()->create(['name' => 'Kepala Madrasah']);
        $this->principal->assignRole(Role::PRINCIPAL);

        $this->guardian = User::factory()->create(['name' => 'Orang Tua']);
        $this->guardian->assignRole(Role::GUARDIAN);

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

        // Assign homeroom teacher to source classroom
        HomeroomAssignment::create([
            'classroom_id' => $this->sourceClassroom->id,
            'teacher_id' => $this->homeroomTeacher->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'nis' => '1001',
            'name' => 'Budi Santoso',
            'gender' => 'L',
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
    }

    public function test_authorized_staff_can_view_progressions_list(): void
    {
        StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
        ]);

        // Academic Admin can view
        $response = $this->actingAs($this->academicAdmin)
            ->getJson(route('academic.progressions.index'));
        $response->assertOk()
            ->assertJsonPath('total', 1);

        // Homeroom teacher can view
        $responseTeacher = $this->actingAs($this->homeroomTeacher)
            ->getJson(route('academic.progressions.index'));
        $responseTeacher->assertOk();

        // Principal can view
        $responsePrincipal = $this->actingAs($this->principal)
            ->getJson(route('academic.progressions.index'));
        $responsePrincipal->assertOk();

        // Guardian is forbidden
        $responseGuardian = $this->actingAs($this->guardian)
            ->getJson(route('academic.progressions.index'));
        $responseGuardian->assertForbidden();

        // Teacher without homeroom is forbidden
        $responseOther = $this->actingAs($this->otherTeacher)
            ->getJson(route('academic.progressions.index'));
        $responseOther->assertForbidden();
    }

    public function test_staff_can_create_individual_progression_draft_with_published_report(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.store'), [
                'student_id' => $this->student->id,
                'source_academic_year_id' => $this->sourceYear->id,
                'type' => 'promotion',
                'target_academic_year_id' => $this->targetYear->id,
                'target_classroom_id' => $this->targetClassroom->id,
                'reason' => 'Prestasi belajar tuntas',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.student_id', $this->student->id)
            ->assertJsonPath('data.source_report_card_id', $this->publishedReport->id);

        $this->assertDatabaseHas('student_progressions', [
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => 'promotion',
            'status' => 'draft',
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PROGRESSION_CREATED',
            'scope' => 'progression',
        ]);
    }

    public function test_cannot_create_progression_if_student_lacks_published_current_report(): void
    {
        $studentNoReport = Student::create([
            'nis' => '1002',
            'name' => 'Siswa Belum Ada Rapor',
            'gender' => 'P',
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.store'), [
                'student_id' => $studentNoReport->id,
                'source_academic_year_id' => $this->sourceYear->id,
                'type' => 'promotion',
                'target_academic_year_id' => $this->targetYear->id,
                'target_classroom_id' => $this->targetClassroom->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_report_card_id']);
    }

    public function test_cannot_create_duplicate_progression_for_same_student_and_source_academic_year(): void
    {
        StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.store'), [
                'student_id' => $this->student->id,
                'source_academic_year_id' => $this->sourceYear->id,
                'type' => 'retention',
                'target_academic_year_id' => $this->targetYear->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id']);
    }

    public function test_staff_can_batch_create_progressions_for_classroom(): void
    {
        // Add second student to same classroom with published report
        $student2 = Student::create([
            'nis' => '1003',
            'name' => 'Siti Aisyah',
            'gender' => 'P',
        ]);

        $enrollment2 = Enrollment::create([
            'student_id' => $student2->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->sourceTerm->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);

        ReportCard::create([
            'enrollment_id' => $enrollment2->id,
            'term_id' => $this->sourceTerm->id,
            'grading_policy_id' => $this->policy->id,
            'status' => ReportCardStatus::PUBLISHED,
            'availability' => ReportCardAvailability::CURRENT,
            'source_version' => hash('sha256', 'source_v2'),
            'calculation_version' => '1.0',
            'version' => 1,
            'generated_at' => now(),
            'finalized_at' => now(),
            'published_at' => now(),
            'private_file_path' => 'reports/2026/sample2.pdf',
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.store'), [
                'classroom_id' => $this->sourceClassroom->id,
                'type' => 'promotion',
                'target_academic_year_id' => $this->targetYear->id,
                'target_classroom_id' => $this->targetClassroom->id,
                'reason' => 'Usulan rombel 7A',
            ]);

        $response->assertCreated()
            ->assertJsonPath('created_count', 2)
            ->assertJsonPath('skipped_count', 0);

        $this->assertDatabaseCount('student_progressions', 2);
    }

    public function test_staff_can_view_progression_detail(): void
    {
        $progression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->getJson(route('academic.progressions.show', $progression));

        $response->assertOk()
            ->assertJsonPath('data.id', $progression->id)
            ->assertJsonPath('data.student.name', $this->student->name)
            ->assertJsonPath('data.source_report_card.id', $this->publishedReport->id);
    }

    public function test_staff_can_update_draft_progression_with_optimistic_locking(): void
    {
        $progression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
            'reason' => 'Catatan awal',
        ]);

        // Attempting update with wrong expected_version must fail
        $responseStale = $this->actingAs($this->academicAdmin)
            ->patchJson(route('academic.progressions.update', $progression), [
                'expected_version' => 999,
                'type' => 'promotion',
                'target_academic_year_id' => $this->targetYear->id,
                'target_classroom_id' => $this->targetClassroom->id,
                'reason' => 'Catatan revisi',
            ]);

        $responseStale->assertStatus(422)
            ->assertJsonValidationErrors(['expected_version']);

        // Update with correct expected_version succeeds
        $responseSuccess = $this->actingAs($this->academicAdmin)
            ->patchJson(route('academic.progressions.update', $progression), [
                'expected_version' => 1,
                'type' => 'retention',
                'target_academic_year_id' => $this->targetYear->id,
                'reason' => 'Perlu pendampingan belajar',
            ]);

        $responseSuccess->assertOk()
            ->assertJsonPath('data.type', 'retention')
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.reason', 'Perlu pendampingan belajar');

        $this->assertDatabaseHas('student_progressions', [
            'id' => $progression->id,
            'type' => 'retention',
            'version' => 2,
        ]);
    }

    public function test_cannot_update_progression_when_not_in_editable_status(): void
    {
        $progression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::APPROVED, // Approved cannot be updated
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->patchJson(route('academic.progressions.update', $progression), [
                'expected_version' => 1,
                'type' => 'retention',
            ]);

        $response->assertForbidden();
    }

    public function test_staff_can_delete_draft_progression(): void
    {
        $progression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->deleteJson(route('academic.progressions.destroy', $progression), [
                'expected_version' => 1,
            ]);

        $response->assertOk();
        $this->assertDatabaseMissing('student_progressions', ['id' => $progression->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PROGRESSION_DELETED',
            'scope' => 'progression',
        ]);
    }

    public function test_staff_can_submit_draft_progression_for_principal_review(): void
    {
        $progression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.submit', $progression), [
                'expected_version' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('student_progressions', [
            'id' => $progression->id,
            'status' => 'submitted',
            'version' => 2,
        ]);

        // Principal receives notification
        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $this->principal->id,
            'event_category' => 'progression_submitted',
        ]);
    }

    public function test_submit_blocked_if_source_report_card_is_not_current_or_revoked(): void
    {
        $progression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->publishedReport->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
            'proposed_by' => $this->academicAdmin->id,
        ]);

        // Report card is revoked or marked needs_revision
        $this->publishedReport->update([
            'status' => ReportCardStatus::REVOKED,
            'availability' => ReportCardAvailability::SUPERSEDED,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->postJson(route('academic.progressions.submit', $progression), [
                'expected_version' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.source.0', 'Rapor sumber telah berubah, dicabut, atau belum berstatus published saat diajukan.');
    }
}
