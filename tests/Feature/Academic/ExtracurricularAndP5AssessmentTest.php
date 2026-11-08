<?php

namespace Tests\Feature\Academic;

use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularAssessment;
use App\Models\GradingPolicy;
use App\Models\HomeroomAssignment;
use App\Models\P5Assessment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\Reports\ReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtracurricularAndP5AssessmentTest extends TestCase
{
    use RefreshDatabase;

    private User $masterAdmin;

    private User $homeroomTeacher;

    private User $otherTeacher;

    private User $guardian;

    private Term $term;

    private Classroom $classroom;

    private Student $student;

    private Enrollment $enrollment;

    private ReportCard $reportCard;

    private GradingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->masterAdmin = User::factory()->create();
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->homeroomTeacher = User::factory()->create();
        $this->homeroomTeacher->assignRole(Role::TEACHER);

        $this->otherTeacher = User::factory()->create();
        $this->otherTeacher->assignRole(Role::TEACHER);

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

        $this->classroom = Classroom::create([
            'term_id' => $this->term->id,
            'grade_level' => 4,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $homeroom = HomeroomAssignment::create([
            'classroom_id' => $this->classroom->id,
            'teacher_id' => $this->homeroomTeacher->id,
            'status' => 'active',
        ]);

        $this->student = Student::create([
            'nis' => '4001',
            'name' => 'Fatima Az-Zahra',
            'gender' => 'P',
            'status' => 'active',
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'term_id' => $this->term->id,
            'starts_on' => '2026-07-15',
            'ends_on' => null,
            'status' => 'active',
        ]);

        $this->policy = GradingPolicy::create([
            'created_by' => $this->masterAdmin->id,
            'name' => 'Kebijakan MI Standar',
            'kkm' => '75.00',
            'revision' => 1,
            'status' => 'active',
        ]);

        $this->reportCard = ReportCard::create([
            'enrollment_id' => $this->enrollment->id,
            'term_id' => $this->term->id,
            'grading_policy_id' => $this->policy->id,
            'homeroom_assignment_id' => $homeroom->id,
            'revision' => 1,
            'status' => ReportCardStatus::DRAFT,
            'availability' => ReportCardAvailability::CURRENT,
            'version' => 1,
            'source_version' => 'src_test123',
            'calculation_version' => '1.0',
            'generated_at' => now(),
        ]);
    }

    public function test_homeroom_teacher_and_admin_can_add_extracurricular_assessment(): void
    {
        $response = $this->actingAs($this->homeroomTeacher)
            ->post(route('academic.report-cards.extracurricular.store', $this->reportCard->id), [
                'activity_name' => 'Pramuka Siaga',
                'predicate' => 'A',
                'description' => 'Sangat aktif mengikuti latihan mingguan dan terampil dalam membuat simpul tali.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('extracurricular_assessments', [
            'report_card_id' => $this->reportCard->id,
            'activity_name' => 'Pramuka Siaga',
            'predicate' => 'A',
        ]);

        $extra = ExtracurricularAssessment::where('report_card_id', $this->reportCard->id)->first();
        $this->assertEquals('Sangat Baik', $extra->predicate_label);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ADD_EXTRACURRICULAR',
            'entity_id' => $extra->id,
            'actor_user_id' => $this->homeroomTeacher->id,
        ]);
    }

    public function test_homeroom_teacher_and_admin_can_delete_extracurricular_assessment(): void
    {
        $extra = ExtracurricularAssessment::create([
            'report_card_id' => $this->reportCard->id,
            'activity_name' => 'Tahfidz Al-Quran',
            'predicate' => 'B',
            'description' => 'Mampu menghafal Juz 30 dengan tartil.',
        ]);

        $response = $this->actingAs($this->masterAdmin)
            ->delete(route('academic.report-cards.extracurricular.destroy', $extra->id));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseMissing('extracurricular_assessments', [
            'id' => $extra->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DELETE_EXTRACURRICULAR',
            'entity_id' => $extra->id,
            'actor_user_id' => $this->masterAdmin->id,
        ]);
    }

    public function test_homeroom_teacher_and_admin_can_add_and_delete_p5_assessment(): void
    {
        $response = $this->actingAs($this->homeroomTeacher)
            ->post(route('academic.report-cards.p5.store', $this->reportCard->id), [
                'project_title' => 'Gaya Hidup Berkelanjutan: Pengolahan Sampah Organik',
                'dimension' => 'Gotong Royong',
                'predicate' => 'BSH',
                'description' => 'Menunjukkan inisiatif dalam kerja kelompok memilah sampah di madrasah.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $p5 = P5Assessment::where('report_card_id', $this->reportCard->id)->first();
        $this->assertNotNull($p5);
        $this->assertEquals('Gaya Hidup Berkelanjutan: Pengolahan Sampah Organik', $p5->project_title);
        $this->assertEquals('BSH', $p5->predicate);
        $this->assertEquals('Berkembang Sesuai Harapan', $p5->predicate_label);

        // Delete P5 assessment
        $delResponse = $this->actingAs($this->masterAdmin)
            ->delete(route('academic.report-cards.p5.destroy', $p5->id));

        $delResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('p5_assessments', ['id' => $p5->id]);
    }

    public function test_cannot_modify_assessments_on_finalized_or_published_report_cards(): void
    {
        $this->reportCard->update(['status' => ReportCardStatus::FINALIZED]);

        $this->actingAs($this->homeroomTeacher)
            ->post(route('academic.report-cards.extracurricular.store', $this->reportCard->id), [
                'activity_name' => 'Futsal',
                'predicate' => 'B',
                'description' => 'Bermain dengan sportif.',
            ])
            ->assertForbidden();

        $this->actingAs($this->homeroomTeacher)
            ->post(route('academic.report-cards.p5.store', $this->reportCard->id), [
                'project_title' => 'Kewirausahaan',
                'dimension' => 'Mandiri',
                'predicate' => 'SAB',
                'description' => 'Mandiri dalam mengelola stan bazar.',
            ])
            ->assertForbidden();
    }

    public function test_unauthorized_users_cannot_manage_extracurricular_or_p5(): void
    {
        $this->actingAs($this->otherTeacher)
            ->post(route('academic.report-cards.extracurricular.store', $this->reportCard->id), [
                'activity_name' => 'Pramuka',
                'predicate' => 'A',
                'description' => 'Catatan.',
            ])
            ->assertForbidden();

        $this->actingAs($this->guardian)
            ->post(route('academic.report-cards.p5.store', $this->reportCard->id), [
                'project_title' => 'Projek',
                'dimension' => 'Kreatif',
                'predicate' => 'MB',
                'description' => 'Catatan.',
            ])
            ->assertForbidden();
    }

    public function test_validation_rejects_invalid_predicates(): void
    {
        $this->actingAs($this->homeroomTeacher)
            ->post(route('academic.report-cards.extracurricular.store', $this->reportCard->id), [
                'activity_name' => 'Pramuka',
                'predicate' => 'Z', // Invalid predicate
                'description' => 'Catatan.',
            ])
            ->assertSessionHasErrors('predicate');

        $this->actingAs($this->homeroomTeacher)
            ->post(route('academic.report-cards.p5.store', $this->reportCard->id), [
                'project_title' => 'Projek',
                'dimension' => 'Kreatif',
                'predicate' => 'INVALID', // Invalid P5 predicate
                'description' => 'Catatan.',
            ])
            ->assertSessionHasErrors('predicate');
    }

    public function test_pdf_generation_renders_extracurricular_and_p5_sections(): void
    {
        ExtracurricularAssessment::create([
            'report_card_id' => $this->reportCard->id,
            'activity_name' => 'Pramuka Siaga',
            'predicate' => 'A',
            'description' => 'Memiliki kecakapan tali temali yang sangat baik.',
        ]);

        P5Assessment::create([
            'report_card_id' => $this->reportCard->id,
            'project_title' => 'Kearifan Lokal Budaya Sunda',
            'dimension' => 'Berkebinekaan Global',
            'predicate' => 'BSH',
            'description' => 'Mengenal dan menghargai kesenian tradisional daerah.',
        ]);

        /** @var ReportPdfService $pdfService */
        $pdfService = app(ReportPdfService::class);
        $viewData = $pdfService->prepareViewData($this->reportCard);

        $this->assertCount(1, $viewData['extracurriculars']);
        $this->assertEquals('Pramuka Siaga', $viewData['extracurriculars'][0]->activity_name);

        $this->assertCount(1, $viewData['p5Assessments']);
        $this->assertEquals('Kearifan Lokal Budaya Sunda', $viewData['p5Assessments'][0]->project_title);

        $storedPath = $pdfService->renderAndStore($this->reportCard);
        $this->assertNotEmpty($storedPath);
        $this->assertTrue(Storage::disk('local')->exists($storedPath));
    }
}
