<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiClassTeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $academicAdmin;

    private User $masterAdmin;

    private User $teacher;

    private User $guardian;

    private Term $activeTerm;

    private Classroom $class1A;

    private Classroom $class1B;

    private Classroom $class2A;

    private Subject $mathSubject;

    private Subject $arabicSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicAdmin = User::factory()->create(['name' => 'Admin Akademik']);
        $this->academicAdmin->assignRole(Role::ACADEMIC_ADMIN);

        $this->masterAdmin = User::factory()->create(['name' => 'Master Admin']);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->teacher = User::factory()->create(['name' => 'Ustadz Ahmad']);
        $this->teacher->assignRole(Role::TEACHER);

        $this->guardian = User::factory()->create(['name' => 'Wali Murid']);
        $this->guardian->assignRole(Role::GUARDIAN);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-15',
            'ends_on' => '2027-06-20',
        ]);

        $this->activeTerm = Term::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'starts_on' => '2026-07-15',
            'ends_on' => '2026-12-20',
            'status' => 'active',
        ]);

        $this->class1A = Classroom::create([
            'term_id' => $this->activeTerm->id,
            'grade_level' => 1,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $this->class1B = Classroom::create([
            'term_id' => $this->activeTerm->id,
            'grade_level' => 1,
            'section' => 'B',
            'roster_version' => 1,
        ]);

        $this->class2A = Classroom::create([
            'term_id' => $this->activeTerm->id,
            'grade_level' => 2,
            'section' => 'A',
            'roster_version' => 1,
        ]);

        $this->mathSubject = Subject::create([
            'code' => 'MTK',
            'name' => 'Matematika',
            'category' => 'umum',
            'is_active' => true,
        ]);

        $this->arabicSubject = Subject::create([
            'code' => 'ARB',
            'name' => 'Bahasa Arab',
            'category' => 'agama',
            'is_active' => true,
        ]);
    }

    public function test_academic_admin_and_master_admin_can_view_teaching_assignments_page(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->get('/academic/teaching-assignments');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Academic/TeachingAssignments/Index')
            ->has('terms')
            ->has('classrooms', 3)
            ->has('subjects', 2)
            ->has('teachers', 1)
            ->has('classSubjects.data')
            ->has('classSubjects.links')
        );

        $responseMaster = $this->actingAs($this->masterAdmin)
            ->get('/academic/teaching-assignments');
        $responseMaster->assertOk();
    }

    public function test_pagination_and_search_filter_work_correctly(): void
    {
        // Bulk assign math to classrooms
        $this->actingAs($this->academicAdmin)
            ->post('/academic/teaching-assignments', [
                'subject_id' => $this->mathSubject->id,
                'classroom_ids' => [$this->class1A->id, $this->class1B->id],
                'teacher_id' => $this->teacher->id,
            ]);

        // Bulk assign arabic to 2A
        $this->actingAs($this->academicAdmin)
            ->post('/academic/teaching-assignments', [
                'subject_id' => $this->arabicSubject->id,
                'classroom_ids' => [$this->class2A->id],
            ]);

        // Test pagination per_page
        $response = $this->actingAs($this->academicAdmin)
            ->get('/academic/teaching-assignments?per_page=2');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('classSubjects.data', 2)
            ->where('classSubjects.total', 3)
            ->where('classSubjects.per_page', 2)
        );

        // Test search filter
        $responseSearch = $this->actingAs($this->academicAdmin)
            ->get('/academic/teaching-assignments?search=Arab');
        $responseSearch->assertOk();
        $responseSearch->assertInertia(fn ($page) => $page
            ->has('classSubjects.data', 1)
            ->where('classSubjects.data.0.subject.name', 'Bahasa Arab')
        );
    }

    public function test_unauthorized_users_cannot_access_teaching_assignments(): void
    {
        $this->actingAs($this->teacher)
            ->get('/academic/teaching-assignments')
            ->assertForbidden();

        $this->actingAs($this->guardian)
            ->get('/academic/teaching-assignments')
            ->assertForbidden();
    }

    public function test_can_bulk_assign_subject_to_multiple_classrooms_simultaneously(): void
    {
        $response = $this->actingAs($this->academicAdmin)
            ->post('/academic/teaching-assignments', [
                'subject_id' => $this->mathSubject->id,
                'classroom_ids' => [
                    $this->class1A->id,
                    $this->class1B->id,
                    $this->class2A->id,
                ],
                'teacher_id' => $this->teacher->id,
                'starts_on' => '2026-08-01',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify ClassSubject created for all 3 classrooms
        $this->assertDatabaseHas('class_subjects', [
            'classroom_id' => $this->class1A->id,
            'subject_id' => $this->mathSubject->id,
        ]);
        $this->assertDatabaseHas('class_subjects', [
            'classroom_id' => $this->class1B->id,
            'subject_id' => $this->mathSubject->id,
        ]);
        $this->assertDatabaseHas('class_subjects', [
            'classroom_id' => $this->class2A->id,
            'subject_id' => $this->mathSubject->id,
        ]);

        // Verify TeachingAssignment created for each
        $cs1A = ClassSubject::where('classroom_id', $this->class1A->id)
            ->where('subject_id', $this->mathSubject->id)
            ->first();
        $this->assertNotNull($cs1A);
        $this->assertDatabaseHas('teaching_assignments', [
            'class_subject_id' => $cs1A->id,
            'teacher_id' => $this->teacher->id,
            'starts_on' => '2026-08-01 00:00:00',
            'status' => 'assigned',
        ]);
    }

    public function test_reassigning_teacher_performs_handover(): void
    {
        // First assignment with teacher A
        $this->actingAs($this->academicAdmin)
            ->post('/academic/teaching-assignments', [
                'subject_id' => $this->mathSubject->id,
                'classroom_ids' => [$this->class1A->id],
                'teacher_id' => $this->teacher->id,
                'starts_on' => '2026-08-01',
            ]);

        $newTeacher = User::factory()->create(['name' => 'Ustadzah Sarah']);
        $newTeacher->assignRole(Role::TEACHER);

        // Handover to new teacher
        $this->actingAs($this->academicAdmin)
            ->post('/academic/teaching-assignments', [
                'subject_id' => $this->mathSubject->id,
                'classroom_ids' => [$this->class1A->id],
                'teacher_id' => $newTeacher->id,
                'starts_on' => '2026-09-01',
            ]);

        $cs = ClassSubject::where('classroom_id', $this->class1A->id)
            ->where('subject_id', $this->mathSubject->id)
            ->first();

        // Old assignment should have ends_on closed
        $this->assertDatabaseHas('teaching_assignments', [
            'class_subject_id' => $cs->id,
            'teacher_id' => $this->teacher->id,
            'ends_on' => '2026-09-01 00:00:00',
        ]);

        // New assignment should be open and active
        $this->assertDatabaseHas('teaching_assignments', [
            'class_subject_id' => $cs->id,
            'teacher_id' => $newTeacher->id,
            'starts_on' => '2026-09-01 00:00:00',
            'ends_on' => null,
        ]);
    }

    public function test_cannot_assign_non_teacher_user(): void
    {
        $nonTeacher = User::factory()->create();

        $response = $this->actingAs($this->academicAdmin)
            ->post('/academic/teaching-assignments', [
                'subject_id' => $this->mathSubject->id,
                'classroom_ids' => [$this->class1A->id],
                'teacher_id' => $nonTeacher->id,
            ]);

        $response->assertSessionHasErrors('teacher_id');
    }

    public function test_can_remove_assignment_if_no_assessments(): void
    {
        $this->actingAs($this->academicAdmin)
            ->post('/academic/teaching-assignments', [
                'subject_id' => $this->mathSubject->id,
                'classroom_ids' => [$this->class1A->id],
                'teacher_id' => $this->teacher->id,
            ]);

        $cs = ClassSubject::where('classroom_id', $this->class1A->id)
            ->where('subject_id', $this->mathSubject->id)
            ->first();

        $response = $this->actingAs($this->academicAdmin)
            ->delete("/academic/teaching-assignments/{$cs->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('class_subjects', ['id' => $cs->id]);
    }
}
