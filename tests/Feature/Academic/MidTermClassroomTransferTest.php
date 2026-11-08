<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
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
use Tests\TestCase;

class MidTermClassroomTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $masterAdmin;

    private User $academicAdmin;

    private User $teacher;

    private User $guardian;

    private Term $term;

    private Classroom $classA;

    private Classroom $classB;

    private Student $student;

    private Enrollment $enrollmentA;

    private Subject $math;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAdmin = User::factory()->create();
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->academicAdmin = User::factory()->create();
        $this->academicAdmin->assignRole(Role::ACADEMIC_ADMIN);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(Role::TEACHER);

        $this->guardian = User::factory()->create();
        $this->guardian->assignRole(Role::GUARDIAN);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);

        $this->term = Term::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'active',
        ]);

        $this->classA = Classroom::create([
            'term_id' => $this->term->id,
            'grade_level' => 3,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $this->classB = Classroom::create([
            'term_id' => $this->term->id,
            'grade_level' => 3,
            'section' => 'B',
            'roster_version' => 1,
        ]);

        $this->student = Student::create([
            'nis' => '1001',
            'name' => 'Ahmad Fulan',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->enrollmentA = Enrollment::create([
            'student_id' => $this->student->id,
            'classroom_id' => $this->classA->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-07-15',
            'ends_on' => null,
            'status' => 'active',
        ]);

        $this->math = Subject::create([
            'name' => 'Matematika',
            'code' => 'MTK',
            'status' => 'active',
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_transfer_student_mid_term(): void
    {
        $response = $this->actingAs($this->masterAdmin)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $this->classB->id,
                'transfer_date' => '2026-10-01',
                'reason' => 'Penyesuaian kuota rombel',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Source enrollment updated
        $this->enrollmentA->refresh();
        $this->assertEquals('transferred', $this->enrollmentA->status);
        $this->assertEquals('2026-10-01', $this->enrollmentA->ends_on->toDateString());
        $this->assertEquals('Penyesuaian kuota rombel', $this->enrollmentA->transfer_reason);

        // New target enrollment created
        $newEnrollment = Enrollment::where('classroom_id', $this->classB->id)
            ->where('student_id', $this->student->id)
            ->first();

        $this->assertNotNull($newEnrollment);
        $this->assertEquals('active', $newEnrollment->status);
        $this->assertEquals('2026-10-01', $newEnrollment->starts_on->toDateString());
        $this->assertNull($newEnrollment->ends_on);
        $this->assertEquals($this->enrollmentA->id, $newEnrollment->transferred_from_enrollment_id);
        $this->assertEquals('Penyesuaian kuota rombel', $newEnrollment->transfer_reason);

        // Roster versions incremented on both classrooms
        $this->assertEquals(2, $this->classA->fresh()->roster_version);
        $this->assertEquals(2, $this->classB->fresh()->roster_version);

        // Audit log created
        $audit = AuditLog::where('action', 'TRANSFER_STUDENT')
            ->where('entity_id', $newEnrollment->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals($this->masterAdmin->id, $audit->actor_user_id);
        $this->assertEquals($this->classA->id, $audit->allowed_changes['source_classroom_id']);
        $this->assertEquals($this->classB->id, $audit->allowed_changes['target_classroom_id']);
    }

    public function test_unauthorized_users_cannot_transfer_students(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $this->classB->id,
                'transfer_date' => '2026-10-01',
            ])
            ->assertForbidden();

        $this->actingAs($this->guardian)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $this->classB->id,
                'transfer_date' => '2026-10-01',
            ])
            ->assertForbidden();
    }

    public function test_cannot_transfer_to_same_classroom(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $this->classA->id,
                'transfer_date' => '2026-10-01',
            ]);

        $response->assertSessionHasErrors('target_classroom_id');
    }

    public function test_cannot_transfer_to_different_term(): void
    {
        $term2 = Term::create([
            'academic_year_id' => $this->term->academic_year_id,
            'semester' => 2,
            'starts_on' => '2027-01-05',
            'ends_on' => '2027-06-20',
            'status' => 'inactive',
        ]);

        $otherClass = Classroom::create([
            'term_id' => $term2->id,
            'grade_level' => 3,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $response = $this->actingAs($this->academicAdmin)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $otherClass->id,
                'transfer_date' => '2026-10-01',
            ]);

        $response->assertSessionHasErrors('target_classroom_id');
    }

    public function test_cannot_transfer_earlier_than_source_starts_on(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $this->classB->id,
                'transfer_date' => '2026-07-01', // earlier than starts_on 2026-07-15
            ]);

        $response->assertSessionHasErrors('transfer_date');
    }

    public function test_cannot_transfer_non_active_enrollment(): void
    {
        $this->enrollmentA->update(['status' => 'withdrawn']);

        $response = $this->actingAs($this->academicAdmin)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $this->classB->id,
                'transfer_date' => '2026-10-01',
            ]);

        $response->assertSessionHasErrors('enrollment');
    }

    public function test_attendance_eligibility_respects_cutover_date_d27(): void
    {
        // Before transfer on 2026-10-01:
        // Student is eligible in Class A on 2026-08-15
        $eligibleInABefore = $this->classA->enrollments()
            ->eligibleOn('2026-08-15')
            ->pluck('student_id')
            ->all();
        $this->assertContains($this->student->id, $eligibleInABefore);

        // Student is NOT eligible in Class B on 2026-08-15
        $eligibleInBBefore = $this->classB->enrollments()
            ->eligibleOn('2026-08-15')
            ->pluck('student_id')
            ->all();
        $this->assertNotContains($this->student->id, $eligibleInBBefore);

        // Perform transfer on 2026-10-01
        $this->actingAs($this->masterAdmin)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $this->classB->id,
                'transfer_date' => '2026-10-01',
            ])
            ->assertSessionHasNoErrors();

        // After transfer date (e.g. 2026-10-15):
        // Student is no longer eligible in Class A
        $eligibleInAAfter = $this->classA->enrollments()
            ->eligibleOn('2026-10-15')
            ->pluck('student_id')
            ->all();
        $this->assertNotContains($this->student->id, $eligibleInAAfter);

        // Student IS eligible in Class B
        $eligibleInBAfter = $this->classB->enrollments()
            ->eligibleOn('2026-10-15')
            ->pluck('student_id')
            ->all();
        $this->assertContains($this->student->id, $eligibleInBAfter);

        // Historical query: student remains eligible in Class A for the period before transfer!
        $eligibleInAHistorical = $this->classA->enrollments()
            ->eligibleOn('2026-08-15')
            ->pluck('student_id')
            ->all();
        $this->assertContains($this->student->id, $eligibleInAHistorical);
    }

    public function test_report_card_snapshot_combines_scores_and_attendance_from_transferred_enrollment(): void
    {
        // 1. Setup ClassSubjects for Math in Class A and Class B
        $csA = ClassSubject::create([
            'classroom_id' => $this->classA->id,
            'subject_id' => $this->math->id,
        ]);
        $csB = ClassSubject::create([
            'classroom_id' => $this->classB->id,
            'subject_id' => $this->math->id,
        ]);

        $taA = TeachingAssignment::create([
            'class_subject_id' => $csA->id,
            'teacher_id' => $this->teacher->id,
            'starts_on' => '2026-07-15',
            'status' => 'assigned',
        ]);
        $taB = TeachingAssignment::create([
            'class_subject_id' => $csB->id,
            'teacher_id' => $this->teacher->id,
            'starts_on' => '2026-07-15',
            'status' => 'assigned',
        ]);

        HomeroomAssignment::create([
            'classroom_id' => $this->classB->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);

        // 2. Setup GradingPolicy with Tugas (50%) and UAS (50%)
        $compTugas = ScoringComponent::create([
            'code' => 'tugas',
            'name' => 'Tugas',
            'source_type' => 'assignment',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $compUas = ScoringComponent::create([
            'code' => 'uas',
            'name' => 'UAS',
            'source_type' => 'exam',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $policy = GradingPolicy::create([
            'created_by' => $this->masterAdmin->id,
            'name' => 'Kebijakan Standar',
            'kkm' => '70.00',
            'revision' => 1,
            'status' => 'active',
        ]);
        GradingPolicyComponent::create([
            'grading_policy_id' => $policy->id,
            'scoring_component_id' => $compTugas->id,
            'weight_percentage' => '50.00',
        ]);
        GradingPolicyComponent::create([
            'grading_policy_id' => $policy->id,
            'scoring_component_id' => $compUas->id,
            'weight_percentage' => '50.00',
        ]);

        // 3. In Class A (before transfer):
        // Session 1 in Class A on 2026-08-10 (present)
        $sessionA = AttendanceSession::create([
            'class_subject_id' => $csA->id,
            'teaching_assignment_id' => $taA->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-10',
            'status' => AttendanceSession::STATUS_FINALIZED,
            'finalized_at' => now(),
            'version' => 1,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $sessionA->id,
            'enrollment_id' => $this->enrollmentA->id,
            'status' => AttendanceRecord::STATUS_PRESENT,
            'recorded_by' => $this->teacher->id,
        ]);

        // Assessment Tugas in Class A with score 80
        $assessA = Assessment::create([
            'class_subject_id' => $csA->id,
            'teaching_assignment_id' => $taA->id,
            'scoring_component_id' => $compTugas->id,
            'created_by' => $this->teacher->id,
            'title' => 'Tugas 1 Aljabar',
            'assessed_on' => '2026-08-15',
            'status' => Assessment::STATUS_PUBLISHED,
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $assessA->id,
            'enrollment_id' => $this->enrollmentA->id,
            'score' => '80.00',
            'graded_by' => $this->teacher->id,
        ]);

        // 4. Perform Transfer on 2026-10-01
        $this->actingAs($this->masterAdmin)
            ->post(route('academic.enrollments.transfer', $this->enrollmentA->id), [
                'target_classroom_id' => $this->classB->id,
                'transfer_date' => '2026-10-01',
                'reason' => 'Pindah rombel',
            ])
            ->assertSessionHasNoErrors();

        $newEnrollment = Enrollment::where('classroom_id', $this->classB->id)
            ->where('student_id', $this->student->id)
            ->first();

        // 5. In Class B (after transfer):
        // Session 2 in Class B on 2026-11-10 (present)
        $sessionB = AttendanceSession::create([
            'class_subject_id' => $csB->id,
            'teaching_assignment_id' => $taB->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-11-10',
            'status' => AttendanceSession::STATUS_FINALIZED,
            'finalized_at' => now(),
            'version' => 1,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $sessionB->id,
            'enrollment_id' => $newEnrollment->id,
            'status' => AttendanceRecord::STATUS_PRESENT,
            'recorded_by' => $this->teacher->id,
        ]);

        // Assessment UAS in Class B with score 90
        $assessB = Assessment::create([
            'class_subject_id' => $csB->id,
            'teaching_assignment_id' => $taB->id,
            'scoring_component_id' => $compUas->id,
            'created_by' => $this->teacher->id,
            'title' => 'UAS Semester 1',
            'assessed_on' => '2026-11-20',
            'status' => Assessment::STATUS_PUBLISHED,
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $assessB->id,
            'enrollment_id' => $newEnrollment->id,
            'score' => '90.00',
            'graded_by' => $this->teacher->id,
        ]);

        // 6. Calculate snapshot for new enrollment in Class B
        /** @var ReportSnapshotService $reportService */
        $reportService = app(ReportSnapshotService::class);
        $snapshot = $reportService->calculateSnapshot($newEnrollment, $policy);

        // Assert completeness: Both Tugas (from Class A) and UAS (from Class B) are combined!
        $this->assertTrue($snapshot['is_complete']);
        $this->assertTrue($snapshot['can_finalize']);
        $this->assertEmpty($snapshot['blocked_reasons']);

        // Final score: (80 * 0.5) + (90 * 0.5) = 40 + 45 = 85.00
        $this->assertEquals('85.00', $snapshot['items'][0]['final_score']);
        $this->assertEquals('Mencapai KKM', $snapshot['items'][0]['kkm_status']);

        // Combined attendance: 2 sessions, 2 present -> 100%
        $this->assertEquals(2, $snapshot['attendance_summary']['total_sessions']);
        $this->assertEquals(2, $snapshot['attendance_summary']['present']);
        $this->assertEquals('100.00', (string) $snapshot['attendance_summary']['rate']);

        // Transfer info snapshot present
        $this->assertNotNull($snapshot['transfer_info']);
        $this->assertTrue($snapshot['transfer_info']['is_transferred']);
        $this->assertEquals('2026-10-01', $snapshot['transfer_info']['transfer_date']);

        // 7. Verify Draft & Finalize ReportCard
        $draft = $reportService->buildDraft($newEnrollment, 'Catatan wali murid', $policy, true);
        $this->assertInstanceOf(ReportCard::class, $draft);

        $finalized = $reportService->finalizeReportCard($draft, $this->academicAdmin);
        $this->assertEquals('finalized', $finalized->status->value);
        $this->assertNotNull($finalized->identity_snapshot['transfer']);
        $this->assertEquals('2026-10-01', $finalized->identity_snapshot['transfer']['transfer_date']);
    }
}
