<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Score;
use App\Models\ScoringComponent;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $academicAdmin;

    protected User $teacher;

    protected Classroom $classroom;

    protected ClassSubject $classSubject;

    protected Assessment $draftAssessment;

    protected Enrollment $enrollment1;

    protected Enrollment $enrollment2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->academicAdmin = User::factory()->create(['name' => 'Admin Akademik']);
        $this->academicAdmin->assignRole(Role::ACADEMIC_ADMIN);

        $this->teacher = User::factory()->create(['name' => 'Guru Mapel']);
        $this->teacher->assignRole(Role::TEACHER);

        $year = AcademicYear::create([
            'name' => '2025/2026',
            'starts_on' => '2025-07-15',
            'ends_on' => '2026-06-20',
        ]);

        $term = Term::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'starts_on' => '2025-07-15',
            'ends_on' => '2025-12-20',
            'status' => 'active',
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
        ]);

        $this->classSubject = ClassSubject::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $subject->id,
        ]);

        TeachingAssignment::create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
            'starts_on' => '2025-07-15',
            'status' => 'assigned',
        ]);

        $component = ScoringComponent::create([
            'code' => 'TGS',
            'name' => 'Tugas Harian',
            'weight' => 20,
            'source_type' => 'manual',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->draftAssessment = Assessment::create([
            'class_subject_id' => $this->classSubject->id,
            'scoring_component_id' => $component->id,
            'created_by' => $this->teacher->id,
            'title' => 'Tugas 1 Fiqih Ibadah',
            'assessed_on' => '2025-08-10',
            'status' => 'draft',
            'version' => 1,
        ]);

        $student1 = Student::create([
            'nis' => '101',
            'name' => 'Ahmad Rasyid',
            'gender' => 'L',
            'birth_date' => '2018-05-10',
            'birth_place' => 'Bandung',
            'status' => 'active',
        ]);

        $student2 = Student::create([
            'nis' => '102',
            'name' => 'Fatimah Zahra',
            'gender' => 'P',
            'birth_date' => '2018-08-20',
            'birth_place' => 'Bandung',
            'status' => 'active',
        ]);

        $this->enrollment1 = Enrollment::create([
            'student_id' => $student1->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $term->id,
            'starts_on' => '2025-07-15',
            'status' => 'active',
        ]);

        $this->enrollment2 = Enrollment::create([
            'student_id' => $student2->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $term->id,
            'starts_on' => '2025-07-15',
            'status' => 'active',
        ]);
    }

    public function test_authorized_user_can_export_students_csv_stream(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->get(route('academic.students.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('nis,name,gender,birth_place,birth_date,status', $content);
        $this->assertStringContainsString('101,"Ahmad Rasyid",L,Bandung,2018-05-10,active', $content);
        $this->assertStringContainsString('102,"Fatimah Zahra",P,Bandung,2018-08-20,active', $content);
    }

    public function test_authorized_user_can_download_student_csv_template(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->get(route('academic.students.template'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('nis,name,gender,birth_place,birth_date', $content);
        $this->assertStringContainsString('1001,"Ahmad Santoso",L,Bandung,2015-05-12', $content);
    }

    public function test_authorized_user_can_bulk_import_students_from_csv(): void
    {
        $csvContent = "nis,name,gender,birth_place,birth_date\n"
            ."201,Muhammad Ali,L,Bandung,2018-03-15\n"
            ."202,Aisyah Humaira,P,Cimahi,2018-04-12\n";

        $file = UploadedFile::fake()->createWithContent('import_siswa.csv', $csvContent);

        $response = $this->actingAs($this->academicAdmin)
            ->post(route('academic.students.import'), [
                'file' => $file,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('students', [
            'nis' => '201',
            'name' => 'Muhammad Ali',
            'gender' => 'L',
            'birth_place' => 'Bandung',
            'birth_date' => '2018-03-15',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('students', [
            'nis' => '202',
            'name' => 'Aisyah Humaira',
            'gender' => 'P',
            'birth_place' => 'Cimahi',
            'birth_date' => '2018-04-12',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'IMPORT_STUDENTS',
            'scope' => 'student',
            'actor_user_id' => $this->academicAdmin->id,
        ]);
    }

    public function test_student_import_rejects_duplicate_nis_and_rolls_back_atomically(): void
    {
        // 101 already exists in database from setUp
        $csvContent = "nis,name,gender,birth_date\n"
            ."301,Santoso,L,2018-01-01\n"
            ."101,Ahmad Duplikat,L,2018-01-01\n";

        $file = UploadedFile::fake()->createWithContent('duplicate.csv', $csvContent);

        $response = $this->actingAs($this->academicAdmin)
            ->post(route('academic.students.import'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');

        // Verify row 301 was NOT inserted (atomic all-or-nothing rollback)
        $this->assertDatabaseMissing('students', [
            'nis' => '301',
        ]);
    }

    public function test_teacher_can_download_assessment_score_template_csv(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('academic.assessments.template', $this->draftAssessment));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('enrollment_id,nis,student_name,score,feedback', $content);
        $this->assertStringContainsString("{$this->enrollment1->id},101,\"Ahmad Rasyid\",,", $content);
        $this->assertStringContainsString("{$this->enrollment2->id},102,\"Fatimah Zahra\",,", $content);
    }

    public function test_teacher_can_import_assessment_scores_csv(): void
    {
        $csvContent = "enrollment_id,nis,student_name,score,feedback\n"
            ."{$this->enrollment1->id},101,Ahmad Rasyid,85.50,Sangat baik dalam hafalan doa\n"
            ."{$this->enrollment2->id},102,Fatimah Zahra,92.00,Penguasaan materi sempurna\n";

        $file = UploadedFile::fake()->createWithContent('scores.csv', $csvContent);

        $response = $this->actingAs($this->teacher)
            ->post(route('academic.assessments.import', $this->draftAssessment), [
                'expected_version' => 1,
                'file' => $file,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('scores', [
            'assessment_id' => $this->draftAssessment->id,
            'enrollment_id' => $this->enrollment1->id,
            'score' => '85.50',
            'feedback' => 'Sangat baik dalam hafalan doa',
            'graded_by' => $this->teacher->id,
        ]);

        $this->assertDatabaseHas('scores', [
            'assessment_id' => $this->draftAssessment->id,
            'enrollment_id' => $this->enrollment2->id,
            'score' => '92.00',
            'feedback' => 'Penguasaan materi sempurna',
            'graded_by' => $this->teacher->id,
        ]);

        // Assessment version is bumped
        $this->assertSame(2, $this->draftAssessment->fresh()->version);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'IMPORT_SCORES',
            'scope' => 'assessment',
            'entity_id' => $this->draftAssessment->id,
            'actor_user_id' => $this->teacher->id,
        ]);
    }

    public function test_assessment_import_rejects_out_of_range_scores_and_optimistic_locking_conflict(): void
    {
        // 1. Out of range score (> 100)
        $csvInvalid = "enrollment_id,score\n{$this->enrollment1->id},105.00\n";
        $fileInvalid = UploadedFile::fake()->createWithContent('invalid.csv', $csvInvalid);

        $responseFail = $this->actingAs($this->teacher)
            ->post(route('academic.assessments.import', $this->draftAssessment), [
                'expected_version' => 1,
                'file' => $fileInvalid,
            ]);

        $responseFail->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('scores', [
            'assessment_id' => $this->draftAssessment->id,
        ]);

        // 2. Version mismatch
        $csvValid = "enrollment_id,score\n{$this->enrollment1->id},80.00\n";
        $fileValid = UploadedFile::fake()->createWithContent('valid.csv', $csvValid);

        $responseVersionMismatch = $this->actingAs($this->teacher)
            ->post(route('academic.assessments.import', $this->draftAssessment), [
                'expected_version' => 999,
                'file' => $fileValid,
            ]);

        $responseVersionMismatch->assertSessionHasErrors('expected_version');
    }

    public function test_cannot_import_scores_into_published_assessment(): void
    {
        $this->draftAssessment->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $this->teacher->id,
        ]);

        $csvValid = "enrollment_id,score\n{$this->enrollment1->id},80.00\n";
        $fileValid = UploadedFile::fake()->createWithContent('valid.csv', $csvValid);

        $response = $this->actingAs($this->teacher)
            ->post(route('academic.assessments.import', $this->draftAssessment), [
                'expected_version' => 1,
                'file' => $fileValid,
            ]);

        // Policy or controller validation blocks update on published assessment
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302], true));
        if ($response->getStatusCode() === 302) {
            $response->assertSessionHasErrors();
        }
    }
}
