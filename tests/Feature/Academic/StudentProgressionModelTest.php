<?php

namespace Tests\Feature\Academic;

use App\Enums\AuditScope;
use App\Enums\ProgressionStatus;
use App\Enums\ProgressionType;
use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\GradingPolicy;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentProgression;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProgressionModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $proposer;

    protected User $principal;

    protected User $applier;

    protected AcademicYear $sourceYear;

    protected AcademicYear $targetYear;

    protected Term $term;

    protected Classroom $sourceClassroom;

    protected Classroom $targetClassroom;

    protected Student $student;

    protected Enrollment $enrollment;

    protected ReportCard $reportCard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->proposer = User::factory()->create(['name' => 'Wali Kelas']);
        $this->proposer->assignRole(Role::TEACHER);

        $this->principal = User::factory()->create(['name' => 'Kepala Madrasah']);
        $this->principal->assignRole(Role::PRINCIPAL);

        $this->applier = User::factory()->create(['name' => 'Admin Akademik']);
        $this->applier->assignRole(Role::ACADEMIC_ADMIN);

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

        $this->term = Term::create([
            'academic_year_id' => $this->sourceYear->id,
            'semester' => 2,
            'starts_on' => '2026-01-05',
            'ends_on' => '2026-06-20',
            'status' => 'active',
        ]);

        $this->sourceClassroom = Classroom::create([
            'term_id' => $this->term->id,
            'grade_level' => 7,
            'section' => 'A',
        ]);

        $targetTerm = Term::create([
            'academic_year_id' => $this->targetYear->id,
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'draft',
        ]);

        $this->targetClassroom = Classroom::create([
            'term_id' => $targetTerm->id,
            'grade_level' => 8,
            'section' => 'A',
        ]);

        $this->student = Student::create([
            'nis' => '12345',
            'name' => 'Ahmad Santoso',
            'gender' => 'L',
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'classroom_id' => $this->sourceClassroom->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-01-05',
            'status' => 'active',
        ]);

        $policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 75.00,
            'status' => 'active',
            'created_by' => $this->masterAdmin->id,
            'activated_by' => $this->masterAdmin->id,
            'activated_at' => now(),
        ]);

        $this->reportCard = ReportCard::create([
            'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->term->id,
            'grading_policy_id' => $policy->id,
            'status' => ReportCardStatus::PUBLISHED,
            'availability' => ReportCardAvailability::CURRENT,
            'source_version' => hash('sha256', 'sample_source'),
            'calculation_version' => '1.0',
            'version' => 1,
            'generated_at' => now(),
            'finalized_at' => now(),
            'published_at' => now(),
            'private_file_path' => 'reports/2026/sample.pdf',
        ]);
    }

    public function test_can_create_student_progression_with_attributes_and_enum_casts(): void
    {
        $progression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->reportCard->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
            'proposed_by' => $this->proposer->id,
            'reason' => 'Memenuhi KKM seluruh mapel dan kehadiran > 90%',
        ]);

        $this->assertDatabaseHas('student_progressions', [
            'id' => $progression->id,
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->reportCard->id,
            'type' => 'promotion',
            'status' => 'draft',
            'version' => 1,
        ]);

        $this->assertInstanceOf(ProgressionType::class, $progression->type);
        $this->assertSame(ProgressionType::PROMOTION, $progression->type);
        $this->assertSame('Naik Kelas', $progression->type->label());

        $this->assertInstanceOf(ProgressionStatus::class, $progression->status);
        $this->assertSame(ProgressionStatus::DRAFT, $progression->status);
        $this->assertSame('Draf Usulan', $progression->status->label());
        $this->assertFalse($progression->status->isTerminal());
        $this->assertTrue($progression->status->canBeEdited());

        $this->assertSame(1, $progression->version);
    }

    public function test_verifies_all_relationships_and_inverse_associations(): void
    {
        $progression = StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->reportCard->id,
            'type' => ProgressionType::PROMOTION,
            'target_academic_year_id' => $this->targetYear->id,
            'target_classroom_id' => $this->targetClassroom->id,
            'status' => ProgressionStatus::APPROVED,
            'version' => 2,
            'proposed_by' => $this->proposer->id,
            'approved_by' => $this->principal->id,
            'approved_at' => now(),
            'applied_by' => $this->applier->id,
            'applied_at' => now(),
            'reason' => 'Prestasi memuaskan',
        ]);

        // Direct BelongsTo relationships
        $this->assertSame($this->student->id, $progression->student->id);
        $this->assertSame($this->sourceYear->id, $progression->sourceAcademicYear->id);
        $this->assertSame($this->reportCard->id, $progression->sourceReportCard->id);
        $this->assertSame($this->targetYear->id, $progression->targetAcademicYear->id);
        $this->assertSame($this->targetClassroom->id, $progression->targetClassroom->id);
        $this->assertSame($this->proposer->id, $progression->proposedBy->id);
        $this->assertSame($this->principal->id, $progression->approvedBy->id);
        $this->assertSame($this->applier->id, $progression->appliedBy->id);

        // Inverse HasMany relationships
        $this->assertTrue($this->student->progressions->contains($progression));
        $this->assertTrue($this->reportCard->progressions->contains($progression));
        $this->assertTrue($this->sourceYear->sourceProgressions->contains($progression));
        $this->assertTrue($this->targetYear->targetProgressions->contains($progression));
    }

    public function test_verifies_status_and_type_helper_methods(): void
    {
        $progression = new StudentProgression([
            'status' => ProgressionStatus::DRAFT,
            'type' => ProgressionType::PROMOTION,
        ]);

        $this->assertTrue($progression->isDraft());
        $this->assertFalse($progression->isSubmitted());
        $this->assertFalse($progression->isApproved());
        $this->assertFalse($progression->isRejected());
        $this->assertFalse($progression->isApplied());
        $this->assertFalse($progression->isCancelled());
        $this->assertFalse($progression->isNeedsReview());
        $this->assertTrue($progression->isPromotion());
        $this->assertFalse($progression->isGraduation());
        $this->assertFalse($progression->isRetention());

        // Test Graduation
        $progression->type = ProgressionType::GRADUATION;
        $this->assertTrue($progression->isGraduation());
        $this->assertSame('Lulus', $progression->type->label());

        // Test Retention
        $progression->type = ProgressionType::RETENTION;
        $this->assertTrue($progression->isRetention());
        $this->assertSame('Tinggal Kelas', $progression->type->label());

        // Test Submitted
        $progression->status = ProgressionStatus::SUBMITTED;
        $this->assertTrue($progression->isSubmitted());

        // Test Approved
        $progression->status = ProgressionStatus::APPROVED;
        $this->assertTrue($progression->isApproved());

        // Test Rejected
        $progression->status = ProgressionStatus::REJECTED;
        $this->assertTrue($progression->isRejected());
        $this->assertTrue($progression->status->isTerminal());

        // Test Applied
        $progression->status = ProgressionStatus::APPLIED;
        $this->assertTrue($progression->isApplied());
        $this->assertTrue($progression->status->isTerminal());

        // Test Cancelled
        $progression->status = ProgressionStatus::CANCELLED;
        $this->assertTrue($progression->isCancelled());
        $this->assertTrue($progression->status->isTerminal());

        // Test Needs Review
        $progression->status = ProgressionStatus::NEEDS_REVIEW;
        $this->assertTrue($progression->isNeedsReview());
        $this->assertTrue($progression->status->canBeEdited());
    }

    public function test_enforces_unique_constraint_on_student_and_source_academic_year(): void
    {
        StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->reportCard->id,
            'type' => ProgressionType::PROMOTION,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
        ]);

        $this->expectException(QueryException::class);

        // Attempting to create duplicate for same student and source_academic_year must throw
        StudentProgression::create([
            'student_id' => $this->student->id,
            'source_academic_year_id' => $this->sourceYear->id,
            'source_report_card_id' => $this->reportCard->id,
            'type' => ProgressionType::RETENTION,
            'status' => ProgressionStatus::DRAFT,
            'version' => 1,
        ]);
    }

    public function test_audit_scope_progression_persists_in_audit_log(): void
    {
        $this->assertSame('progression', AuditScope::PROGRESSION->value);

        $log = AuditLog::create([
            'actor_user_id' => $this->principal->id,
            'action' => 'progression_approved',
            'entity_type' => StudentProgression::class,
            'entity_id' => '100',
            'scope' => AuditScope::PROGRESSION,
            'allowed_changes' => ['status' => ['before' => 'submitted', 'after' => 'approved']],
            'reason' => 'Disetujui oleh Kepala Madrasah',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'scope' => 'progression',
            'action' => 'progression_approved',
        ]);

        $freshLog = $log->fresh();
        $this->assertInstanceOf(AuditScope::class, $freshLog->scope);
        $this->assertSame(AuditScope::PROGRESSION, $freshLog->scope);
    }
}
