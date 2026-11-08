<?php

namespace Tests\Feature\Reports;

use App\Enums\AuditScope;
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
use App\Models\ReportCardItem;
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

class ReportSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected AcademicYear $academicYear;

    protected Term $term;

    protected Classroom $classroom;

    protected Student $student;

    protected Enrollment $enrollment;

    protected Subject $mathSubject;

    protected ClassSubject $mathClassSubject;

    protected GradingPolicy $policy;

    protected ReportSnapshotService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReportSnapshotService::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::MASTER_ADMIN);

        $this->teacher = User::factory()->create(['name' => 'Guru Matematika']);
        $this->teacher->assignRole(Role::TEACHER);

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
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);

        $this->student = Student::create([
            'nis' => '123456',
            'name' => 'Ahmad Fulan',
            'gender' => 'L',
            'status' => 'active',
        ]);

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
            'teacher_id' => $this->teacher->id,
            'starts_on' => '2026-07-01',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Setup active GradingPolicy: Tugas (30%), Ulangan Harian (30%), UTS (20%), UAS (20%)
        $this->policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 75.00,
            'status' => 'active',
            'created_by' => $this->admin->id,
            'activated_by' => $this->admin->id,
            'activated_at' => now(),
        ]);

        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();
        $dailyTestComp = ScoringComponent::where('code', 'daily_test')->first();
        $midtermComp = ScoringComponent::where('code', 'midterm')->first();
        $finalComp = ScoringComponent::where('code', 'final')->first();
        $attendanceComp = ScoringComponent::where('code', 'attendance')->first();

        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $assignmentComp->id,
            'weight_percentage' => 30.00,
        ]);
        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $dailyTestComp->id,
            'weight_percentage' => 30.00,
        ]);
        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $midtermComp->id,
            'weight_percentage' => 20.00,
        ]);
        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $finalComp->id,
            'weight_percentage' => 20.00,
        ]);
        // Attendance with 0% weight in this policy
        GradingPolicyComponent::create([
            'grading_policy_id' => $this->policy->id,
            'scoring_component_id' => $attendanceComp->id,
            'weight_percentage' => 0.00,
        ]);
    }

    public function test_audit_scope_report_enum_exists(): void
    {
        $this->assertEquals('report', AuditScope::REPORT->value);
    }

    public function test_snapshot_calculates_correct_kkm_and_final_score_when_complete(): void
    {
        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();
        $dailyTestComp = ScoringComponent::where('code', 'daily_test')->first();
        $midtermComp = ScoringComponent::where('code', 'midterm')->first();
        $finalComp = ScoringComponent::where('code', 'final')->first();

        // 1. Assignment published (Score 80.00) -> 30% * 80 = 24.00
        $a1 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'scoring_component_id' => $assignmentComp->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => 'published',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $a1->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 80.00,
            'graded_by' => $this->teacher->id,
        ]);

        // 2. Daily Test published (Score 90.00) -> 30% * 90 = 27.00
        $a2 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'scoring_component_id' => $dailyTestComp->id,
            'title' => 'UH 1',
            'assessed_on' => '2026-08-15',
            'status' => 'published',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $a2->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 90.00,
            'graded_by' => $this->teacher->id,
        ]);

        // 3. Midterm published (Score 70.00) -> 20% * 70 = 14.00
        $a3 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'scoring_component_id' => $midtermComp->id,
            'title' => 'UTS',
            'assessed_on' => '2026-09-20',
            'status' => 'published',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $a3->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 70.00,
            'graded_by' => $this->teacher->id,
        ]);

        // 4. Final published (Score 80.00) -> 20% * 80 = 16.00
        $a4 = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'scoring_component_id' => $finalComp->id,
            'title' => 'UAS',
            'assessed_on' => '2026-11-25',
            'status' => 'published',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $a4->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 80.00,
            'graded_by' => $this->teacher->id,
        ]);

        // 5. Finalized Attendance session
        $sess = AttendanceSession::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'finalized',
            'version' => 1,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $sess->id,
            'enrollment_id' => $this->enrollment->id,
            'status' => 'present',
            'recorded_by' => $this->teacher->id,
        ]);

        // Total calculated = 24.00 + 27.00 + 14.00 + 16.00 = 81.00
        // KKM is 75.00 -> 81.00 >= 75.00 -> 'Mencapai KKM'
        $snapshot = $this->service->calculateSnapshot($this->enrollment);

        $this->assertTrue($snapshot['is_complete']);
        $this->assertTrue($snapshot['can_finalize']);
        $this->assertEmpty($snapshot['blocked_reasons']);
        $this->assertNotEmpty($snapshot['source_version']);
        $this->assertEquals(1, count($snapshot['items']));

        $item = $snapshot['items'][0];
        $this->assertEquals('81.00', $item['final_score']);
        $this->assertEquals('75.00', $item['kkm_snapshot']);
        $this->assertEquals('Mencapai KKM', $item['kkm_status']);
        $this->assertEquals(1, $item['attendance']['present']);
        $this->assertEquals(1, $item['attendance']['total_sessions']);
        $this->assertEquals('100.00', $item['attendance']['rate']);
    }

    public function test_snapshot_ignores_draft_assessments_and_unfinalized_attendance(): void
    {
        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();

        // Draft assessment should NOT be counted in report snapshot
        $draftAssessment = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'scoring_component_id' => $assignmentComp->id,
            'title' => 'Tugas Draft',
            'assessed_on' => '2026-08-01',
            'status' => 'draft',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $draftAssessment->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 95.00,
            'graded_by' => $this->teacher->id,
        ]);

        // Draft attendance session should NOT be counted in report snapshot
        $draftSess = AttendanceSession::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'meeting_number' => 1,
            'held_on' => '2026-08-01',
            'status' => 'draft',
            'version' => 1,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $draftSess->id,
            'enrollment_id' => $this->enrollment->id,
            'status' => 'present',
            'recorded_by' => $this->teacher->id,
        ]);

        $snapshot = $this->service->calculateSnapshot($this->enrollment);

        // Since draft assessment is ignored, 'assignment' component has no scores and blocks completion
        $this->assertFalse($snapshot['is_complete']);
        $this->assertFalse($snapshot['can_finalize']);
        $this->assertNull($snapshot['items'][0]['final_score']);
        $this->assertEquals('Belum tersedia', $snapshot['items'][0]['kkm_status']);
        $this->assertContains('assignment', $snapshot['items'][0]['missing_components']);
        $this->assertEquals(0, $snapshot['items'][0]['attendance']['total_sessions']);
    }

    public function test_source_version_is_deterministic_and_changes_when_scores_change(): void
    {
        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();

        $a = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'scoring_component_id' => $assignmentComp->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => 'published',
            'version' => 1,
        ]);
        $score = Score::create([
            'assessment_id' => $a->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 80.00,
            'graded_by' => $this->teacher->id,
        ]);

        $snapshot1 = $this->service->calculateSnapshot($this->enrollment);
        $snapshot2 = $this->service->calculateSnapshot($this->enrollment);

        // Same database state produces identical source_version
        $this->assertEquals($snapshot1['source_version'], $snapshot2['source_version']);

        // Mutate score: updated_at and score change
        $score->update(['score' => 85.00]);

        $snapshot3 = $this->service->calculateSnapshot($this->enrollment);
        $this->assertNotEquals($snapshot1['source_version'], $snapshot3['source_version']);
    }

    public function test_build_draft_creates_and_updates_draft_report_card(): void
    {
        $assignmentComp = ScoringComponent::where('code', 'assignment')->first();
        $a = Assessment::create([
            'class_subject_id' => $this->mathClassSubject->id,
            'created_by' => $this->teacher->id,
            'scoring_component_id' => $assignmentComp->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => 'published',
            'version' => 1,
        ]);
        Score::create([
            'assessment_id' => $a->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 85.00,
            'graded_by' => $this->teacher->id,
        ]);

        $draft = $this->service->buildDraft($this->enrollment, 'Catatan awal wali kelas');

        $this->assertInstanceOf(ReportCard::class, $draft);
        $this->assertEquals(ReportCardStatus::DRAFT, $draft->status);
        $this->assertEquals(ReportCardAvailability::CURRENT, $draft->availability);
        $this->assertEquals(1, $draft->revision);
        $this->assertEquals('Catatan awal wali kelas', $draft->homeroom_note);
        $this->assertEquals(1, $draft->items()->count());

        $item = $draft->items()->first();
        $this->assertInstanceOf(ReportCardItem::class, $item);
        $this->assertEquals($this->mathClassSubject->id, $item->class_subject_id);
        $this->assertEquals('Matematika', $item->subject_snapshot['name']);
        $this->assertNotEmpty($item->teaching_history_snapshot);
        $this->assertEquals('Guru Matematika', $item->teaching_history_snapshot[0]['teacher_name']);

        // Updating draft should update existing record without creating a new revision
        $updatedDraft = $this->service->buildDraft($this->enrollment, 'Catatan baru');
        $this->assertEquals($draft->id, $updatedDraft->id);
        $this->assertEquals(1, $updatedDraft->revision);
        $this->assertEquals('Catatan baru', $updatedDraft->homeroom_note);
        $this->assertEquals(1, ReportCard::where('enrollment_id', $this->enrollment->id)->count());
    }
}
