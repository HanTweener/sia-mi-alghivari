<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\DataEntryDelegation;
use App\Models\Enrollment;
use App\Models\GradingPolicy;
use App\Models\Score;
use App\Models\ScoringComponent;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\Grading\GradingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $otherTeacher;

    protected User $dataEntry;

    protected Classroom $classroom;

    protected ClassSubject $classSubject;

    protected TeachingAssignment $assignment;

    protected ScoringComponent $componentTugas;

    protected ScoringComponent $componentUH;

    protected ScoringComponent $componentKehadiran;

    protected Student $student1;

    protected Student $student2;

    protected Enrollment $enrollment1;

    protected Enrollment $enrollment2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Master Admin']);
        $this->admin->assignRole(Role::MASTER_ADMIN);

        $this->teacher = User::factory()->create(['name' => 'Guru Pengampu PAI']);
        $this->teacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create(['name' => 'Guru Lain']);
        $this->otherTeacher->assignRole(Role::TEACHER);

        $this->dataEntry = User::factory()->create(['name' => 'Petugas Data Entry']);
        $this->dataEntry->assignRole(Role::DATA_ENTRY_ADMIN);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'status' => 'active',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-12-31',
        ]);

        $this->classroom = Classroom::create([
            'term_id' => $term->id,
            'grade_level' => 1,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $subject = Subject::create([
            'code' => 'PAI',
            'name' => 'Pendidikan Agama Islam',
            'category' => 'PAI',
            'is_active' => true,
        ]);

        $this->classSubject = ClassSubject::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $subject->id,
        ]);

        $this->assignment = TeachingAssignment::create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
            'starts_on' => '2026-07-01',
            'status' => 'assigned',
            'assigned_by' => $this->admin->id,
        ]);

        // Scoring components
        $this->componentTugas = ScoringComponent::create([
            'code' => 'TUGAS',
            'name' => 'Tugas Mandiri & Terstruktur',
            'source_type' => 'assignment',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->componentUH = ScoringComponent::create([
            'code' => 'UH',
            'name' => 'Ulangan Harian',
            'source_type' => 'exam',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->componentKehadiran = ScoringComponent::create([
            'code' => 'HADIR',
            'name' => 'Kehadiran Tatap Muka',
            'source_type' => 'attendance',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Students & Enrollments
        $this->student1 = Student::create(['nis' => '1001', 'name' => 'Ahmad', 'gender' => 'L', 'status' => 'active']);
        $this->student2 = Student::create(['nis' => '1002', 'name' => 'Budi', 'gender' => 'L', 'status' => 'active']);

        $this->enrollment1 = Enrollment::create([
            'student_id' => $this->student1->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);

        $this->enrollment2 = Enrollment::create([
            'student_id' => $this->student2->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $term->id,
            'starts_on' => '2026-07-01',
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_view_assessments_and_create_draft(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('academic.class-subjects.assessments.index', $this->classSubject->id));
        $response->assertOk();

        $createResponse = $this->post(route('academic.class-subjects.assessments.store', $this->classSubject->id), [
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'Ulangan Harian 1',
            'assessed_on' => '2026-08-15',
        ]);

        $this->assertDatabaseHas('assessments', [
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'Ulangan Harian 1',
            'assessed_on' => '2026-08-15',
            'status' => Assessment::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->teacher->id,
        ]);

        $assessment = Assessment::where('title', 'Ulangan Harian 1')->first();
        $createResponse->assertRedirect(route('academic.assessments.show', $assessment->id));
    }

    public function test_other_teacher_cannot_access_or_manage_assessments(): void
    {
        $this->actingAs($this->otherTeacher);

        $indexResponse = $this->get(route('academic.class-subjects.assessments.index', $this->classSubject->id));
        $indexResponse->assertForbidden();

        $storeResponse = $this->post(route('academic.class-subjects.assessments.store', $this->classSubject->id), [
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'Illegal Assessment',
            'assessed_on' => '2026-08-15',
        ]);
        $storeResponse->assertForbidden();
    }

    public function test_store_assessment_validates_attendance_component(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->post(route('academic.class-subjects.assessments.store', $this->classSubject->id), [
            'scoring_component_id' => $this->componentKehadiran->id,
            'title' => 'Penilaian Kehadiran Manual',
            'assessed_on' => '2026-08-15',
        ]);

        $response->assertSessionHasErrors('scoring_component_id');
        $this->assertDatabaseMissing('assessments', [
            'title' => 'Penilaian Kehadiran Manual',
        ]);
    }

    public function test_save_scores_with_valid_range_and_decimals(): void
    {
        $this->actingAs($this->teacher);

        $assessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'Ulangan Harian 1',
            'assessed_on' => '2026-08-15',
            'status' => Assessment::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->teacher->id,
        ]);

        $response = $this->patch(route('academic.assessments.scores.update', $assessment->id), [
            'expected_version' => 1,
            'expected_roster_version' => $this->classroom->roster_version,
            'entries' => [
                [
                    'enrollment_id' => $this->enrollment1->id,
                    'score' => '85.50',
                    'feedback' => 'Sangat baik dalam penguasaan materi',
                ],
                [
                    'enrollment_id' => $this->enrollment2->id,
                    'score' => '90',
                    'feedback' => null,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('scores', [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '85.50',
            'feedback' => 'Sangat baik dalam penguasaan materi',
        ]);

        $this->assertDatabaseHas('scores', [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment2->id,
            'score' => '90.00',
        ]);

        $this->assertEquals(2, $assessment->fresh()->version);
    }

    public function test_blank_vs_zero_distinction(): void
    {
        $this->actingAs($this->teacher);

        $assessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'Kuis Tajwid',
            'assessed_on' => '2026-08-20',
            'status' => Assessment::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->teacher->id,
        ]);

        // Ahmad gets 0 (valid zero score), Budi is blank (absent/not taken yet)
        $this->patch(route('academic.assessments.scores.update', $assessment->id), [
            'expected_version' => 1,
            'entries' => [
                [
                    'enrollment_id' => $this->enrollment1->id,
                    'score' => '0',
                    'feedback' => 'Belum menjawab dengan benar',
                ],
                [
                    'enrollment_id' => $this->enrollment2->id,
                    'score' => null,
                    'feedback' => null,
                ],
            ],
        ]);

        // Ahmad should have a numeric score row with 0.00
        $this->assertDatabaseHas('scores', [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '0.00',
        ]);

        // Budi should NOT have any row in scores table (blank)
        $this->assertDatabaseMissing('scores', [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment2->id,
        ]);

        // Now teacher clears Ahmad's score (sets to blank)
        $this->patch(route('academic.assessments.scores.update', $assessment->id), [
            'expected_version' => 2,
            'entries' => [
                [
                    'enrollment_id' => $this->enrollment1->id,
                    'score' => null,
                    'feedback' => null,
                ],
            ],
        ]);

        // Ahmad's score row should now be deleted
        $this->assertDatabaseMissing('scores', [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment1->id,
        ]);
    }

    public function test_data_entry_with_active_delegation_can_draft_scores_but_cannot_publish_or_delete(): void
    {
        // Grant active delegation for grade_entry
        DataEntryDelegation::create([
            'user_id' => $this->dataEntry->id,
            'teaching_assignment_id' => $this->assignment->id,
            'capability' => 'grade_entry',
            'valid_from' => now()->subDays(5)->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'granted_by' => $this->admin->id,
            'reason' => 'Bantuan input nilai tengah semester',
        ]);

        $this->actingAs($this->dataEntry);

        // Can view index
        $indexResponse = $this->get(route('academic.class-subjects.assessments.index', $this->classSubject->id));
        $indexResponse->assertOk();

        // Can create draft
        $createResponse = $this->post(route('academic.class-subjects.assessments.store', $this->classSubject->id), [
            'scoring_component_id' => $this->componentTugas->id,
            'title' => 'Tugas 1 Delegasi',
            'assessed_on' => now()->toDateString(),
        ]);
        $createResponse->assertRedirect();

        $assessment = Assessment::where('title', 'Tugas 1 Delegasi')->first();
        $this->assertNotNull($assessment);

        // Can save draft scores
        $scoreResponse = $this->patch(route('academic.assessments.scores.update', $assessment->id), [
            'expected_version' => 1,
            'entries' => [
                [
                    'enrollment_id' => $this->enrollment1->id,
                    'score' => '78.50',
                    'feedback' => 'Input petugas',
                ],
            ],
        ]);
        $scoreResponse->assertRedirect();

        $this->assertDatabaseHas('scores', [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '78.50',
            'graded_by' => $this->dataEntry->id,
        ]);

        // Cannot publish (403 forbidden)
        $publishResponse = $this->post(route('academic.assessments.publish', $assessment->id), [
            'expected_version' => 2,
        ]);
        $publishResponse->assertForbidden();

        // Cannot delete (403 forbidden)
        $deleteResponse = $this->delete(route('academic.assessments.destroy', $assessment->id));
        $deleteResponse->assertForbidden();
    }

    public function test_data_entry_cannot_clear_existing_score(): void
    {
        // Teacher creates assessment and enters initial score
        $assessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentTugas->id,
            'title' => 'Tugas Hafalan',
            'assessed_on' => now()->toDateString(),
            'status' => Assessment::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->teacher->id,
        ]);

        Score::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '80.00',
            'graded_by' => $this->teacher->id,
        ]);

        // Grant active delegation to Data Entry
        DataEntryDelegation::create([
            'user_id' => $this->dataEntry->id,
            'teaching_assignment_id' => $this->assignment->id,
            'capability' => 'grade_entry',
            'valid_from' => now()->subDays(5)->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'granted_by' => $this->admin->id,
            'reason' => 'Bantuan input tugas',
        ]);

        $this->actingAs($this->dataEntry);

        // Data Entry attempts to send null score for enrollment1 (clear existing score)
        $response = $this->patch(route('academic.assessments.scores.update', $assessment->id), [
            'expected_version' => 1,
            'entries' => [
                [
                    'enrollment_id' => $this->enrollment1->id,
                    'score' => null, // Trying to clear!
                    'feedback' => null,
                ],
            ],
        ]);

        // Must fail with validation error and NOT delete score row
        $response->assertSessionHasErrors('entries');
        $this->assertDatabaseHas('scores', [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '80.00',
        ]);
    }

    public function test_optimistic_locking_prevents_stale_update_and_stale_scores(): void
    {
        $this->actingAs($this->teacher);

        $assessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentTugas->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => Assessment::STATUS_DRAFT,
            'version' => 3, // Current version is 3
            'created_by' => $this->teacher->id,
        ]);

        // Attempt score save with stale expected_version = 2
        $response = $this->patch(route('academic.assessments.scores.update', $assessment->id), [
            'expected_version' => 2,
            'entries' => [
                [
                    'enrollment_id' => $this->enrollment1->id,
                    'score' => '85.00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('expected_version');
        $this->assertEquals(3, $assessment->fresh()->version);
    }

    public function test_roster_version_mismatch_prevents_stale_scores(): void
    {
        $this->actingAs($this->teacher);

        $assessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentTugas->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => Assessment::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->teacher->id,
        ]);

        // Classroom roster version is 1, but client expected 0 (stale)
        $response = $this->patch(route('academic.assessments.scores.update', $assessment->id), [
            'expected_version' => 1,
            'expected_roster_version' => 0, // Stale!
            'entries' => [
                [
                    'enrollment_id' => $this->enrollment1->id,
                    'score' => '85.00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('roster_changed');
    }

    public function test_publish_locks_assessment_and_allows_partial_scores(): void
    {
        $this->actingAs($this->teacher);

        $assessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'UH Tajwid',
            'assessed_on' => '2026-08-25',
            'status' => Assessment::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->teacher->id,
        ]);

        // Only student 1 is graded, student 2 is blank (partial score allowed on publish)
        Score::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '88.00',
            'graded_by' => $this->teacher->id,
        ]);

        $publishResponse = $this->post(route('academic.assessments.publish', $assessment->id), [
            'expected_version' => 1,
        ]);

        $publishResponse->assertRedirect();
        $assessment->refresh();

        $this->assertEquals(Assessment::STATUS_PUBLISHED, $assessment->status);
        $this->assertNotNull($assessment->published_at);
        $this->assertEquals($this->teacher->id, $assessment->published_by);
        $this->assertEquals(2, $assessment->version);

        // Subsequent update scores on published assessment must fail (403 forbidden)
        $updateResponse = $this->patch(route('academic.assessments.scores.update', $assessment->id), [
            'expected_version' => 2,
            'entries' => [
                [
                    'enrollment_id' => $this->enrollment2->id,
                    'score' => '90.00',
                ],
            ],
        ]);
        $updateResponse->assertForbidden();

        // Direct delete of published assessment must fail (403 forbidden)
        $deleteResponse = $this->delete(route('academic.assessments.destroy', $assessment->id));
        $deleteResponse->assertForbidden();
    }

    public function test_published_assessment_integrates_with_grading_calculator(): void
    {
        // Setup an active grading policy
        $policy = GradingPolicy::create([
            'academic_year_id' => AcademicYear::first()->id,
            'revision' => 1,
            'kkm' => '70.00',
            'status' => 'active',
            'activated_at' => now(),
            'activated_by' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        // Policy weights: Tugas 40%, UH 60%
        $policy->components()->create([
            'scoring_component_id' => $this->componentTugas->id,
            'weight_percentage' => '40.00',
        ]);
        $policy->components()->create([
            'scoring_component_id' => $this->componentUH->id,
            'weight_percentage' => '60.00',
        ]);

        // Create published Tugas assessment with score 80
        $tugas = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentTugas->id,
            'title' => 'Tugas 1',
            'assessed_on' => '2026-08-01',
            'status' => Assessment::STATUS_PUBLISHED,
            'version' => 2,
            'created_by' => $this->teacher->id,
        ]);
        Score::create([
            'assessment_id' => $tugas->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '80.00',
            'graded_by' => $this->teacher->id,
        ]);

        // Create published UH assessment with score 90
        $uh = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $this->componentUH->id,
            'title' => 'UH 1',
            'assessed_on' => '2026-08-10',
            'status' => Assessment::STATUS_PUBLISHED,
            'version' => 2,
            'created_by' => $this->teacher->id,
        ]);
        Score::create([
            'assessment_id' => $uh->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '90.00',
            'graded_by' => $this->teacher->id,
        ]);

        // Fetch published scores for Ahmad (enrollment1)
        $scoresByComponent = Score::where('enrollment_id', $this->enrollment1->id)
            ->whereHas('assessment', fn ($q) => $q->where('status', Assessment::STATUS_PUBLISHED))
            ->with('assessment')
            ->get()
            ->groupBy(fn ($s) => (string) $s->assessment->scoring_component_id)
            ->map(fn ($group) => $group->pluck('score')->map(fn ($v) => (string) $v)->all())
            ->all();

        // Calculate final score using DP-23 GradingCalculator
        $calculator = new GradingCalculator;
        $result = $calculator->calculate(
            policy: $policy,
            scoresByComponent: $scoresByComponent,
            attendance: null
        );

        // Expected:
        // Tugas (40%): 80 * 0.40 = 32.00
        // UH (60%): 90 * 0.60 = 54.00
        // Total Final Score: 86.00, Complete: true, KKM: 'Mencapai KKM'
        $this->assertTrue($result->isComplete);
        $this->assertEquals('86.00', $result->finalScore);
        $this->assertEquals('Mencapai KKM', $result->kkmStatus);
    }
}
